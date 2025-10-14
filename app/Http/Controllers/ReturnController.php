<?php

namespace App\Http\Controllers;

use App\Models\BookingTrans;
use App\Models\Checklist;
use App\Models\UploadData;
use App\Models\Discount;
use App\Models\Extend;
use App\Models\Sale;
use App\Models\SaleLog;
use App\Models\OutstandingSale;
use App\Models\JournalLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class ReturnController extends Controller
{
    public function show($booking_id)
    {   
        $booking = BookingTrans::with(['customer', 'vehicle'])->findOrFail($booking_id);
        $checklist = Checklist::where('booking_trans_id', $booking_id)->first();
        $uploadData = UploadData::where('booking_trans_id', $booking_id)->first();

        
        return view('reservation.return_vehicle', compact('booking','checklist', 'uploadData'));
    }


    public function updateReturn(Request $request, $booking_id)
    {
        $booking = BookingTrans::with(['vehicle', 'customer'])->findOrFail($booking_id);

        // Decode hidden JSON arrays from modal
        $request->merge([
            'damageParts'   => json_decode($request->input('damageParts', '[]'), true),
            'damageRemarks' => json_decode($request->input('damageRemarks', '[]'), true),
        ]);

        $validated = $request->validate([
            'fuel_level'            => 'required|integer|min:0|max:6',
            'mileage'               => 'required|integer|min:0',
            'car_seat_condition'    => 'required|string|max:100',
            'cleanliness'           => 'required|string|max:100',
            'markingRemarks'        => 'nullable|string|max:2000',

            // data URLs from canvas
            'hidden_datas'          => 'nullable|string',
            'signature_return_data' => 'nullable|string',

            // checklist flags
            'start_engine'      => 'nullable|string',
            'engine_condition'  => 'nullable|string',
            'test_gear'         => 'nullable|string',
            'no_alarm'          => 'nullable|string',
            'air_conditioner'   => 'nullable|string',
            'radio'             => 'nullable|string',
            'wiper'             => 'nullable|string',
            'window_condition'  => 'nullable|string',
            'power_window'      => 'nullable|string',
            'perfume'           => 'nullable|string',
            'carpet'            => 'nullable|string',
            'sticker_p'         => 'nullable|string',

            'jack'              => 'nullable|string',
            'tools'             => 'nullable|string',
            'signage'           => 'nullable|string',
            'tyre_spare'        => 'nullable|string',
            'child_seat'        => 'nullable|string',
            'lamp'              => 'nullable|string',
            'tyres_condition'   => 'nullable|string',

            // uploads
            'interior'          => 'sometimes|array',
            'interior.*'        => 'image|mimes:jpeg,jpg,png|max:8192',
            'exterior'          => 'sometimes|array',
            'exterior.*'        => 'image|mimes:jpeg,jpg,png|max:8192',

            // optional damage photos on return
            'damagePhotos'      => 'nullable|array|max:50',
            'damagePhotos.*'    => 'image|mimes:jpeg,png,jpg|max:5120',
            'damageParts'       => 'nullable|array',
            'damageParts.*'     => 'nullable|string|max:255',
            'damageRemarks'     => 'nullable|array',
            'damageRemarks.*'   => 'nullable|string|max:255',
        ]);

        $manager = new ImageManager(new GdDriver());

        /**
         * Compress + save a dataURL (NO RESIZE) to keep the exact canvas size.
         * $format: 'jpg' or 'png'
         * Returns relative path under public disk.
         */
        $saveDataUrlCompressed = function (?string $dataUrl, string $dir, string $basename, string $format = 'jpg') use ($manager) {
            if (!$dataUrl || !Str::startsWith($dataUrl, 'data:image/')) return null;

            $raw = Str::after($dataUrl, 'base64,');
            $bin = base64_decode(str_replace(' ', '+', $raw));
            if ($bin === false) return null;

            $img = $manager->read($bin); // keep the exact size from the browser (e.g., 300x200)

            $encoded = ($format === 'png') ? $img->toPng() : $img->toJpeg(75);
            $path = "{$dir}/{$basename}.{$format}";
            Storage::disk('public')->put($path, (string) $encoded);

            return $path;
        };

        // checklist mapping
        $interiorChecks = [
            'start_engine'      => 'car_in_start_engine',
            'engine_condition'  => 'car_in_engine_condition',
            'test_gear'         => 'car_in_test_gear',
            'no_alarm'          => 'car_in_no_alarm',
            'air_conditioner'   => 'car_in_air_conditioner',
            'radio'             => 'car_in_radio',
            'wiper'             => 'car_in_wiper',
            'window_condition'  => 'car_in_window_condition',
            'power_window'      => 'car_in_power_window',
            'perfume'           => 'car_in_perfume',
            'carpet'            => 'car_in_carpet',
            'sticker_p'         => 'car_in_sticker_p',
        ];
        $exteriorChecks = [
            'jack'              => 'car_in_jack',
            'tools'             => 'car_in_tools',
            'signage'           => 'car_in_signage',
            'tyre_spare'        => 'car_in_tyre_spare',
            'child_seat'        => 'car_in_child_seat',
            'lamp'              => 'car_in_lamp',
            'tyres_condition'   => 'car_in_tyres_condition',
        ];

        // “no” mapping order
        $interiorNoOrder = [1,2,3,4,5];
        $exteriorNoOrder = [1,3,5,4,2,7,6]; // FL, RL, Rear, RR, FR, Front, Front+Cust

        DB::beginTransaction();

        try {
            // ---------- Checklist upsert (CAR IN) ----------
            $checklist = Checklist::firstOrNew(['booking_trans_id' => $booking->id]);
            
            $checkerName = optional(auth()->user())->name
                        ?? optional(auth()->user())->username
                        ?? optional(auth()->user())->email
                        ?? ('User#'.auth()->id());

            foreach ($interiorChecks as $input => $col) {
                $checklist->{$col} = $request->has($input) ? 'Y' : 'X';
            }
            foreach ($exteriorChecks as $input => $col) {
                $checklist->{$col} = $request->has($input) ? 'Y' : 'X';
            }

            $checklist->car_in_fuel_level     = (int) $validated['fuel_level'];
            $checklist->car_in_mileage        = (int) $validated['mileage'];
            $checklist->car_in_seat_condition = $validated['car_seat_condition'];
            $checklist->car_in_cleanliness    = $validated['cleanliness'];
            $checklist->car_in_remark         = $request->input('markingRemarks');
            $checklist->car_in_checkby        = $checkerName;

            // Damage sketch (JPEG) — keep exact size from the browser (e.g., 300×200)
            if ($request->filled('hidden_datas')) {
                $path = $saveDataUrlCompressed(
                    $request->input('hidden_datas'),
                    'damage_markings',
                    "return_{$booking_id}",
                    'jpg'
                );
                if ($path) {
                    $checklist->car_in_image = $path; // store relative path
                }
            }

            // Signature (PNG) — keep exact size from the browser
            if ($request->filled('signature_return_data')) {
                $sigPath = $saveDataUrlCompressed(
                    $request->input('signature_return_data'),
                    'sign_return',
                    "sign_return_{$booking_id}",
                    'png'
                );
                if ($sigPath) {
                    $checklist->car_in_sign_image = basename($sigPath); // legacy style: filename only
                }
            }

            $checklist->save();

            // ---------- Return images (compressed to width 800 like pickup) ----------
            $lastSeq = UploadData::whereIn('position', ['return_interior', 'return_exterior', 'return_damage'])
                ->orderByDesc('created')
                ->value('sequence') ?? 0;
            $nextSequence = ($lastSeq % 5) + 1;

            // Clean old images in this sequence for this booking
            UploadData::where('booking_trans_id', $booking_id)
                ->whereIn('position', ['return_interior', 'return_exterior', 'return_damage'])
                ->where('sequence', $nextSequence)
                ->delete();

            // INTERIOR
            if ($request->hasFile('interior')) {
                foreach ($request->file('interior') as $idx => $file) {
                    if (!$file) continue;
                    $no = $interiorNoOrder[$idx] ?? ($idx + 1);

                    $filename = "{$booking_id}_return_interior_seq{$nextSequence}_no{$no}.jpg";
                    $image    = $manager->read($file)->scale(width: 800)->toJpeg(75);
                    $path     = "return_images/interior/{$filename}";
                    Storage::disk('public')->put($path, (string) $image);

                    UploadData::create([
                        'booking_trans_id' => $booking_id,
                        'position'         => 'return_interior',
                        'sequence'         => $nextSequence,
                        'no'               => $no,
                        'customer_id'      => optional($booking->customer)->id,
                        'file_name'        => $path,
                        'file_size'        => Storage::disk('public')->size($path),
                        'file_type'        => 'jpg',
                        'status'           => 'Active',
                        'vehicle_id'       => optional($booking->vehicle)->id,
                        'modified'         => Carbon::now(),
                        'mid'              => auth()->id(),
                        'created'          => Carbon::now(),
                        'cid'              => auth()->id(),
                    ]);
                }
            }

            // EXTERIOR
            if ($request->hasFile('exterior')) {
                foreach ($request->file('exterior') as $idx => $file) {
                    if (!$file) continue;
                    $no = $exteriorNoOrder[$idx] ?? ($idx + 1);

                    $filename = "{$booking_id}_return_exterior_seq{$nextSequence}_no{$no}.jpg";
                    $image    = $manager->read($file)->scale(width: 800)->toJpeg(75);
                    $path     = "return_images/exterior/{$filename}";
                    Storage::disk('public')->put($path, (string) $image);

                    UploadData::create([
                        'booking_trans_id' => $booking_id,
                        'position'         => 'return_exterior',
                        'sequence'         => $nextSequence,
                        'no'               => $no,
                        'customer_id'      => optional($booking->customer)->id,
                        'file_name'        => $path,
                        'file_size'        => Storage::disk('public')->size($path),
                        'file_type'        => 'jpg',
                        'status'           => 'Active',
                        'vehicle_id'       => optional($booking->vehicle)->id,
                        'modified'         => Carbon::now(),
                        'mid'              => auth()->id(),
                        'created'          => Carbon::now(),
                        'cid'              => auth()->id(),
                    ]);
                }
            }

            // DAMAGE PHOTOS
            if ($request->hasFile('damagePhotos')) {
                $photos  = $request->file('damagePhotos', []);
                $parts   = $request->input('damageParts', []);
                $remarks = $request->input('damageRemarks', []);

                foreach ($photos as $i => $file) {
                    if (!$file) continue;

                    $part       = Arr::get($parts, $i, 'Unknown');
                    $remark     = Arr::get($remarks, $i, '');
                    $partSafe   = preg_replace('/[^a-z0-9]/i', '_', strtolower($part));
                    $remarkSafe = preg_replace('/[^a-z0-9]/i', '_', strtolower($remark));
                    $no         = $i + 1;

                    $filename = "{$booking_id}_return_damage_seq{$nextSequence}_no{$no}_{$partSafe}_{$remarkSafe}.jpg";
                    $image    = $manager->read($file)->scale(width: 800)->toJpeg(75);
                    $path     = "return_images/damage/{$filename}";
                    Storage::disk('public')->put($path, (string) $image);

                    UploadData::create([
                        'booking_trans_id' => $booking_id,
                        'position'         => 'return_damage',
                        'sequence'         => $nextSequence,
                        'no'               => $no,
                        'customer_id'      => optional($booking->customer)->id,
                        'file_name'        => $path,
                        'file_size'        => Storage::disk('public')->size($path),
                        'file_type'        => 'jpg',
                        'status'           => 'Active',
                        'vehicle_id'       => optional($booking->vehicle)->id,
                        'modified'         => Carbon::now(),
                        'mid'              => auth()->id(),
                        'created'          => Carbon::now(),
                        'cid'              => auth()->id(),
                        'label'            => $part,
                        'remarks'          => $remark,
                    ]);
                }
            }

            // ✅ Only mark vehicle Park if everything above succeeded
            $booking->update(['available' => 'Park']);

            DB::commit();

            return redirect()
                ->route('reservation.view', $booking_id)
                ->with('success', 'Return details successfully saved.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->with('error', 'Failed to save return: '.$e->getMessage())->withInput();
        }
    }

    public function overdue(Request $request, BookingTrans $booking)
    {
        // ========= 1) Resolve baseline datetimes =========
        // Use booking return_* OR the latest extend_to_date (whichever is later) as baseline
        $baseline = $this->mergeDateAndTime($booking->return_date, $booking->return_time);

        $latestExtendTo = Extend::query()
            ->where('booking_trans_id', $booking->id)
            ->orderByDesc('id')
            ->value('extend_to_date');

        if (!empty($latestExtendTo)) {
            $latestTo = Carbon::parse($latestExtendTo);
            if ($latestTo->gt($baseline)) {
                $baseline = $latestTo;
            }
        }

        $returnAtPlanOrig = $baseline;

        // Latest planned return BEFORE this extension (from modal if provided; else baseline)
        if ($request->query('extend_from_date') && $request->query('extend_from_time')) {
            $previousPlannedReturn = $this->mergeDateAndTime(
                $request->query('extend_from_date'),
                $request->query('extend_from_time')
            );
        } else {
            $previousPlannedReturn = $returnAtPlanOrig;
        }

        // --- Respect modal's Extend-TO values (fallback to inputs, then to baseline) ---
        $extendFromDate = $request->input('extend_from_date')
            ?? $request->query('extend_from_date')
            ?? $previousPlannedReturn->format('Y-m-d');
        $extendFromTime = $request->input('extend_from_time')
            ?? $request->query('extend_from_time')
            ?? $previousPlannedReturn->format('H:i');

        $extendToDate = $request->input('extend_to_date')
            ?? $request->query('extend_to_date')
            ?? $request->input('return_date')
            ?? $request->query('return_date')
            ?? $baseline->format('Y-m-d');
        $extendToTime = $request->input('extend_to_time')
            ?? $request->query('extend_to_time')
            ?? $request->input('return_time')
            ?? $request->query('return_time')
            ?? $baseline->format('H:i');

        $pickupAt     = $this->mergeDateAndTime($extendFromDate, $extendFromTime); // shows "last planned return before extension"
        $extendFromAt = $pickupAt;

        $extendToAt   = $this->mergeDateAndTime($extendToDate, $extendToTime);     // <-- now matches modal/baseline
        if ($extendToAt->lt($extendFromAt)) {
            $extendToAt = $extendFromAt->copy(); // clamp to avoid negative window
        }

        // ========= 2) Rates & helpers =========
        $vehicle    = $booking->vehicle;
        $priceClass = optional(optional($vehicle)->class)->priceClass;

        // Page 1 uses ONLY: hour, halfday, oneday (simple rules)
        $ratePerHour = (float) ($priceClass?->hour    ?? 0);
        $rateHalfDay = (float) ($priceClass?->halfday ?? 0);
        $rateOneDay  = (float) ($priceClass?->oneday  ?? 0);

        // Page 1 hour pricing rule:
        //  < 8h  => h * hour
        //  8–12h => halfday
        //  >=13h => halfday + (hour * (h - 12))
        $computeTimeSubtotalPage1 = function (int $hours) use ($ratePerHour, $rateHalfDay) : float {
            if ($hours <= 0) return 0.0;
            if ($hours < 8)  return $hours * $ratePerHour;
            if ($hours <= 12) return $rateHalfDay;
            return $rateHalfDay + ($ratePerHour * ($hours - 12));
        };

        // ========= 3) Overdue GROSS (Page 1-compatible) =========
        $overdueMinutes = $extendFromAt->diffInMinutes($extendToAt);

        // Derive whole days & hours
        $grossDay  = intdiv($overdueMinutes, 1440);
        $grossHour = intdiv($overdueMinutes % 1440, 60);

        // Page 1 quirk: subtract 1 hour if not zero
        if ($grossHour !== 0) {
            $grossHour = max(0, $grossHour - 1);
        }

        // Page 1 day subtotal: day * oneday
        $day_subtotal_page1  = $grossDay * $rateOneDay;

        // Page 1 time subtotal
        $time_subtotal_page1 = $computeTimeSubtotalPage1($grossHour);

        // Combine
        $overdueGross = (float) ($day_subtotal_page1 + $time_subtotal_page1);

        // ========= 4) Discount logic (A = fixed, P = percent, H/D = display only) =========
        $discountCode   = trim((string) $request->input('discount_code', ''));
        $discountModel  = null;
        $discountAmount = 0.0;

        // Start with the extendToAt the modal gave us; H/D may adjust the display only.
        $displayReturnAt = $extendToAt->copy();

        if ($discountCode !== '') {
            $today = now()->toDateString();
            $discountModel = Discount::where('code', $discountCode)
                ->where('start_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                })
                ->first();

            $valueIn = strtoupper((string) ($discountModel->value_in ?? ''));
            $rate    = (float) ($discountModel->rate ?? 0);

            // Optional inference for HR*/DY* forms
            if (!in_array($valueIn, ['A','P','H','D'], true) || $rate <= 0) {
                $up = strtoupper($discountCode);
                if (preg_match('/^(HR|H)(\d{1,3})$/', $up, $m)) { $valueIn = 'H'; $rate = (float)$m[2]; }
                elseif (preg_match('/^(DY|D)(\d{1,3})$/', $up, $m)) { $valueIn = 'D'; $rate = (float)$m[2]; }
            }

            // Money defaults to gross; adjust per type
            $subtotal = $overdueGross;

            if ($discountModel || in_array($valueIn, ['A','P','H','D'], true)) {
                if ($valueIn === 'A') {
                    $discountAmount = min($overdueGross, max(0.0, $rate));
                    $subtotal       = max(0.0, $overdueGross - $discountAmount);

                } elseif ($valueIn === 'P') {
                    $discountAmount = min($overdueGross, max(0.0, $overdueGross * ($rate / 100)));
                    $subtotal       = max(0.0, $overdueGross - $discountAmount);

                } elseif ($valueIn === 'H') {
                    // DISPLAY ONLY: add hours, clamp to 08:00–22:00 like the legacy page
                    $freeMinutes = (int) ($rate * 60);
                    $tentative   = $extendToAt->copy()->addMinutes($freeMinutes);
                    $hhmm = (int) $tentative->format('Hi');
                    if     ($hhmm > 2200) $displayReturnAt = $tentative->copy()->setTime(22, 0);
                    elseif ($hhmm <  800) $displayReturnAt = $extendToAt->copy()->setTime(22, 0);
                    else                   $displayReturnAt = $tentative;

                } elseif ($valueIn === 'D') {
                    // DISPLAY ONLY: add days
                    $freeMinutes     = (int) ($rate * 1440);
                    $displayReturnAt = $extendToAt->copy()->addMinutes($freeMinutes);
                }
            } else {
                $subtotal = $overdueGross;
            }

            // Redeem feedback
            if ($request->isMethod('post') && $request->input('form_action') === 'redeem') {
                $request->validate(['discount_code' => 'nullable|string|max:50']);
                if ($discountModel || $discountAmount > 0 || in_array($valueIn, ['H','D'], true)) {
                    session()->flash('success', 'Discount applied.');
                } else {
                    session()->flash('error', 'The discount code is invalid or expired.');
                }
            }
        } else {
            $subtotal = $overdueGross;
            if ($request->isMethod('post') && $request->input('form_action') === 'redeem') {
                $request->validate(['discount_code' => 'nullable|string|max:50']);
                session()->flash('error', 'Please enter a discount code.');
            }
        }

        // ========= 5) Header / flags (use effective display return) =========
        $activeReturnAt     = $displayReturnAt;
        $baseDiff           = $pickupAt->diff($activeReturnAt);
        $baseRateText       = sprintf(
            '%d Day %d Hours',
            $baseDiff->d + ($baseDiff->m * 30) + ($baseDiff->y * 365),
            $baseDiff->h
        );
        $pickupDateTimeText = $pickupAt->format('d/m/Y - H:i');
        $returnDateTimeText = $activeReturnAt->format('d/m/Y - H:i');

        $now    = \Carbon\Carbon::now();
        $exceed = $now->greaterThan($activeReturnAt);
        $extend = false;
        $note   = $exceed ? 'Return time already exceeded.' : ($extend ? 'Return time close; consider extend.' : '');

        // Final totals (A/P modify money; H/D do not)
        $estTotal = $subtotal;

        // ========= 6) Proceed path =========
        if ($request->isMethod('post') && $request->input('form_action') === 'proceed') {
            $validated = $request->validate([
                'payment'         => 'required|numeric|min:0',
                'payment_status'  => 'required|string|in:Paid,Collect',
                'payment_type'    => 'required|string|in:Collect,Cash,Online,Cheque,QRPay',
                'payment_receipt' => 'required|image|mimes:jpeg,jpg,png|max:8192',
                'discount_code'   => 'nullable|string|max:50',
                'pickup_date'     => 'nullable|date',
                'pickup_time'     => 'nullable|string',
                'return_date'     => 'nullable|date',
                'return_time'     => 'nullable|string',
            ]);

            // Reapply money-affecting discounts (A/P only) using Page 1 gross
            $proceedCode = trim((string) ($validated['discount_code'] ?? ''));
            if ($proceedCode !== '') {
                $today = now()->toDateString();
                $model = Discount::where('code', $proceedCode)
                    ->where('start_date', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                    })
                    ->first();

                $pType = strtoupper((string) ($model->value_in ?? ''));
                $pRate = (float) ($model->rate ?? 0);
                if (!in_array($pType, ['A','P','H','D'], true) || $pRate <= 0) {
                    $up = strtoupper($proceedCode);
                    if (preg_match('/^(HR|H)(\d{1,3})$/', $up, $m)) { $pType = 'H'; $pRate = (float)$m[2]; }
                    elseif (preg_match('/^(DY|D)(\d{1,3})$/', $up, $m)) { $pType = 'D'; $pRate = (float)$m[2]; }
                }

                $estTotal = $overdueGross; // start from Page 1 gross
                if ($model || in_array($pType, ['A','P','H','D'], true)) {
                    if ($pType === 'A') {
                        $disc     = min($overdueGross, max(0.0, $pRate));
                        $estTotal = max(0.0, $overdueGross - $disc);
                    } elseif ($pType === 'P') {
                        $disc     = min($overdueGross, max(0.0, $overdueGross * ($pRate / 100)));
                        $estTotal = max(0.0, $overdueGross - $disc);
                    } else {
                        // H/D: display-only; no change to money
                        $estTotal = $overdueGross;
                    }
                }
            }

            \DB::beginTransaction();
            try {
                $manager  = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                $file     = $request->file('payment_receipt');
                $filename = "overdue_receipt-{$booking->id}.jpg";
                $image    = $manager->read($file)->scale(width: 800)->toJpeg(75);
                $path     = "overdue_images/receipts/{$filename}";
                \Storage::disk('public')->put($path, (string)$image);

                UploadData::create([
                    'booking_trans_id' => $booking->id,
                    'position'         => 'overdue_receipt',
                    'sequence'         => null,
                    'no'               => null,
                    'customer_id'      => optional($booking->customer)->id,
                    'file_name'        => $path,
                    'file_size'        => \Storage::disk('public')->size($path),
                    'file_type'        => 'jpg',
                    'status'           => 'Active',
                    'vehicle_id'       => optional($booking->vehicle)->id,
                    'modified'         => now(),
                    'mid'              => auth()->id(),
                    'created'          => now(),
                    'cid'              => auth()->id(),
                    'label'            => 'Overdue Payment Receipt',
                    'remarks'          => $validated['payment_status'].' / '.$validated['payment_type'],
                ]);

                \DB::commit();

                return redirect()
                    ->route('return.vehicle', $booking->id)
                    ->with('success', 'Overdue payment recorded. Continue with return.');
            } catch (\Throwable $e) {
                \DB::rollBack();
                report($e);
                return back()->with('error', 'Failed to record overdue payment: '.$e->getMessage())->withInput();
            }
        }

        // ========= 7) Render page =========
        return view('reservation.overdue_return', [
            'booking'            => $booking,

            'overdueType'        => $request->query('overdue_extend') ? 'extend' : 'overdue',

            'baseRateText'       => $baseRateText,
            'pickupDateTimeText' => $pickupDateTimeText,
            'returnDateTimeText' => $returnDateTimeText,

            'note'               => $note,
            'exceed'             => $exceed,
            'extend'             => $extend,

            'subtotal'           => round($subtotal, 2),
            'estTotal'           => round($estTotal, 2),
            'discountCode'       => $discountCode,
            'discountAmount'     => round($discountAmount, 2),

            // carry the RESOLVED values (these now match the modal selections)
            'pickupDate'         => $pickupAt->format('Y-m-d'),
            'pickupTime'         => $pickupAt->format('H:i'),
            'returnDateOriginal' => $returnAtPlanOrig->format('Y-m-d'),
            'returnDate'         => $displayReturnAt->format('Y-m-d'),
            'returnTime'         => $displayReturnAt->format('H:i'),
        ]);
    }


    private function mergeDateAndTime($date, $time): \Carbon\Carbon
    {
        $date = (string) ($date ?? '');
        $time = (string) ($time ?? '');

        // If $date already contains a time (or ISO 'T' separator), just parse it.
        $dateHasTime = (bool) preg_match('/[ T]\d{2}:\d{2}/', $date);
        if ($date !== '' && $dateHasTime) {
            return \Carbon\Carbon::parse($date);
        }

        // If we have a date-only + a time, merge them.
        if ($date !== '' && $time !== '') {
            return \Carbon\Carbon::parse(trim($date.' '.$time));
        }

        // If we have only date, set to midnight.
        if ($date !== '') {
            return \Carbon\Carbon::parse($date.' 00:00');
        }

        // Fallback: now()
        return \Carbon\Carbon::now();
    }

    public function extend(Request $request, BookingTrans $booking = null)
    {
        // Resolve booking either from route-model binding or ?booking_id=
        if (!$booking) {
            $bookingId = $request->query('booking_id');
            $booking   = BookingTrans::findOrFail($bookingId);
        }

        // We need rates
        $booking->loadMissing(['vehicle.class.priceClass']);

        // ========= 1) Resolve baseline datetimes =========
        // Start from booking's planned return (NOT auto-extend)
        $baseline = $this->mergeDateAndTime($booking->return_date, $booking->return_time);

        // Consider ONLY latest **manual** extend end-time
        $latestManualTo = Extend::query()
            ->where('booking_trans_id', $booking->id)
            ->where('extend_type', 'manual')
            ->max('extend_to_date'); // "Y-m-d H:i:s" or null

        if (!empty($latestManualTo)) {
            $latestTo = \Carbon\Carbon::parse($latestManualTo);
            if ($latestTo->gt($baseline)) {
                $baseline = $latestTo;
            }
        }

        $returnAtPlanOrig = $baseline;

        // Latest planned return BEFORE this extension (from modal if provided; else baseline)
        if ($request->query('extend_from_date') && $request->query('extend_from_time')) {
            $previousPlannedReturn = $this->mergeDateAndTime(
                $request->query('extend_from_date'),
                $request->query('extend_from_time')
            );
        } else {
            $previousPlannedReturn = $returnAtPlanOrig;
        }

        // Respect modal's Extend-TO values (fallbacks)
        $extendFromDate = $request->input('extend_from_date')
            ?? $request->query('extend_from_date')
            ?? $previousPlannedReturn->format('Y-m-d');

        $extendFromTime = $request->input('extend_from_time')
            ?? $request->query('extend_from_time')
            ?? $previousPlannedReturn->format('H:i');

        $extendToDate = $request->input('extend_to_date')
            ?? $request->query('extend_to_date')
            ?? $request->input('return_date')
            ?? $request->query('return_date')
            ?? $baseline->format('Y-m-d');

        $extendToTime = $request->input('extend_to_time')
            ?? $request->query('extend_to_time')
            ?? $request->input('return_time')
            ?? $request->query('return_time')
            ?? $baseline->format('H:i');

        $pickupAt     = $this->mergeDateAndTime($extendFromDate, $extendFromTime); // previous plan
        $extendFromAt = $pickupAt;
        $extendToAt   = $this->mergeDateAndTime($extendToDate, $extendToTime);

        // ✅ IMPORTANT: never price a 0-length window
        if ($extendToAt->lte($extendFromAt)) {
            // choose your default: addHour() or addDay()
            $extendToAt = $extendFromAt->copy()->addHour();
        }

        // ========= 2) Rates & helpers =========
        $vehicle    = $booking->vehicle;
        $priceClass = optional(optional($vehicle)->class)->priceClass;

        $getRate = function (?object $pc, string $field): float {
            return (float) ($pc?->$field ?? 0);
        };

        $hourlyRates = [
            0  => 0,
            1  => $getRate($priceClass,'hour'),
            2  => $getRate($priceClass,'hour2'),
            3  => $getRate($priceClass,'hour3'),
            4  => $getRate($priceClass,'hour4'),
            5  => $getRate($priceClass,'hour5'),
            6  => $getRate($priceClass,'hour6'),
            7  => $getRate($priceClass,'hour7'),
            8  => $getRate($priceClass,'hour8'),
            9  => $getRate($priceClass,'hour9'),
            10 => $getRate($priceClass,'hour10'),
            11 => $getRate($priceClass,'hour11'),
            12 => $getRate($priceClass,'halfday'),
            13 => $getRate($priceClass,'hour13'),
            14 => $getRate($priceClass,'hour14'),
            15 => $getRate($priceClass,'hour15'),
            16 => $getRate($priceClass,'hour16'),
            17 => $getRate($priceClass,'hour17'),
            18 => $getRate($priceClass,'hour18'),
            19 => $getRate($priceClass,'hour19'),
            20 => $getRate($priceClass,'hour20'),
            21 => $getRate($priceClass,'hour21'),
            22 => $getRate($priceClass,'hour22'),
            23 => $getRate($priceClass,'hour23'),
        ];

        $dailyRates = [
            0  => 0,
            1  => $getRate($priceClass,'oneday'),
            2  => $getRate($priceClass,'twoday'),
            3  => $getRate($priceClass,'threeday'),
            4  => $getRate($priceClass,'fourday'),
            5  => $getRate($priceClass,'fiveday'),
            6  => $getRate($priceClass,'sixday'),
            7  => $getRate($priceClass,'weekly'),
            8  => $getRate($priceClass,'eightday'),
            9  => $getRate($priceClass,'nineday'),
            10 => $getRate($priceClass,'tenday'),
            11 => $getRate($priceClass,'elevenday'),
            12 => $getRate($priceClass,'twelveday'),
            13 => $getRate($priceClass,'thirteenday'),
            14 => $getRate($priceClass,'fourteenday'),
            15 => $getRate($priceClass,'fifteenday'),
            16 => $getRate($priceClass,'sixteenday'),
            17 => $getRate($priceClass,'seventeenday'),
            18 => $getRate($priceClass,'eighteenday'),
            19 => $getRate($priceClass,'nineteenday'),
            20 => $getRate($priceClass,'twentyday'),
            21 => $getRate($priceClass,'twentyoneday'),
            22 => $getRate($priceClass,'twentytwoday'),
            23 => $getRate($priceClass,'twentythreeday'),
            24 => $getRate($priceClass,'twentyfourday'),
            25 => $getRate($priceClass,'twentyfiveday'),
            26 => $getRate($priceClass,'twentysixday'),
            27 => $getRate($priceClass,'twentysevenday'),
            28 => $getRate($priceClass,'twentyeightday'),
            29 => $getRate($priceClass,'twentynineday'),
            30 => $getRate($priceClass,'monthly'),
        ];

        $computeSubtotal = function (int $day, int $hour, float $monthly_subtotal = 0.0) use ($hourlyRates, $dailyRates, $priceClass): float {
            $time_subtotal = (float) ($hourlyRates[$hour] ?? 0);
            if ($day === 0) {
                $time_day_subtotal = $time_subtotal;
            } else {
                $day_subtotal      = (float) ($dailyRates[$day] ?? 0);
                $time_day_subtotal = $time_subtotal + $day_subtotal;

                $nextTier = $dailyRates[$day + 1] ?? null;
                if ($nextTier && $time_day_subtotal >= (float)$nextTier) {
                    $time_day_subtotal = (float)$nextTier;
                }

                if ($day === 30) {
                    $car_rate_extra = (float) ($priceClass?->monthly ?? 0) + (float) ($priceClass?->oneday ?? 0);
                    if ($time_day_subtotal >= $car_rate_extra) {
                        $time_day_subtotal = $car_rate_extra;
                    }
                }
            }
            return (float) ($time_day_subtotal + $monthly_subtotal);
        };

        // ========= 3) Base amount (gross) =========
        $overdueMinutes = $extendFromAt->diffInMinutes($extendToAt);
        $grossDay       = intdiv($overdueMinutes, 1440);
        $grossHour      = intdiv(($overdueMinutes % 1440), 60);

        $grossMonthCount      = 0;
        $grossMonthlySubtotal = 0.0;
        if ($grossDay > 30) {
            $grossMonthCount      = intdiv($grossDay, 30);
            $grossMonthlySubtotal = $grossMonthCount * (float) ($priceClass?->monthly ?? 0);
            $grossDay            -= $grossMonthCount * 30;
        }
        $overdueGross = $computeSubtotal($grossDay, $grossHour, $grossMonthlySubtotal);

        // ========= 4) Discount logic =========
        $discountCode   = trim((string) $request->input('discount_code', ''));
        $discountModel  = null;
        $discountAmount = 0.0;

        // For header; H/D may adjust visually
        $displayReturnAt = $extendToAt->copy();

        if ($discountCode !== '') {
            $today = now()->toDateString();
            $discountModel = \App\Models\Discount::where('code', $discountCode)
                ->where('start_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                })
                ->first();

            $valueIn = strtoupper((string) ($discountModel->value_in ?? ''));
            $rate    = (float) ($discountModel->rate ?? 0);

            // Infer simple HR*/DY* codes when schema not set
            if (!in_array($valueIn, ['A','P','H','D'], true) || $rate <= 0) {
                $up = strtoupper($discountCode);
                if (preg_match('/^(HR|H)(\d{1,3})$/', $up, $m)) { $valueIn = 'H'; $rate = (float)$m[2]; }
                elseif (preg_match('/^(DY|D)(\d{1,3})$/', $up, $m)) { $valueIn = 'D'; $rate = (float)$m[2]; }
            }

            $subtotal = $overdueGross;

            if ($discountModel || in_array($valueIn, ['A','P','H','D'], true)) {
                if ($valueIn === 'A') {
                    $discountAmount = min($overdueGross, max(0.0, $rate));
                    $subtotal       = max(0.0, $overdueGross - $discountAmount);

                } elseif ($valueIn === 'P') {
                    $discountAmount = min($overdueGross, max(0.0, $overdueGross * ($rate / 100)));
                    $subtotal       = max(0.0, $overdueGross - $discountAmount);

                } elseif ($valueIn === 'H') {
                    // DISPLAY ONLY (08:00–22:00 clamp)
                    $freeMinutes = (int) ($rate * 60);
                    $tentative   = $extendToAt->copy()->addMinutes($freeMinutes);
                    $hhmm        = (int) $tentative->format('Hi');
                    if     ($hhmm > 2200) $displayReturnAt = $tentative->copy()->setTime(22, 0);
                    elseif ($hhmm <  800) $displayReturnAt = $extendToAt->copy()->setTime(22, 0);
                    else                   $displayReturnAt = $tentative;

                } elseif ($valueIn === 'D') {
                    // DISPLAY ONLY
                    $freeMinutes     = (int) ($rate * 1440);
                    $displayReturnAt = $extendToAt->copy()->addMinutes($freeMinutes);
                }
            } else {
                $subtotal = $overdueGross;
            }

            if ($request->isMethod('post') && $request->input('form_action') === 'redeem') {
                $request->validate(['discount_code' => 'nullable|string|max:50']);
                if ($discountModel || $discountAmount > 0 || in_array($valueIn, ['H','D'], true)) {
                    session()->flash('success', 'Discount applied.');
                } else {
                    session()->flash('error', 'The discount code is invalid or expired.');
                }
            }
        } else {
            $subtotal = $overdueGross;
            if ($request->isMethod('post') && $request->input('form_action') === 'redeem') {
                $request->validate(['discount_code' => 'nullable|string|max:50']);
                session()->flash('error', 'Please enter a discount code.');
            }
        }

        // ========= 5) Header / flags (use effective display return) =========
        $activeReturnAt     = $displayReturnAt;
        $baseDiff           = $pickupAt->diff($activeReturnAt);
        $baseRateText       = sprintf(
            '%d Day %d Hours',
            $baseDiff->d + ($baseDiff->m * 30) + ($baseDiff->y * 365),
            $baseDiff->h
        );
        $pickupDateTimeText = $pickupAt->format('d/m/Y - H:i');
        $returnDateTimeText = $activeReturnAt->format('d/m/Y - H:i');

        $now    = \Carbon\Carbon::now();
        $exceed = $now->greaterThan($activeReturnAt);
        $extend = true; // mark as extend context
        $note   = $exceed ? 'Return time already exceeded.' : '';

        // Final totals (A/P modify money; H/D do not)
        $estTotal = $subtotal;

        // ========= 6) Render EXTEND page =========
        return view('reservation.extend', [
            'booking'            => $booking,

            'overdueType'        => 'extend',

            'baseRateText'       => $baseRateText,
            'pickupDateTimeText' => $pickupDateTimeText,
            'returnDateTimeText' => $returnDateTimeText,

            'note'               => $note,
            'exceed'             => $exceed,
            'extend'             => $extend,

            'subtotal'           => round($subtotal, 2),
            'estTotal'           => round($estTotal, 2),
            'discountCode'       => $discountCode,
            'discountAmount'     => round($discountAmount, 2),

            // carry the RESOLVED values
            'pickupDate'         => $pickupAt->format('Y-m-d'),
            'pickupTime'         => $pickupAt->format('H:i'),
            'returnDateOriginal' => $returnAtPlanOrig->format('Y-m-d'),
            'returnDate'         => $displayReturnAt->format('Y-m-d'),
            'returnTime'         => $displayReturnAt->format('H:i'),
        ]);
    }




    public function proceedOutstanding(Request $request, BookingTrans $booking)
    {
        // ------------- Validate inputs -------------
        $validated = $request->validate([
            'extend_from_date' => 'required|date',
            'extend_from_time' => 'required|string',   // HH:mm
            'extend_to_date'   => 'required|date',
            'extend_to_time'   => 'required|string',   // HH:mm

            'est_total'        => 'required|numeric|min:0',
            'payment_status'   => 'required|string|in:Paid,Collect',
            'payment_type'     => 'required|string|in:Collect,Cash,Online,Cheque,QRPay',
            'payment_receipt'  => 'required|image|mimes:jpeg,jpg,png|max:8192',

            'discount_code'    => 'nullable|string|max:50',
            'type_of_payment'  => 'nullable|string|max:50',
            'payment'          => 'nullable|numeric|min:0', // (optional capture)
        ]);

        // ------------- Resolve datetimes -------------
        $extendFromAt = $this->mergeDateAndTime($validated['extend_from_date'], $validated['extend_from_time']);
        $extendToAt   = $this->mergeDateAndTime($validated['extend_to_date'],   $validated['extend_to_time']);
        if ($extendToAt->lt($extendFromAt)) {
            $extendToAt = $extendFromAt->copy(); // clamp
        }

        $vehicle       = $booking->vehicle;
        $total         = (float) $validated['est_total'];       // what Page-1 stores in sale.total_sale
        $coupon        = trim((string) ($validated['discount_code'] ?? ''));
        $typeOfPayment = trim((string) ($validated['type_of_payment'] ?? $validated['payment_type']));
        $paidAmount    = (float) ($validated['payment'] ?? 0);

        // "timeorigin" in legacy = raw hour difference (no -1)
        $hoursOrigin     = $extendFromAt->diffInHours($extendToAt);  // integer
        $hoursOriginText = $hoursOrigin . ' hour(s)';

        \DB::beginTransaction();
        try {
            // ------------- 1) Upload receipt (public disk) -------------
            $manager  = new ImageManager(new GdDriver());
            $file     = $request->file('payment_receipt');
            $filename = "outstanding_receipt-{$booking->id}.jpg";
            $image    = $manager->read($file)->scale(width: 800)->toJpeg(75);
            $path     = "outstanding_images/receipts/{$filename}";
            Storage::disk('public')->put($path, (string) $image);

            // UploadData (position='outstanding_receipt')
            UploadData::create([
                'booking_trans_id' => $booking->id,
                'position'         => 'outstanding_receipt',
                'sequence'         => null,
                'no'               => null,
                'customer_id'      => optional($booking->customer)->id,
                'file_name'        => $path,
                'file_size'        => Storage::disk('public')->size($path),
                'file_type'        => 'jpg',
                'status'           => 'Active',
                'vehicle_id'       => optional($vehicle)->id,
                'modified'         => now(),
                'mid'              => auth()->id(),
                'created'          => now(),
                'cid'              => auth()->id(),
                'label'            => 'Outstanding Payment Receipt',
                'remarks'          => $validated['payment_status'].' / '.$validated['payment_type'],
            ]);

            // ------------- 2) Insert into sale -------------
            // Legacy style: title='Outstanding Extend', type='Sale'
            $sale = Sale::create([
                'title'            => 'Outstanding Extend',
                'type'             => 'Sale',
                'booking_trans_id' => $booking->id,
                'vehicle_id'       => optional($vehicle)->id,
                'total_day'        => 0,
                'total_sale'       => $total,
                'payment_type'     => $validated['payment_type'],
                'payment_status'   => $validated['payment_status'],
                'image'            => $filename,                              // filename only (legacy)
                'pickup_date'      => $extendFromAt,                          // model casts to datetime
                'return_date'      => $extendToAt,
                'staff_id'         => auth()->id(),
                'created'          => now(),
            ]);

            // ------------- 3) Insert journal_log -------------
            // Your SQL for journal_log has only: id, sale_id, title, cid, created
            JournalLog::create([
                'sale_id' => $sale->id,
                'title'   => 'Outstanding Extend',
                'cid'     => auth()->id(),
                'created' => now(),
            ]);

            // ------------- 4) Insert sale_log (legacy style) -------------
            $saleLogId = null;
            $hourlog   = $extendFromAt->diffInHours($extendToAt); // raw hour difference

            // month mapping used by your legacy (jan..dis)
            $monthMap = [
                'Jan'=>'jan','Feb'=>'feb','Mar'=>'march','Apr'=>'apr','May'=>'may',
                'Jun'=>'june','Jul'=>'july','Aug'=>'aug','Sep'=>'sept','Oct'=>'oct','Nov'=>'nov','Dec'=>'dis'
            ];
            $monthKey = $monthMap[$extendToAt->format('M')] ?? 'jan';

            // week bucket in month (week1..week5)
            $d = (int)$extendToAt->format('d');
            $weekKey = $d <= 7 ? 'week1' : ($d <= 14 ? 'week2' : ($d <= 21 ? 'week3' : ($d <= 28 ? 'week4' : 'week5')));

            if (strtolower($validated['payment_status']) !== 'collect') {
                // Single legacy-style row at the return datetime
                $payload = [
                    'sale_id'    => $sale->id,
                    'daily_sale' => $total,
                    'day'        => 0,
                    'hour'       => (string) $hourlog,
                    'type'       => 'hour (outstanding)',
                    'year'       => (int)$extendToAt->format('Y'),
                    'date'       => $extendToAt,
                    'created'    => now(),
                    // dynamic week & month columns with same value
                    $weekKey     => $total,
                    $monthKey    => $total,
                ];
                $saleLog = SaleLog::create($payload);
                $saleLogId = $saleLog->id;
            }

            // ------------- 5) Update booking_trans (outstanding_* fields) -------------
            $booking->update([
                'outstanding_extend'                  => $hoursOriginText,
                'outstanding_extend_type_of_payment'  => $typeOfPayment,
                'outstanding_extend_cost'             => $total,
                // add other columns if you have them (e.g., outstanding_paid_amount => $paidAmount)
            ]);

            // ------------- 6) outstanding_sale record -------------
            OutstandingSale::create([
                'booking_trans_id' => $booking->id,
                'sale_id'          => $sale->id,
                'sale_log_id'      => $saleLogId,
                'coupon'           => $coupon,
                'sale_before'      => $total,
                'sale_after'       => $total,
                'reason_modified'  => '',
                'mid'              => auth()->id(),
                'modified'         => now(),
                'cid'              => auth()->id(),
                'created'          => now(),
            ]);

            \DB::commit();

            return redirect()
                ->route('return.vehicle', $booking->id)
                ->with('success', 'Outstanding overdue payment recorded. Continue with return.');

        } catch (\Throwable $e) {
            \DB::rollBack();
            report($e);
            return back()->with('error', 'Failed to record outstanding overdue payment: '.$e->getMessage())->withInput();
        }
    }

    public function proceedExtend(Request $request, $bookingId)
    {
        // --- Validate the Payment Details form ---
        $data = $request->validate([
            'payment'          => ['required','numeric','min:0'],
            'payment_receipt'  => ['required','image','mimes:jpeg,jpg,png','max:5120'],
            'payment_status'   => ['required','in:Paid,Collect'],
            'payment_type'     => ['required','in:Unpaid,Collect,Cash,Online,Card,QRPay'],

            // hidden “resolved” fields from your Blade
            'pickup_date'      => ['required','date'],
            'pickup_time'      => ['required'],
            'return_date'      => ['required','date'],
            'return_time'      => ['required'],
            'subtotal'         => ['required','numeric','min:0'],
            'est_total'        => ['required','numeric','min:0'],

            'extend_from_date' => ['required','date'],
            'extend_to_date'   => ['required','date'],
            // 👇 allow explicit times if you add them (optional)
            'extend_from_time' => ['nullable','date_format:H:i'],
            'extend_to_time'   => ['nullable','date_format:H:i'],

            'price'            => ['required','numeric','min:0'],
            'total'            => ['required','numeric','min:0'],

            'discount_code'    => ['nullable','string','max:50'],
            'discount_amount'  => ['nullable','numeric','min:0'],
        ]);

        // base entities
        $booking = BookingTrans::query()->where('id', $bookingId)->first();
        abort_unless($booking, 404, 'Booking not found');

        // make sure we can read rates
        $booking->loadMissing(['vehicle.class.priceClass']);
        $priceClass = optional(optional($booking->vehicle)->class)->priceClass;

        $vehicleId = $booking->vehicle_id ?? $request->query('vehicle_id');

        // --- Use REAL times ---
        // Prefer explicit extend_*_time if present, otherwise fall back to pickup_time/return_time
        $fromTime = $data['extend_from_time'] ?? $data['pickup_time'];
        $toTime   = $data['extend_to_time']   ?? $data['return_time'];

        // reconstruct authoritative datetimes with the correct clocks
        $extendFromAt = $this->mergeDateAndTime($data['extend_from_date'], $fromTime);
        $extendToAt   = $this->mergeDateAndTime($data['extend_to_date'],   $toTime);
        if ($extendToAt->lt($extendFromAt)) {
            $extendToAt = $extendFromAt->copy();
        }

        // durations for logging
        $diffMin = $extendFromAt->diffInMinutes($extendToAt);
        $day     = intdiv($diffMin, 60 * 24);
        $hour    = intdiv($diffMin % (60 * 24), 60);

        // monetary values from form
        $subtotal   = (float)$data['subtotal'];
        $estTotal   = (float)$data['est_total'];
        $price      = (float)$data['price']; // legacy “price”
        $total      = (float)$data['total']; // legacy “total”
        $paidAmt    = (float)$data['payment'];
        $payStatus  = $data['payment_status']; // Paid | Collect
        $payType    = $data['payment_type'];   // Unpaid/Collect/Cash/Online/Card/QRPay
        $discCode   = $data['discount_code'] ?? null;
        $discAmt    = (float)($data['discount_amount'] ?? 0);
        $staffId    = auth()->id() ?? session('cid');

        // LIMIT: manual extend < 9
        $manualCount = Extend::query()
            ->where('booking_trans_id', $bookingId)
            ->where('extend_type', 'manual')
            ->count();

        if ($manualCount >= 9) {
            return back()->withInput()->with('error', 'Extend has reached the limit.');
        }

        $pathRel = null;

        DB::beginTransaction();
        try {
            // compute “no_of_extend”
            $noOfExtend = $manualCount + 1;

            // ====== 1) Receipt upload (keep legacy path + filename pattern) ======
            $file   = $request->file('payment_receipt');
            $ext    = strtolower($file->getClientOriginalExtension()); // jpg/jpeg/png
            $fname  = "extend_receipt-{$bookingId}-{$noOfExtend}.{$ext}";
            $dirRel = "assets/img/receipt/extend";
            $pathRel= "{$dirRel}/{$fname}";

            // store under public so it’s web-accessible (like legacy assets/)
            Storage::disk('public')->putFileAs($dirRel, $file, $fname);

            // upload_data insert (legacy columns)
            UploadData::create([
                'position'          => 'extend_receipt',
                'no'                => $noOfExtend,
                'sequence'          => null,
                'booking_trans_id'  => $bookingId,
                'file_name'         => $fname,
                'file_size'         => $file->getSize(),
                'file_type'         => $file->getMimeType(),
                'vehicle_id'        => $vehicleId,
                'modified'          => now(),
                'mid'               => $staffId,
                'created'           => now(),
                'cid'               => $staffId,
            ]);

            // ====== 2) Sale header ======
            $sale  = Sale::create([
                'title'             => 'Extend',
                'type'              => 'Extend',
                'booking_trans_id'  => $bookingId,
                'vehicle_id'        => $vehicleId,
                'total_day'         => $day,
                'total_sale'        => $total,               // legacy
                'payment_status'    => $payStatus,
                'payment_type'      => $payType,
                'image'             => $fname,
                'pickup_date'       => $extendFromAt,
                'return_date'       => $extendToAt,
                'staff_id'          => $staffId,
                'created'           => now(),
            ]);

            // ====== 3) Extend row (manual) ======
            Extend::create([
                'sale_id'           => $sale->id,
                'booking_trans_id'  => $bookingId,
                'vehicle_id'        => $vehicleId,
                'extend_type'       => 'manual',
                'auto_extend_status'=> 'A',
                'extend_from_date'  => $extendFromAt->format('Y-m-d H:i:s'),
                'extend_from_time'  => $extendFromAt->format('H:i'),
                'extend_to_date'    => $extendToAt->format('Y-m-d H:i:s'),
                'extend_to_time'    => $extendToAt->format('H:i'),
                'payment_status'    => $payStatus,
                'payment_type'      => $payType,
                'discount_coupon'   => $discCode,
                'discount_amount'   => $discAmt,
                'price'             => $price + $discAmt,
                'total'             => $total,
                'payment'           => $paidAmt,
                'cid'               => $staffId,
                'c_date'            => now(),
            ]);

            // ====== 4) Update the AUTO extend window to next day (legacy logic) ======
            Extend::query()
                ->where('vehicle_id', $vehicleId)
                ->where('extend_type', 'auto')
                ->update([
                    'extend_from_date'   => $extendToAt->format('Y-m-d H:i:s'),
                    'extend_from_time'   => $extendToAt->format('H:i'),
                    'extend_to_date'     => $extendToAt->copy()->addDay()->format('Y-m-d H:i:s'),
                    'extend_to_time'     => $extendToAt->format('H:i'),
                    'auto_extend_status' => 'Extend',
                    'mid'                => $staffId,
                    'm_date'             => now(),
                ]);

            // ====== 5) Booking flag + persist the NEW planned return (very important) ======
            BookingTrans::query()->where('id', $bookingId)->update([
                'available'   => 'Extend',
                'return_date' => $extendToAt->format('Y-m-d'),
                'return_time' => $extendToAt->format('H:i'),
            ]);

            // ====== 6) Sale log (hour/firstday/day pattern) ======
            $this->insertExtendSaleLogsForExtend(
                $sale->id,
                $extendFromAt,
                $extendToAt,
                (float)$total,
                $extendToAt->format('H:i:s'),
                $priceClass // <<< pass rates so logs use real oneday/hour rates
            );

            // ====== 7) Journal log ======
            JournalLog::create([
                'sale_id'  => $sale->id,
                'mid'      => $staffId,
                'modified' => now(),
                'cid'      => $staffId,
                'created'  => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('reservation.view', $bookingId)
                ->with('success', 'Extend processed successfully. New return time: '.$extendToAt->format('d/m/Y H:i'));

        } catch (\Throwable $e) {
            DB::rollBack();

            // best-effort cleanup if file stored
            if (!empty($pathRel ?? null) && Storage::disk('public')->exists($pathRel)) {
                Storage::disk('public')->delete($pathRel);
            }

            report($e);
            return back()->withInput()->with('error', 'Failed to process extend: '.$e->getMessage());
        }
    }



    /**
         * Insert sale_log rows for EXTEND:
         * - one "hour (extend)" row for leftover hours (if any) at the first day's stamp
         * - one "day (extend)" row for each full day
         * - fills week1..week5 and jan..dis columns
         */
        /**
     * Insert sale_log rows for EXTEND:
     * - if there are leftover hours: 1 "hour (extend)" row on the first day
     * - if there are full days:
     *     - when NO leftover hours: add a zero "firstday (extend)" marker row first
     *     - then 1 "day (extend)" row per day
     * - fills week1..week5 and jan..dis columns
     */
    private function insertExtendSaleLogsForExtend(
        int $saleId,
        Carbon $extendFromAt,
        Carbon $extendToAt,
        float $total,
        string $clockHms = '22:00:00',
        ?object $priceClass = null
    ): void {
        if ($extendToAt->lte($extendFromAt) || $total <= 0) {
            return;
        }

        // Durations
        $minutes = $extendFromAt->diffInMinutes($extendToAt);
        $days    = intdiv($minutes, 1440);
        $hours   = intdiv($minutes % 1440, 60);

        // Month/Week helpers
        $monthMap = [
            'Jan'=>'jan','Feb'=>'feb','Mar'=>'march','Apr'=>'apr','May'=>'may',
            'Jun'=>'june','Jul'=>'july','Aug'=>'aug','Sep'=>'sept','Oct'=>'oct','Nov'=>'nov','Dec'=>'dis'
        ];
        $weekKeyOf = static function (Carbon $d): string {
            $dom = (int)$d->format('j');
            return $dom <= 7 ? 'week1' : ($dom <= 14 ? 'week2' : ($dom <= 21 ? 'week3' : ($dom <= 28 ? 'week4' : 'week5')));
        };
        $clock = Carbon::parse($clockHms)->format('H:i:s');

        // If we have rates, compute EXACT day/hour amounts from priceClass
        $dayAmount  = 0.0;
        $hourAmount = 0.0;

        if ($priceClass) {
            $get = fn(string $f) => (float)($priceClass?->$f ?? 0);

            // Build hourly & daily tables like your extend page
            $hourlyRates = [
                0=>0, 1=>$get('hour'), 2=>$get('hour2'), 3=>$get('hour3'), 4=>$get('hour4'),
                5=>$get('hour5'), 6=>$get('hour6'), 7=>$get('hour7'), 8=>$get('hour8'),
                9=>$get('hour9'), 10=>$get('hour10'), 11=>$get('hour11'), 12=>$get('halfday'),
                13=>$get('hour13'), 14=>$get('hour14'), 15=>$get('hour15'), 16=>$get('hour16'),
                17=>$get('hour17'), 18=>$get('hour18'), 19=>$get('hour19'), 20=>$get('hour20'),
                21=>$get('hour21'), 22=>$get('hour22'), 23=>$get('hour23'),
            ];
            $dailyRates = [
                0=>0, 1=>$get('oneday'), 2=>$get('twoday'), 3=>$get('threeday'), 4=>$get('fourday'),
                5=>$get('fiveday'), 6=>$get('sixday'), 7=>$get('weekly'), 8=>$get('eightday'),
                9=>$get('nineday'), 10=>$get('tenday'), 11=>$get('elevenday'), 12=>$get('twelveday'),
                13=>$get('thirteenday'), 14=>$get('fourteenday'), 15=>$get('fifteenday'),
                16=>$get('sixteenday'), 17=>$get('seventeenday'), 18=>$get('eighteenday'),
                19=>$get('nineteenday'), 20=>$get('twentyday'), 21=>$get('twentyoneday'),
                22=>$get('twentytwoday'), 23=>$get('twentythreeday'), 24=>$get('twentyfourday'),
                25=>$get('twentyfiveday'), 26=>$get('twentysixday'), 27=>$get('twentysevenday'),
                28=>$get('twentyeightday'), 29=>$get('twentynineday'), 30=>$get('monthly'),
            ];

            $dayAmount  = (float)($dailyRates[$days]  ?? 0);
            $hourAmount = (float)($hourlyRates[$hours] ?? 0);

            // Cap by next tier: day + hour should not exceed the next daily tier
            if ($days > 0) {
                $nextTier = $dailyRates[$days + 1] ?? null;
                if ($nextTier && ($dayAmount + $hourAmount) > (float)$nextTier) {
                    $hourAmount = max(0.0, (float)$nextTier - $dayAmount);
                }

                // Special 30-day extra rule (monthly + oneday cap)
                if ($days === 30) {
                    $cap = $get('monthly') + $get('oneday');
                    if (($dayAmount + $hourAmount) > $cap) {
                        $hourAmount = max(0.0, $cap - $dayAmount);
                    }
                }
            }

            // If the UI applied a discount so $total < computed sum, scale both down proportionally
            $sum = $dayAmount + $hourAmount;
            if ($sum > 0 && $total < $sum) {
                $scale = $total / $sum;
                $dayAmount  = round($dayAmount  * $scale, 2);
                $hourAmount = round($hourAmount * $scale, 2);
            }
        } else {
            // Fallback: keep your current proportional split
            $den = ($days * 24) + $hours;
            if ($den <= 0) return;

            $perHour    = $total / $den;
            $hourAmount = $hours > 0 ? $perHour * $hours : 0.0;
            $dayAmount  = $perHour * 24 * $days;
        }

        // ===== Write logs =====

        // 1) Hour remainder row (if any)
        if ($hours > 0 && $hourAmount > 0) {
            $stamp = $extendFromAt->copy()->setTimeFromTimeString($clock);
            $wk    = $weekKeyOf($stamp);
            $monk  = $monthMap[$stamp->format('M')] ?? 'jan';

            SaleLog::create(array_merge([
                'sale_id'    => $saleId,
                'daily_sale' => $hourAmount,
                'day'        => 0,
                'hour'       => (string)$hours,
                'type'       => 'hour (extend)',
                'year'       => (int)$stamp->format('Y'),
                'date'       => $stamp,
                'created'    => now(),
            ], [
                $wk   => $hourAmount,
                $monk => $hourAmount,
            ]));
        }

        // 2) If there are full days AND there was NO hour remainder, insert the zero "firstday (extend)" marker
        if ($days > 0 && $hours === 0) {
            $stamp = $extendFromAt->copy()->setTimeFromTimeString($clock);
            $wk    = $weekKeyOf($stamp);
            $monk  = $monthMap[$stamp->format('M')] ?? 'jan';

            SaleLog::create(array_merge([
                'sale_id'    => $saleId,
                'daily_sale' => 0,
                'day'        => 0,
                'hour'       => 0,
                'type'       => 'firstday (extend)',
                'year'       => (int)$stamp->format('Y'),
                'date'       => $stamp,
                'created'    => now(),
            ], [
                $wk   => 0,
                $monk => 0,
            ]));
        }

        // 3) Per-day rows (split the dayAmount evenly per day)
        if ($days > 0 && $dayAmount > 0) {
            $perDay = $dayAmount / $days;
            $cur    = $extendFromAt->copy();

            for ($i = 1; $i <= $days; $i++) {
                $stamp = $cur->copy()->setTimeFromTimeString($clock);
                $wk    = $weekKeyOf($stamp);
                $monk  = $monthMap[$stamp->format('M')] ?? 'jan';

                SaleLog::create(array_merge([
                    'sale_id'    => $saleId,
                    'daily_sale' => $perDay,
                    'day'        => $i,
                    'type'       => 'day (extend)',
                    'hour'       => 0,
                    'year'       => (int)$stamp->format('Y'),
                    'date'       => $stamp,
                    'created'    => now(),
                ], [
                    $wk   => $perDay,
                    $monk => $perDay,
                ]));

                $cur->addDay();
            }
        }
    }


}