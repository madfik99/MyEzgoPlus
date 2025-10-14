<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BookingTrans;
use App\Models\Checklist;
use App\Models\UploadData;
use App\Models\Sale;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Carbon\Carbon;
use App\Models\CDWLog;
use App\Models\SaleLog;
use App\Models\JournalLog;
use Google\Service\Analytics\Upload;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;


class PickupController extends Controller
{
    public function show($booking_id)
    {
        $booking = BookingTrans::with(['customer', 'vehicle.class'])->findOrFail($booking_id);

        if (in_array($booking->available, ['Out', 'Extend'])) {
            return redirect()
                ->route('reservation.view', $booking_id)
                ->with('error', 'This vehicle is currently not available for pickup.');
        }

        $className = strtolower($booking->vehicle->class->class_name ?? '');
        $layoutMap = [
            'myvi' => 'hatchback', 'axia' => 'hatchback', 'yaris' => 'hatchback', 'mazda 2' => 'hatchback',
            'bezza' => 'sedan', 'saga' => 'sedan',
            'alza' => 'mpv', 'exora' => 'mpv', 'freed' => 'mpv', 'serena' => 'mpv',
            'xpender' => 'suv',
            'starex' => 'van', 'hiace' => 'van',
        ];

        $layoutType = 'sedan';
        foreach ($layoutMap as $keyword => $type) {
            if (str_contains($className, $keyword)) {
                $layoutType = $type;
                break;
            }
        }

        return view('reservation.pickup_vehicle', compact('booking', 'layoutType'));
    }




public function updatePickup(Request $request, $booking_id)
{
    $booking = BookingTrans::with(['vehicle.class.priceClass', 'customer'])->findOrFail($booking_id);

    $cdwlog = CDWLog::where('booking_trans_id', $booking_id)->first();

    if (in_array($booking->available, ['Out', 'Extend'])) {
        return back()->with('error', 'Vehicle currently unavailable for pickup.');
    }

    // Decode hidden JSON arrays from the modal uploader
    $request->merge([
        'damageParts'   => json_decode($request->input('damageParts', '[]'), true),
        'damageRemarks' => json_decode($request->input('damageRemarks', '[]'), true),
    ]);

    $validated = $request->validate([
        'payment_type'        => 'required|string',
        'payment_balance'     => 'nullable|numeric|min:0',

        'fuel_level'          => 'required|integer|min:0|max:6',
        'mileage'             => 'required|integer',
        'car_seat_condition'  => 'required|string',
        'cleanliness'         => 'required|string',
        'hidden_datas'        => 'nullable|string',

        // Interior checklist (optional flags)
        'start_engine'        => 'nullable|string',
        'engine_condition'    => 'nullable|string',
        'test_gear'           => 'nullable|string',
        'no_alarm'            => 'nullable|string',
        'air_conditioner'     => 'nullable|string',
        'radio'               => 'nullable|string',
        'wiper'               => 'nullable|string',
        'window_condition'    => 'nullable|string',
        'power_window'        => 'nullable|string',
        'perfume'             => 'nullable|string',
        'carpet'              => 'nullable|string',
        'sticker_p'           => 'nullable|string',
        'Jack'                => 'nullable|string',
        'Tools'               => 'nullable|string',
        'Signage'             => 'nullable|string',
        'Tyre_Spare'          => 'nullable|string',
        'Child_Seat'          => 'nullable|string',
        'Lamp'                => 'nullable|string',
        'Tyres_Condition'     => 'nullable|string',

        // Image uploads
        'interior0'           => 'required|image', 'interior1' => 'nullable|image',
        'interior2'           => 'required|image', 'interior3' => 'nullable|image',
        'interior4'           => 'nullable|image', 'front_left' => 'required|image',
        'rear'                => 'required|image', 'front_right' => 'required|image',
        'front_with_customer' => 'required|image', 'rear_left' => 'nullable|image',
        'rear_right'          => 'nullable|image', 'front' => 'nullable|image',

        // Modal damage
        'damagePhotos'        => 'nullable|array|max:50',
        'damagePhotos.*'      => 'image|mimes:jpeg,png,jpg|max:5120',
        'damageParts'         => 'nullable|array',
        'damageParts.*'       => 'nullable|string|max:255',
        'damageRemarks'       => 'nullable|array',
        'damageRemarks.*'     => 'nullable|string|max:255',

        // NEW: signature
        'renter_ack'          => 'required|in:Y,yes,on,1',
        'signature_data'      => 'required|string',
    ], [
        'renter_ack.in'           => 'Please confirm the renter acknowledgement.',
        'signature_data.required' => 'Please provide a signature.',
    ]);

    // Mark booking out & store payment type
    $booking->update([
        'payment_type'   => $validated['payment_type'],
        'payment_status' => 'FullRental',
        'balance'        => $booking->balance + $validated['payment_balance'] + $cdwlog->amount,
        'available'      => 'Out',
    ]);

    $cdwlog->update([
        'status' => 'Paid',
    ]);

    // Sale Parts
    $pickupDate = $booking->pickup_date ?? Carbon::now()->format('Y-m-d');
    $returnDate = $booking->return_date ?? Carbon::now()->format('Y-m-d');
    $totalDays  = Carbon::parse($returnDate)->diffInDays(Carbon::parse($pickupDate));

    $estTotal   = (float) ($booking->est_total ?? 0);
    $refundDep  = (float) ($booking->refund_dep ?? 0);
    $saleAmount = max(0, round($estTotal - $refundDep, 2));

    Sale::updateOrCreate(
        [
            'booking_trans_id' => $booking_id,
            'title'            => 'Pickup',
            'type'             => 'Sale'
        ],
        [
            'vehicle_id'     => $booking->vehicle->id,
            'total_day'      => $totalDays,
            'total_sale'     => $saleAmount,
            'payment_status' => 'Paid',
            'payment_type'   => $booking->payment_type,
            'image'          => $receiptName ?? null,
            'pickup_date'    => $pickupDate,
            'return_date'    => $returnDate,
            'staff_id'       => auth()->id(),
            'created'        => now(),
        ]
    );
    // END Sale Parts
    
    // ===================== BEGIN: ADD-ONLY SaleLog entries =====================
    try {
        $saleRow = Sale::where('booking_trans_id', $booking_id)
            ->where('title', 'Pickup')
            ->where('type', 'Sale')
            ->latest('created')
            ->first();

        if ($saleRow) {
            $saleId = $saleRow->id;

            // ✅ Insert into journal_log (Eloquent instead of raw SQL)
            JournalLog::create([
                'sale_id' => $saleId,
                'cid'     => session('cid') ?? auth()->id(),
                'created' => now(),
            ]);

            // Build start/end datetimes robustly (avoid double time concatenation)
            $startAt = Carbon::parse($booking->pickup_date ?? $pickupDate);
            $endAt   = Carbon::parse($booking->return_date ?? $returnDate);

            if (!empty($booking->pickup_time) && $startAt->format('H:i:s') === '00:00:00') {
                $startAt->setTimeFromTimeString($booking->pickup_time);
            }
            if (!empty($booking->return_time) && $endAt->format('H:i:s') === '00:00:00') {
                $endAt->setTimeFromTimeString($booking->return_time);
            }
            if ($endAt->lessThan($startAt)) {
                $endAt = (clone $startAt);
            }

            // Helpers to map week and month columns
            $weekOf = function (Carbon $dt) {
                $d = (int) $dt->day;
                if ($d <= 7)  return 'week1';
                if ($d <= 14) return 'week2';
                if ($d <= 21) return 'week3';
                if ($d <= 28) return 'week4';
                return 'week5';
            };
            $monthCol = function (Carbon $dt) {
                $map = [
                    1=>'jan', 2=>'feb', 3=>'march', 4=>'apr', 5=>'may', 6=>'june',
                    7=>'july', 8=>'aug', 9=>'sept', 10=>'oct', 11=>'nov', 12=>'dis',
                ];
                return $map[(int)$dt->month] ?? null;
            };

            // ----- Time split: full days + leftover hours
            $hoursTotal = max(0, $startAt->diffInHours($endAt)); // total hours
            $daysFull   = intdiv($hoursTotal, 24);               // 0..N
            $hoursLeft  = $hoursTotal % 24;                      // 0..23
    
            // ----- Get PriceClass via Vehicle -> ClassModel
            $priceClass = optional($booking->vehicle)->class?->priceClass;
            if (!$priceClass) {
                \Log::error('SaleLog: PriceClass not found via Vehicle->Class', [
                    'booking_id' => $booking_id,
                    'vehicle_id' => $booking->vehicle->id ?? null,
                    'class_id'   => $booking->vehicle->class_id ?? null,
                ]);
                return back()->with('error', 'No price class linked to this vehicle class.');
            }

            // Column maps
            $hourCols = [
                1=>'hour',2=>'hour2',3=>'hour3',4=>'hour4',5=>'hour5',6=>'hour6',7=>'hour7',
                8=>'hour8',9=>'hour9',10=>'hour10',11=>'hour11',12=>'halfday',
                13=>'hour13',14=>'hour14',15=>'hour15',16=>'hour16',17=>'hour17',
                18=>'hour18',19=>'hour19',20=>'hour20',21=>'hour21',22=>'hour22',23=>'hour23',
            ];
            $dayCols = [
                1=>'oneday',2=>'twoday',3=>'threeday',4=>'fourday',5=>'fiveday',6=>'sixday',
                7=>'weekly',8=>'eightday',9=>'nineday',10=>'tenday',11=>'elevenday',12=>'twelveday',
                13=>'thirteenday',14=>'fourteenday',15=>'fifteenday',16=>'sixteenday',17=>'seventeenday',
                18=>'eighteenday',19=>'nineteenday',20=>'twentyday',21=>'twentyoneday',22=>'twentytwoday',
                23=>'twentythreeday',24=>'twentyfourday',25=>'twentyfiveday',26=>'twentysixday',
                27=>'twentysevenday',28=>'twentyeightday',29=>'twentynineday',30=>'monthly'
            ];

            // 1) EXACT N-day total from price_class (e.g., weekly for 7), with safe fallbacks
            $dayTotal = 0.00;
            if ($daysFull > 0) {
                if ($daysFull <= 30) {
                    $col = $dayCols[$daysFull] ?? null;
                    if ($col && isset($priceClass->$col) && (float)$priceClass->$col > 0) {
                        $dayTotal = (float)$priceClass->$col;
                    }
                } else {
                    // >30: monthly blocks + remainder
                    $months  = intdiv($daysFull, 30);
                    $rem     = $daysFull % 30;
                    $monthly = (float)($priceClass->monthly ?? 0);
                    $remCol  = $dayCols[$rem] ?? null;
                    $remVal  = $remCol ? (float)($priceClass->$remCol ?? 0) : 0.0;
                    if ($monthly > 0 || $remVal > 0) {
                        $dayTotal = ($months * $monthly) + $remVal;
                    }
                }
                // Fallbacks to avoid zeros
                if ($dayTotal <= 0 && $daysFull === 7 && (float)($priceClass->weekly ?? 0) > 0) {
                    $dayTotal = (float)$priceClass->weekly;
                }
                if ($dayTotal <= 0 && (float)($priceClass->oneday ?? 0) > 0) {
                    $dayTotal = (float)$priceClass->oneday * $daysFull;
                }
                if ($dayTotal <= 0) {
                    \Log::error('SaleLog: Missing N-day tariff in price_class', [
                        'booking_id'=>$booking_id,'daysFull'=>$daysFull,'pc_id'=>$priceClass->id ?? null
                    ]);
                    return back()->with('error','Price class missing N-day tariff.');
                }
            }

            // 2) EXACT hour total from price_class (one row, do NOT subtract from weekly)
            $hourTotal = 0.00;
            if ($hoursLeft > 0) {
                $hcol = $hourCols[$hoursLeft] ?? null;
                if ($hcol && isset($priceClass->$hcol) && (float)$priceClass->$hcol > 0) {
                    $hourTotal = (float)$priceClass->$hcol;
                } else {
                    // Fallback ONLY if specific hour column missing: derive from oneday
                    $oneDay = (float)($priceClass->oneday ?? 0);
                    if ($oneDay > 0) {
                        $hourTotal = round($oneDay * ($hoursLeft / 24.0), 2);
                    } else {
                        \Log::error('SaleLog: Missing hour tariff in price_class', [
                            'booking_id'=>$booking_id,'hoursLeft'=>$hoursLeft,'pc_id'=>$priceClass->id ?? null
                        ]);
                        return back()->with('error','Price class missing hour tariff.');
                    }
                }
            }

            // -------- INSERT in order: firstday -> day rows -> hour row --------

            // firstday marker (only when we have full days)
            if ($daysFull >= 1) {
                $dt = (clone $startAt);
                $w  = $weekOf($dt);
                $m  = $monthCol($dt);

                $payload = [
                    'sale_id'    => $saleId,
                    'daily_sale' => 0,
                    'day'        => 0,
                    'hour'       => 0,
                    'type'       => 'firstday',
                    'year'       => (int)$dt->year,
                    'date'       => $dt->format('Y-m-d H:i:s'),
                    'created'    => now(),
                ];
                $payload[$w] = 0;
                if ($m) $payload[$m] = 0;
                SaleLog::create($payload);
            }

            // day rows: split dayTotal equally across N days (last day holds remainder)
            if ($daysFull > 0) {
                $dt = (clone $startAt);
                $perDay  = ($daysFull > 1) ? round($dayTotal / $daysFull, 2) : round($dayTotal, 2);
                $sumDays = 0.00;

                for ($i = 1; $i <= $daysFull; $i++) {
                    $amt = ($i === $daysFull)
                        ? round($dayTotal - $sumDays, 2)  // exact remainder → sum(days) == dayTotal
                        : $perDay;

                    $w = $weekOf($dt);
                    $m = $monthCol($dt);

                    $payload = [
                        'sale_id'    => $saleId,
                        'daily_sale' => $amt,
                        'day'        => $i,
                        'hour'       => 0,
                        'type'       => 'day',
                        'year'       => (int)$dt->year,
                        'date'       => $dt->format('Y-m-d H:i:s'),
                        'created'    => now(),
                    ];
                    $payload[$w] = $amt;
                    if ($m) $payload[$m] = $amt;

                    SaleLog::create($payload);

                    $sumDays = round($sumDays + $amt, 2);
                    $dt->addDay();
                }
            } else {
                // no full days; hours (if any) will be on start date
                $dt = (clone $startAt);
            }

            // hour row LAST — add on top of weekly (do NOT subtract)
            if ($hoursLeft > 0 && $hourTotal > 0) {
                $w = $weekOf($dt);              // date: the day AFTER the last full day
                $m = $monthCol($dt);

                $payload = [
                    'sale_id'    => $saleId,
                    'daily_sale' => round($hourTotal, 2),
                    'day'        => 0,
                    'hour'       => $hoursLeft,
                    'type'       => 'hour',
                    'year'       => (int)$dt->year,
                    'date'       => $dt->format('Y-m-d H:i:s'),
                    'created'    => now(),
                ];
                $payload[$w] = round($hourTotal, 2);
                if ($m) $payload[$m] = round($hourTotal, 2);

                SaleLog::create($payload);
            }

            \Log::debug('SaleLog written (price_class exact)', [
                'sale_id'   => $saleId,
                'daysFull'  => $daysFull,
                'hoursLeft' => $hoursLeft,
                'dayTotal'  => $dayTotal,
                'hourTotal' => $hourTotal,
                'pc_id'     => $priceClass->id ?? null,
            ]);
        }
    } catch (\Throwable $e) {
        \Log::warning('SaleLog write failed for booking '.$booking_id.' : '.$e->getMessage());
    }
    // ====================== END: ADD-ONLY SaleLog entries =====================

    // Save FABRIC damage canvas (existing)
    if ($request->filled('hidden_datas')) {
        [, $damageData] = explode(';', $request->input('hidden_datas'));
        [, $damageData] = explode(',', $damageData);
        $decoded = base64_decode($damageData);
        Storage::disk('public')->put("damage_markings/damage_{$booking_id}.jpg", $decoded);
    }

    // NEW: Save SIGNATURE canvas
    $signFilename = null;
    if ($request->filled('signature_data')) {
        $sigDataUrl = $request->input('signature_data'); // data:image/png;base64,....
        if (Str::startsWith($sigDataUrl, 'data:image/')) {
            $sigDataUrl = Str::after($sigDataUrl, 'base64,');
        }
        $sigDataUrl = str_replace(' ', '+', $sigDataUrl);
        $sigBinary = base64_decode($sigDataUrl);

        if ($sigBinary === false) {
            return back()->withErrors(['signature_data' => 'Invalid signature image.'])->withInput();
        }

        $signFilename = "sign-pickup-{$booking_id}.png";
        Storage::disk('public')->put("sign_pickup/{$signFilename}", $sigBinary);
    }

    // Checklist saving (don’t nuke signature unless we actually received one)
    $checklistData = [
        'car_out_start_engine'     => $request->has('start_engine') ? 'Y' : 'X',
        'car_out_engine_condition' => $request->has('engine_condition') ? 'Y' : 'X',
        'car_out_test_gear'        => $request->has('test_gear') ? 'Y' : 'X',
        'car_out_no_alarm'         => $request->has('no_alarm') ? 'Y' : 'X',
        'car_out_air_conditioner'  => $request->has('air_conditioner') ? 'Y' : 'X',
        'car_out_radio'            => $request->has('radio') ? 'Y' : 'X',
        'car_out_wiper'            => $request->has('wiper') ? 'Y' : 'X',
        'car_out_window_condition' => $request->has('window_condition') ? 'Y' : 'X',
        'car_out_power_window'     => $request->has('power_window') ? 'Y' : 'X',
        'car_out_perfume'          => $request->has('perfume') ? 'Y' : 'X',
        'car_out_carpet'           => $request->has('carpet') ? 'Y' : 'X',
        'car_out_sticker_p'        => $request->has('sticker_p') ? 'Y' : 'X',
        'car_out_jack'             => $request->has('Jack') ? 'Y' : 'X',
        'car_out_tools'            => $request->has('Tools') ? 'Y' : 'X',
        'car_out_signage'          => $request->has('Signage') ? 'Y' : 'X',
        'car_out_tyre_spare'       => $request->has('Tyre_Spare') ? 'Y' : 'X',
        'car_out_child_seat'       => $request->has('Child_Seat') ? 'Y' : 'X',
        'car_out_lamp'             => $request->has('Lamp') ? 'Y' : 'X',
        'car_out_tyres_condition'  => $request->has('Tyres_Condition') ? 'Y' : 'X',
        'car_out_fuel_level'       => $validated['fuel_level'],
        'car_out_mileage'          => $validated['mileage'],
        'car_out_seat_condition'   => $validated['car_seat_condition'],
        'car_out_cleanliness'      => $validated['cleanliness'],
        'car_out_remark'           => $request->has('markingRemarks') ? $request->input('markingRemarks') : null,
        'car_out_checkby'          => auth()->user()->name,
        'modified'                 => Carbon::now(),
    ];

    if (isset($decoded)) {
        $checklistData['car_out_image'] = "damage_markings/damage_{$booking_id}.jpg";
    } else {
        $checklistData['car_out_image'] = null;
    }

    // only set sign image if a new one arrived this submit
    if ($signFilename) {
        $checklistData['car_out_sign_image'] = $signFilename;
    }

    Checklist::updateOrCreate(
        ['booking_trans_id' => $booking_id],
        $checklistData
    );

    // === existing uploads logic below remains unchanged ===

    // Get global sequence
    $lastGlobalSequence = UploadData::whereIn('position', ['pickup_damage', 'pickup_interior', 'pickup_exterior'])
        ->orderByDesc('created')->value('sequence') ?? 0;

    $nextSequence = ($lastGlobalSequence % 5) + 1;

    // Delete old interior/exterior in this sequence
    UploadData::whereIn('position', ['pickup_interior', 'pickup_exterior'])
        ->where('sequence', $nextSequence)
        ->delete();

    $manager = new ImageManager(new GdDriver());

    $imageFields = [
        'interior0' => 'pickup_interior', 'interior1' => 'pickup_interior',
        'interior2' => 'pickup_interior', 'interior3' => 'pickup_interior',
        'interior4' => 'pickup_interior', 'front_left' => 'pickup_exterior',
        'rear' => 'pickup_exterior', 'front_right' => 'pickup_exterior',
        'front_with_customer' => 'pickup_exterior', 'rear_left' => 'pickup_exterior',
        'rear_right' => 'pickup_exterior', 'front' => 'pickup_exterior',
    ];

    $noMap = [
        'interior0' => 1, 'interior1' => 2, 'interior2' => 3, 'interior3' => 4, 'interior4' => 5,
        'front_left' => 1, 'front_right' => 2, 'rear_left' => 3, 'rear_right' => 4, 'rear' => 5,
        'front_with_customer' => 6, 'front' => 7,
    ];

    foreach ($imageFields as $field => $position) {
        if ($request->hasFile($field)) {
            $file = $request->file($field);
            $no = $noMap[$field] ?? 1;

            $filename = "{$booking_id}_{$position}_seq{$nextSequence}_no{$no}.jpg";
            $image = $manager->read($file)->scale(width: 800)->toJpeg(75);
            $path = "pickup_images/" . ($position === 'pickup_interior' ? 'interior' : 'exterior') . "/{$filename}";
            Storage::disk('public')->put($path, (string) $image);

            UploadData::updateOrCreate(
                ['position' => $position, 'sequence' => $nextSequence, 'no' => $no],
                [
                    'booking_trans_id' => $booking_id,
                    'customer_id'      => $booking->customer->id,
                    'file_name'        => $path,
                    'file_size'        => Storage::disk('public')->size($path),
                    'file_type'        => 'jpg',
                    'status'           => 'Active',
                    'vehicle_id'       => $booking->vehicle->id,
                    'modified'         => Carbon::now(),
                    'mid'              => auth()->id(),
                    'created'          => Carbon::now(),
                    'cid'              => auth()->id(),
                ]
            );
        }
    }

    // Handle damage photos
    $damagePhotos  = $request->file('damagePhotos', []);
    $damageParts   = $request->input('damageParts', []);
    $damageRemarks = $request->input('damageRemarks', []);

    if (is_array($damagePhotos) && count($damagePhotos) > 0) {
        // Delete existing damage in this sequence
        UploadData::where('position', 'pickup_damage')
            ->where('sequence', $nextSequence)
            ->delete();

        foreach ($damagePhotos as $index => $file) {
            if (!$file) continue;

            $part   = Arr::get($damageParts, $index, 'Unknown');
            $remark = Arr::get($damageRemarks, $index, '');

            $partSafe   = preg_replace('/[^a-z0-9]/i', '_', strtolower($part));
            $remarkSafe = preg_replace('/[^a-z0-9]/i', '_', strtolower($remark));
            $no = $index + 1;

            $filename = "{$booking_id}_pickup_damage_seq{$nextSequence}_no{$no}_{$partSafe}_{$remarkSafe}.jpg";
            $image = $manager->read($file)->scale(width: 800)->toJpeg(75);
            $path = "pickup_images/damage/{$filename}";
            Storage::disk('public')->put($path, (string) $image);

            UploadData::create([
                'booking_trans_id' => $booking_id,
                'position'         => 'pickup_damage',
                'sequence'         => $nextSequence,
                'no'               => $no,
                'customer_id'      => $booking->customer->id,
                'file_name'        => $path,
                'file_size'        => Storage::disk('public')->size($path),
                'file_type'        => 'jpg',
                'status'           => 'Active',
                'vehicle_id'       => $booking->vehicle->id,
                'modified'         => Carbon::now(),
                'mid'              => auth()->id(),
                'created'          => Carbon::now(),
                'cid'              => auth()->id(),
                'label'            => $part,
                'remarks'          => $remark,
            ]);
        }
    }

    // Handle pickup receipt upload
    if ($request->hasFile('pickup_receipt')) {
        $file = $request->file('pickup_receipt');

        $filename = "pickup_receipt-{$booking_id}.jpg";
        $image = $manager->read($file)->scale(width: 800)->toJpeg(75);
        $path = "pickup_images/pickup_receipt/{$filename}";
        Storage::disk('public')->put($path, (string) $image);

        UploadData::create([
            'booking_trans_id' => $booking_id,
            'position'         => 'pickup_receipt',
            'sequence'         => NULL,
            'no'               => NULL,
            'customer_id'      => $booking->customer->id,
            'file_name'        => $filename,
            'file_size'        => Storage::disk('public')->size($path),
            'file_type'        => 'jpg',
            'status'           => 'Active',
            'vehicle_id'       => $booking->vehicle->id,
            'modified'         => Carbon::now(),
            'mid'              => auth()->id(),
            'created'          => Carbon::now(),
            'cid'              => auth()->id(),
            'label'            => NULL,
            'remarks'          => NULL,
        ]);
    }

    return redirect()
        ->route('reservation.view', $booking_id)
        ->with('success', 'Pickup details successfully saved.');
}




public function showDamage($id)
{
    // 1. Get the booking record
    $pickup = BookingTrans::with(['vehicle.class'])->findOrFail($id);

    // 2. Group damage uploads by label
    $damageUploads = UploadData::where('booking_trans_id', $id)
        ->where('position', 'pickup_damage')
        ->get()
        ->groupBy('label');

    // 3. Determine layoutType based on vehicle class name
    $className = strtolower($pickup->vehicle->class->class_name ?? '');
    $layoutMap = [
        'myvi' => 'hatchback', 'axia' => 'hatchback', 'yaris' => 'hatchback', 'mazda 2' => 'hatchback',
        'bezza' => 'sedan', 'saga' => 'sedan',
        'alza' => 'mpv', 'exora' => 'mpv', 'freed' => 'mpv', 'serena' => 'mpv',
        'xpender' => 'suv',
        'starex' => 'van', 'hiace' => 'van',
    ];

    $layoutType = 'sedan'; // default
    foreach ($layoutMap as $keyword => $type) {
        if (str_contains($className, $keyword)) {
            $layoutType = $type;
            break;
        }
    }

    // 4. Pass to view
    return view('reservation.damage-marking', compact('pickup', 'layoutType', 'damageUploads'));
}



}