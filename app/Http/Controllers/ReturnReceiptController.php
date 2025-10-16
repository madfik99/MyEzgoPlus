<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use App\Models\BookingTrans;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;

class ReturnReceiptController extends Controller
{
    /**
     * Resolve a file path that may be stored as:
     *  - "damage_markings/return_123.jpg" (no "storage/" prefix)
     *  - "storage/damage_markings/return_123.jpg"
     *  - "http(s)://.../storage/damage_markings/return_123.jpg"
     *  - or just a filename like "return_123.jpg" (assume in {subdir})
     *
     * Returns an absolute filesystem path (for FPDI) or null if not found.
     */
    private function resolvePublicStoragePath(?string $value, string $subdir): ?string
    {
        if (!$value) return null;

        $v = trim($value);

        // Full URL with "/storage/..."
        if (Str::contains($v, '/storage/')) {
            $rel = Str::after($v, '/storage/');       // e.g. "damage_markings/return_123.jpg"
            $abs = public_path('storage/' . ltrim($rel, '/'));
            return file_exists($abs) ? $abs : null;
        }

        // Already "storage/..."
        if (Str::startsWith($v, ['storage/', '/storage/'])) {
            $abs = public_path(ltrim($v, '/'));       // public/storage/...
            return file_exists($abs) ? $abs : null;
        }

        // Starts with expected subdir, e.g. "damage_markings/..."
        if (Str::startsWith($v, [$subdir . '/', '/' . $subdir . '/'])) {
            $rel = ltrim($v, '/');                    // "damage_markings/return_123.jpg"
            if (Storage::disk('public')->exists($rel)) {
                return Storage::disk('public')->path($rel); // storage/app/public/...
            }
            $abs = public_path('storage/' . $rel);    // fallback to public/storage
            return file_exists($abs) ? $abs : null;
        }

        // Plain filename — assume inside the subdir on "public" disk
        $candidate = $subdir . '/' . $v;
        if (Storage::disk('public')->exists($candidate)) {
            return Storage::disk('public')->path($candidate);
        }
        $abs = public_path('storage/' . $candidate);
        return file_exists($abs) ? $abs : null;
    }

    public function generate($id)
    {
        // Load booking + relations
        $booking = BookingTrans::with([
            'vehicle',
            'customer',
            'checklist',
        ])->findOrFail($id);

        $vehicle  = $booking->vehicle;
        $customer = $booking->customer;
        $cl       = $booking->checklist; // contains car_in_*/car_out_* fields
        $company  = Company::first();
        $nickname = optional(User::find($booking->staff_id))->name;

        // Totals & extend rows
        $totalExt = (float) DB::table('extend')
            ->where('booking_trans_id', $booking->id)
            ->sum('total');

        $extends = DB::table('extend')
            ->where('extend_type', 'manual')
            ->where('booking_trans_id', $booking->id)
            ->selectRaw("
                DATE_FORMAT(extend_from_date, '%d/%m/%Y') AS from_date,
                DATE_FORMAT(extend_from_time, '%H:%i')     AS from_time,
                DATE_FORMAT(extend_to_date,   '%d/%m/%Y')  AS to_date,
                DATE_FORMAT(extend_to_time,   '%H:%i')     AS to_time,
                payment_status,
                payment_type,
                DATE_FORMAT(c_date, '%d/%m/%Y')            AS c_date,
                total
            ")
            ->get();

        // PDF setup
        $pdf = new Fpdi('P', 'mm', [203, 305]);
        $pdf->AddPage();
        $tpl = public_path('assets/document/returnreceipt.pdf');
        $pdf->setSourceFile($tpl);
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx);

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // === sizes (tweak as needed) ===
        $SIGN_WIDTH   = 20; // smaller than before (was 27)
        $SIGN_WIDTH2  = 17;
        $DAMAGE_WIDTH = 38; // smaller than before (was 50)

        // Company header + logo
        if ($company && $company->image && file_exists(public_path('assets/img/company/' . $company->image))) {
            $pdf->Image(public_path('assets/img/company/' . $company->image), 5, 8, 20);
        }
        $pdf->SetXY(25, 12);   $pdf->Write(0, $company->company_name ?? '');
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(175, 3.7); $pdf->Write(0, date('d/m/Y'));
        $pdf->SetXY(25, 14);   $pdf->MultiCell(70, 3.5, $company->address ?? '', 0);
        $pdf->SetXY(25, 23);   $pdf->Write(0, $company->phone_no ?? '');

        // Top booking/customer box
        $fullname = trim(($customer->firstname ?? '') . ' ' . ($customer->lastname ?? ''));
        $pdf->SetXY(137, 8);   $pdf->Write(0, $booking->agreement_no ?? '');
        $pdf->SetXY(137, 12);  $pdf->MultiCell(65, 3, $fullname, 0);
        $pdf->SetXY(137, 21);  $pdf->Write(0, $vehicle->reg_no ?? '');
        $pdf->SetXY(153, 21);  $pdf->Write(0, '/');
        $pdf->SetXY(155, 21);  $pdf->Write(0, $vehicle->model ?? '');

        // Pickup/Return line
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetXY(137, 26.5);   $pdf->Write(0, Carbon::parse($booking->pickup_date)->format('d/m/Y'));
        $pdf->SetXY(148, 26.5);   $pdf->Write(0, '@');
        $pdf->SetXY(150.5, 26.5); $pdf->Write(0, Carbon::parse($booking->pickup_date)->format('H:i'));
        $pdf->SetXY(160, 26.5);   $pdf->Write(0, '/');
        $pdf->SetXY(162, 26.5);   $pdf->Write(0, Carbon::parse($booking->return_date)->format('d/m/Y'));
        $pdf->SetXY(173, 26.5);   $pdf->Write(0, '@');
        $pdf->SetXY(176, 26.5);   $pdf->Write(0, Carbon::parse($booking->return_date)->format('H:i'));

        // Helper to write (x,y,value)
        $put = function (float $x, float $y, $val) use ($pdf) {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetXY($x, $y);
            $pdf->Write(0, (string) ($val ?? ''));
        };

        // Car OUT checks
        $put(39, 37.2, $cl->car_out_start_engine ?? '');
        $put(39, 42.2, $cl->car_out_no_alarm ?? '');
        $put(39, 47.2, $cl->car_out_wiper ?? '');
        $put(39, 52.2, $cl->car_out_air_conditioner ?? '');
        $put(39, 57.2, $cl->car_out_radio ?? '');
        $put(39, 62.2, $cl->car_out_power_window ?? '');
        $put(39, 67.2, $cl->car_out_window_condition ?? '');
        $put(39, 72.2, $cl->car_out_perfume ?? '');
        $put(39, 77.2, $cl->car_out_carpet ?? '');
        $put(39, 82.2, $cl->car_out_lamp ?? '');
        $put(39, 87.2, $cl->car_out_engine_condition ?? '');
        $put(89, 37.2, $cl->car_out_tyres_condition ?? '');
        $put(89, 42.2, $cl->car_out_jack ?? '');
        $put(89, 47.2, $cl->car_out_tools ?? '');
        $put(89, 52.2, $cl->car_out_signage ?? '');
        $put(89, 57.2, $cl->car_out_tyre_spare ?? '');
        $put(89, 62.2, $cl->car_out_sticker_p ?? '');
        $put(89, 67.2, $cl->car_out_usb_charger ?? '');
        $put(89, 72.2, $cl->car_out_touch_n_go ?? '');
        $put(89, 77.2, $cl->car_out_smart_tag ?? '');
        $put(89, 82.2, $cl->car_out_child_seat ?? '');
        $put(89, 87.2, $cl->car_out_gps ?? '');
        $put(137, 37.5, $cl->car_out_seat_condition ?? '');
        $put(137, 42.5, $cl->car_out_cleanliness ?? '');

        // Fuel bars
        $drawFuel = function (Fpdi $pdf, $level, $xStart) {
            $level = (int) $level;
            $bars = [
                [0, 52, 3, 2],
                [4.5, 51, 2.5, 3],
                [8.7, 49.8, 2.5, 4],
                [12.7, 48, 2.5, 6],
                [16.7, 46.5, 2.5, 7.5],
                [20.8, 45.2, 2.5, 8.6],
            ];
            for ($i = 0; $i < min($level, 6); $i++) {
                [$dx, $y, $w, $h] = $bars[$i];
                $pdf->Rect($xStart + $dx, $y, $w, $h, 'F');
            }
        };

        if (!is_null($cl->car_out_fuel_level ?? null)) {
            $drawFuel($pdf, $cl->car_out_fuel_level, 142.0);
        }

        // Car OUT damage marking (smaller)
        if (!empty($cl->car_out_image ?? null)) {
            $outPath = $this->resolvePublicStoragePath($cl->car_out_image, 'damage_markings');
            if ($outPath) {
                $pdf->Image($outPath, 107, 63.5, $DAMAGE_WIDTH);
            }
        }

        $pdf->SetXY(2, 103);      $pdf->Write(0, (string) ($cl->car_out_remark ?? ''));
        $pdf->SetXY(1.8, 112);    $pdf->Write(0, 'Y');
        $pdf->SetXY(1.8, 119.2);  $pdf->Write(0, 'Y');

        // Car IN checks
        $put(45.8, 37.2, $cl->car_in_start_engine ?? '');
        $put(45.8, 42.2, $cl->car_in_no_alarm ?? '');
        $put(45.8, 47.2, $cl->car_in_wiper ?? '');
        $put(45.8, 52.2, $cl->car_in_air_conditioner ?? '');
        $put(45.8, 57.2, $cl->car_in_radio ?? '');
        $put(45.8, 62.2, $cl->car_in_power_window ?? '');
        $put(45.8, 67.2, $cl->car_in_window_condition ?? '');
        $put(45.8, 72.2, $cl->car_in_perfume ?? '');
        $put(45.8, 77.2, $cl->car_in_carpet ?? '');
        $put(45.8, 82.2, $cl->car_in_lamp ?? '');
        $put(45.8, 87.2, $cl->car_in_engine_condition ?? '');
        $put(95.8, 37.2, $cl->car_in_tyres_condition ?? '');
        $put(95.8, 42.2, $cl->car_in_jack ?? '');
        $put(95.8, 47.2, $cl->car_in_tools ?? '');
        $put(95.8, 52.2, $cl->car_in_signage ?? '');
        $put(95.8, 57.2, $cl->car_in_tyre_spare ?? '');
        $put(95.8, 62.2, $cl->car_in_sticker_p ?? '');
        $put(95.8, 67.2, $cl->car_in_usb_charger ?? '');
        $put(95.8, 72.2, $cl->car_in_touch_n_go ?? '');
        $put(95.8, 77.2, $cl->car_in_smart_tag ?? '');
        $put(95.8, 82.2, $cl->car_in_child_seat ?? '');
        $put(95.8, 87.2, $cl->car_in_gps ?? '');
        $put(168.5, 37.5, $cl->car_in_seat_condition ?? '');
        $put(168.5, 42.5, $cl->car_in_cleanliness ?? '');

        if (!is_null($cl->car_in_fuel_level ?? null)) {
            $drawFuel($pdf, $cl->car_in_fuel_level, 175.3);
        }

        // Car IN damage marking (smaller)
        if (!empty($cl->car_in_image ?? null)) {
            $inPath = $this->resolvePublicStoragePath($cl->car_in_image, 'damage_markings');
            if ($inPath) {
                $pdf->Image($inPath, 157.5, 63.5, $DAMAGE_WIDTH);
            }
        }

        $pdf->SetXY(103, 102.8); $pdf->Write(0, (string) ($cl->car_in_remark ?? ''));
        $pdf->SetXY(102.8, 112); $pdf->Write(0, 'Y');
        $pdf->SetXY(102.8, 119); $pdf->Write(0, 'Y');

        // Names + signatures
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(1, 127);   $pdf->MultiCell(25, 4, (string) ($cl->car_out_checkby ?? ''), 0, 'L');
        $pdf->SetXY(28, 129);  $pdf->MultiCell(40, 4, $fullname, 0, 'L');

        // PICKUP signature (smaller)
        if (!empty($cl->car_out_sign_image ?? null)) {
            $pickupSignPath = $this->resolvePublicStoragePath($cl->car_out_sign_image, 'sign_pickup');
            if ($pickupSignPath) {
                $pdf->Image($pickupSignPath, 75, 124.5, $SIGN_WIDTH);
            }
        }

        // RETURN signature (smaller)
        if (!empty($cl->car_in_sign_image ?? null)) {
            $returnSignPath = $this->resolvePublicStoragePath($cl->car_in_sign_image, 'sign_return');
            if ($returnSignPath) {
                $pdf->Image($returnSignPath, 175, 124.5, $SIGN_WIDTH);
                $pdf->Image($returnSignPath, 160, 273,  $SIGN_WIDTH2);
            }
        }

        $pdf->SetXY(102, 127); $pdf->MultiCell(25, 4, (string) ($cl->car_in_checkby ?? ''), 0, 'L');
        $pdf->SetXY(128, 129);
        $pdf->MultiCell(40, 4,
            is_null($cl->car_in_return_person_nric_no ?? null) ? $fullname : (string) ($cl->car_in_return_person_name ?? ''),
            0, 'L'
        );

        // Extend rows table (manual)
        $pdf->SetXY(11, 129);
        foreach ($extends as $ext) {
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY(11, $y);     $pdf->MultiCell(50, 48, $ext->from_date, 0, 'L', false);
            $pdf->SetXY(26,  $y);    $pdf->MultiCell(50, 48, '-', 0, 'L', false);
            $pdf->SetXY(27.5,$y);    $pdf->MultiCell(50, 48, $ext->from_time, 0, 'L', false);
            $pdf->SetXY(36,  $y);    $pdf->MultiCell(50, 48, '@', 0, 'L', false);
            $pdf->SetXY(40,  $y);    $pdf->MultiCell(50, 48, $ext->to_date, 0, 'L', false);
            $pdf->SetXY(55,  $y);    $pdf->MultiCell(50, 48, '-', 0, 'L', false);
            $pdf->SetXY(56,  $y);    $pdf->MultiCell(50, 48, $ext->to_time, 0, 'L', false);
            $pdf->SetXY(82,  $y);    $pdf->MultiCell(50, 48, $ext->payment_status, 0, 'L', false);
            $pdf->SetXY(108.5,$y);   $pdf->MultiCell(50, 48, $ext->payment_type, 0, 'L', false);
            $pdf->SetXY(138.5,$y);   $pdf->MultiCell(50, 48, $ext->c_date, 0, 'L', false);
            $pdf->SetXY(168.5,$y);   $pdf->MultiCell(50, 48, 'RM ' . number_format($ext->total, 2), 0, 'L', false);

            $pdf->SetXY($x, $y);
            $pdf->MultiCell(50, 5, ' ', 0, 'L', false);
            $pdf->Ln(0);
        }

        // Totals & lower section
        $pdf->SetXY(168.5, 198); $pdf->Write(0, 'RM ' . number_format($totalExt, 2));

        $pdf->SetXY(35, 205.75);
        $pdf->MultiCell(65, 3, $fullname, 0, 'L');

        // Return date/time (final)
        $returnFinal = $booking->return_date_final ?? $booking->return_date;
        $pdf->SetXY(140, 209); $pdf->Write(0, Carbon::parse($returnFinal)->format('d/m/Y'));
        $pdf->SetXY(160, 209); $pdf->Write(0, '@');
        $pdf->SetXY(165, 209); $pdf->Write(0, Carbon::parse($returnFinal)->format('H:i'));

        // Others
        if (!empty($booking->other_details)) {
            $pdf->SetXY(15, 220);  $pdf->Write(0, 'Others');
            $pdf->SetFont('Helvetica', '', 6);
            $pdf->SetXY(61.5, 220); $pdf->Write(0, (string) $booking->other_details);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY(140, 220);  $pdf->Write(0, (string) ($booking->other_details_payment_type ?? ''));
            $pdf->SetXY(170, 220);  $pdf->Write(0, 'RM ' . number_format((float) ($booking->other_details_price ?? 0), 2));
            $pdf->SetXY(170, 230);  $pdf->Write(0, 'RM ' . number_format((float) ($booking->other_details_price ?? 0), 2));
        }

        // Est total line
        $pdf->SetXY(170, 240);
        $pdf->Write(0, 'RM ' . number_format((float) ($booking->est_total ?? 0), 2));

        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetXY(61.5, 245);
        $pdf->Write(0, $extends->count() . ' extend(s) recorded');

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(170, 245);
        $pdf->Write(0, 'RM ' . number_format($totalExt, 2));

        // Damage charges
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetXY(61.5, 250);
        $pdf->Write(0, (string) ($booking->damage_charges_details ?? ''));
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(140, 250);
        $pdf->Write(0, (string) ($booking->damage_charges_payment_type ?? ''));
        $pdf->SetXY(170, 250);
        $pdf->Write(0, 'RM ' . number_format((float) ($booking->damage_charges ?? 0), 2));

        // Additional = missing + additional_cost (with label rules)
        $missing  = (float) ($booking->missing_items_charges ?? 0);
        $addCost  = (float) ($booking->additional_cost ?? 0);
        $label    = '';
        if ($missing != 0 && $addCost != 0)      $label = 'missing item & additional cost';
        elseif ($missing != 0 && $addCost == 0)  $label = 'missing item';
        elseif ($missing == 0 && $addCost != 0)  $label = 'additional cost';
        $combined = $missing + $addCost;

        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetXY(61.5, 255); $pdf->Write(0, $label);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(140, 255); $pdf->Write(0, (string) ($booking->additional_cost_payment_type ?? ''));
        $pdf->SetXY(170, 255); $pdf->Write(0, 'RM ' . number_format($combined, 2));

        // Customer total
        $totalCustomer = (float) ($booking->est_total ?? 0)
                       + $totalExt
                       + (float) ($booking->damage_charges ?? 0)
                       + (float) ($booking->missing_items_charges ?? 0)
                       + $combined;

        $pdf->SetXY(170, 260);
        $pdf->Write(0, 'RM ' . number_format($totalCustomer, 2));

        // Remarks + footer names
        $pdf->SetXY(39, 265); $pdf->Write(0, (string) ($booking->remark ?? ''));
        $pdf->SetXY(10, 280); $pdf->Write(0, (string) ($cl->car_in_checkby ?? ''));
        $pdf->SetXY(99, 277);
        $pdf->MultiCell(60, 3,
            is_null($cl->car_in_return_person_nric_no ?? null) ? $fullname : (string) ($cl->car_in_return_person_name ?? ''),
            0, 'L'
        );

        // Stream
        return response()->streamDownload(function () use ($pdf, $booking) {
            $pdf->Output();
        }, 'ReturnReceipt_' . ($booking->agreement_no ?? $booking->id) . '.pdf');
    }
}
