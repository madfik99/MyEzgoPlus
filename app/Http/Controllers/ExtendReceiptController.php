<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;
use App\Models\BookingTrans;
use App\Models\Company;
use App\Models\User;
use App\Models\Extend; // ensure this model exists and points to 'extend' table
use Carbon\Carbon;

class ExtendReceiptController extends Controller
{
    public function print(BookingTrans $booking, Extend $extend)
    {
        // Safety: make sure the extend row belongs to this booking
        if ((int) $extend->booking_trans_id !== (int) $booking->id) {
            abort(404, 'Extend not found for this booking.');
        }

        // Load relations (vehicle, customer, checklist)
        $booking->loadMissing(['vehicle', 'customer', 'checklist']);
        $vehicle  = $booking->vehicle;
        $customer = $booking->customer;
        $cl       = $booking->checklist;

        // Company + username (like your ReturnReceiptController)
        $company  = Company::first();
        $username = optional(User::find($booking->staff_id))->name ?? 'System';

        // Map extend fields similar to your vanilla query
        $agreement_no      = $booking->agreement_no ?? '';
        $fullname          = trim(($customer->firstname ?? '') . ' ' . ($customer->lastname ?? ''));
        $customer_phone_no = $customer->phone_no ?? '';
        $reg_no            = $vehicle->reg_no ?? '';
        $model             = $vehicle->model ?? '';
        $refund_dep        = (float) ($booking->refund_dep ?? 0); // kept here in case you re-enable it

        // Dates/times from the extend row
        $pickup_date = $extend->extend_from_date
            ? Carbon::parse($extend->extend_from_date)->setTimeFromTimeString($extend->extend_from_time ?? '00:00:00')
            : null;

        $return_date = $extend->extend_to_date
            ? Carbon::parse($extend->extend_to_date)->setTimeFromTimeString($extend->extend_to_time ?? '00:00:00')
            : null;

        $extend_total    = (float) ($extend->total ?? 0);
        $extend_payment  = (float) ($extend->payment ?? 0);
        $discount_coupon = $extend->discount_coupon ?? '';
        $discount_amount = (float) ($extend->discount_amount ?? 0);

        // === Calculations (exactly like your vanilla) ===
        $total_cost = $extend_total; // was extend_total + refund_dep in old comments
        $balance    = $extend_total - $extend_payment;

        if ($balance <= 0) {
            $balancenew = '(Paid)';
        } else {
            $balancenew = '(Collect RM ' . $balance . ')';
        }

        $total_paid = $extend_payment; // previously refund_dep + extend_payment (commented in vanilla)

        // === PDF ===
        $tplPath = public_path('assets/document/extendreceipt.pdf');
        if (!file_exists($tplPath)) {
            Log::error('ExtendReceiptController: Template not found', ['path' => $tplPath]);
            abort(500, 'Template not found: assets/document/extendreceipt.pdf');
        }

        $pdf = new Fpdi('P','mm',[203,305]);
        $pdf->AddPage();
        $pdf->setSourceFile($tplPath);
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx);

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0,0,0);

        // Company logo (top & bottom) — keep same coordinates as vanilla
        $companyLogo = ($company && $company->image)
            ? public_path('assets/img/company/' . $company->image)
            : null;

        if ($companyLogo && file_exists($companyLogo)) {
            $pdf->Image($companyLogo, 6.7, 12.6, 16);
            $pdf->Image($companyLogo, 6.7, 159,   16);
        }

        // Company name (top & bottom)
        $pdf->SetXY(25, 15);    $pdf->Write(0, $company->company_name ?? '');
        $pdf->SetXY(25, 161.4); $pdf->Write(0, $company->company_name ?? '');

        // Company address (top & bottom)
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(25, 17.7);  $pdf->MultiCell(70, 3.5, $company->address ?? '', 0);
        $pdf->SetXY(25, 164.1); $pdf->MultiCell(70, 3.5, $company->address ?? '', 0);

        // Company phone (top & bottom)
        $pdf->SetXY(25, 27);    $pdf->Write(0, $company->phone_no ?? '');
        $pdf->SetXY(25, 173.4); $pdf->Write(0, $company->phone_no ?? '');

        // Agreement no (top & bottom)
        $pdf->SetXY(128.3, 13.5);  $pdf->Write(0, $agreement_no);
        $pdf->SetXY(128.3, 159.9); $pdf->Write(0, $agreement_no);

        // Customer name (top & bottom)
        $pdf->SetXY(128.3, 16.1);  $pdf->MultiCell(70, 3.5, $fullname, 0);
        $pdf->SetXY(128.3, 162.5); $pdf->MultiCell(70, 3.5, $fullname, 0);

        // Customer phone (top & bottom)
        $pdf->SetXY(128.3, 28);    $pdf->Write(0, $customer_phone_no);
        $pdf->SetXY(128.3, 174.4); $pdf->Write(0, $customer_phone_no);

        // Reg no (top & bottom)
        $pdf->SetXY(26.5, 32.8);   $pdf->Write(0, $reg_no);
        $pdf->SetXY(26.5, 179.3);  $pdf->Write(0, $reg_no);

        // Model (top & bottom)
        $pdf->SetXY(101, 32.8);    $pdf->Write(0, $model);
        $pdf->SetXY(101, 179.3);   $pdf->Write(0, $model);

        // Pickup date/time (top & bottom)
        if ($pickup_date) {
            $pdf->SetXY(29, 40.05);
            $pdf->Write(0, $pickup_date->format('d/m/Y') . '   @   ' . $pickup_date->format('h:i A'));

            $pdf->SetXY(29, 186.45);
            $pdf->Write(0, $pickup_date->format('d/m/Y') . '   @   ' . $pickup_date->format('h:i A'));
        }

        // Return date/time (top & bottom)
        if ($return_date) {
            $pdf->SetXY(103.5, 40.05);
            $pdf->Write(0, $return_date->format('d/m/Y') . '   @   ' . $return_date->format('h:i A'));

            $pdf->SetXY(103.5, 186.45);
            $pdf->Write(0, $return_date->format('d/m/Y') . '   @   ' . $return_date->format('h:i A'));
        }

        // Payment (top & bottom)
        $pdf->SetXY(170, 40.3);   $pdf->Write(0, 'RM ' . number_format($extend_payment, 2));
        $pdf->SetXY(170, 186.45); $pdf->Write(0, 'RM ' . number_format($extend_payment, 2));

        // Discount coupon & amount (top & bottom)
        $pdf->SetXY(60, 47.4);    $pdf->Write(0, $discount_coupon);
        $pdf->SetXY(60, 193.9);   $pdf->Write(0, $discount_coupon);

        $pdf->SetXY(170, 47.4);   $pdf->Write(0, 'RM ' . number_format($discount_amount, 2));
        $pdf->SetXY(170, 193.9);  $pdf->Write(0, 'RM ' . number_format($discount_amount, 2));

        // Totals & balance (top & bottom)
        $pdf->SetXY(119.7, 114);  $pdf->Write(0, 'Total Cost: RM ' . number_format($total_cost, 2));
        $pdf->SetXY(119.7, 260);  $pdf->Write(0, 'Total Cost: RM ' . number_format($total_cost, 2));

        $pdf->SetFont('Helvetica', 'I', 7);
        $pdf->SetXY(124.5, 116.9); $pdf->Write(0, $balancenew);
        $pdf->SetXY(124.5, 263.2); $pdf->Write(0, $balancenew);

        // Total paid (top & bottom)
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(170, 115.9);  $pdf->Write(0, 'RM ' . number_format($total_paid, 2));
        $pdf->SetXY(170, 262.2);  $pdf->Write(0, 'RM ' . number_format($total_paid, 2));

        // Footer names (top & bottom)
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(5, 128.7);    $pdf->Write(0, $username);
        $pdf->SetXY(5, 275);      $pdf->Write(0, $username);

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(52, 126.7);   $pdf->MultiCell(70, 3.5, $fullname, 0, 'L');
        $pdf->SetXY(52, 273);     $pdf->MultiCell(70, 3.5, $fullname, 0, 'L');

        // Stream like your ReturnReceiptController to avoid blank page issues
        $filename = 'ExtendReceipt_' . ($booking->agreement_no ?? $booking->id) . '_' . $extend->id . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            $pdf->Output(); // direct output to stream
        }, $filename);
    }
}
