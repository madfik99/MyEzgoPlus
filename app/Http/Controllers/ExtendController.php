<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\Extend;        // table: extend
use App\Models\BookingTrans;  // table: booking_trans
use App\Models\Sale;          // table: sale

class ExtendController extends Controller
{
    public function edit(Extend $extend)
    {   

        $bookingId = (int) DB::table('extend')
        ->where('id', $extend->id)
        ->value('booking_trans_id'); 

        // Joins to pull values exactly like the reference
        $row = DB::table('extend')
            ->join('booking_trans', 'booking_trans.id', '=', 'extend.booking_trans_id')
            ->join('sale', function ($join) {
                $join->on('sale.booking_trans_id', '=', 'booking_trans.id')
                     ->whereIn('sale.type', ['Extend','Extend (Agent)']);
            })
            ->where('extend.id', $extend->id)
            ->where('extend.extend_type', 'manual')
            ->whereColumn('sale.id', 'extend.sale_id') // sale.id = extend.sale_id
            ->selectRaw("
                sale.total_sale,
                sale.id AS sale_id,
                extend.extend_from_date,
                extend.extend_to_date,
                extend.total,
                sale.payment_status,
                sale.payment_type,
                extend.payment,
                extend.sale_id AS extend_sale_id,
                booking_trans.id AS booking_id
            ")
            ->first();

        abort_if(!$row, 404, 'Extend/Sale not found');

        // Pack data for the view
        return view('reservation.extendedit', [
            'extend'             => $extend,
            'sale_id'            => $row->sale_id,
            'booking_id'         => $row->booking_id,
            'total_sale'         => (float)$row->total_sale,
            'extend_from_date'   => $row->extend_from_date, // full datetime
            'extend_to_date'     => $row->extend_to_date,   // full datetime
            'total'              => (float)$row->total,
            'payment'            => (float)$row->payment,
            'payment_status'     => $row->payment_status,
            'payment_type'       => $row->payment_type,
        ]);
    }

    public function update(Request $request, Extend $extend)
    {
        // Pull linked sale + booking ids to keep parity with vanilla
        $saleId = DB::table('extend')->where('id', $extend->id)->value('sale_id');
        $bookingId = DB::table('extend')->where('id', $extend->id)->value('booking_trans_id');
        abort_if(!$saleId || !$bookingId, 404, 'Linked sale/booking not found');

        // Extract flags coming from the hidden inputs toggled by JS
        $dateEdit = $request->filled('date_edit');
        $saleEdit = $request->filled('sale_edit');

        // Validate inputs conditionally (match your selects/rules)
        $rules = [
            'payment_status' => [Rule::in(['Paid','Collect',null])],
            'payment_type'   => [Rule::in(['Collect','Unpaid','Cash','Online','Card','QRPay',null])],
        ];

        if ($dateEdit) {
            $rules = array_merge($rules, [
                'extend_from_date' => ['required', 'date'],
                'extend_from_time' => ['required', 'date_format:H:i'],
                'extend_to_date'   => ['required', 'date'],
                'extend_to_time'   => ['required', 'date_format:H:i'],
            ]);
        }

        if ($saleEdit) {
            $rules = array_merge($rules, [
                'sale'            => ['required', 'numeric'],
                'payment_extend'  => ['required', 'numeric'],
                'payment_status'  => ['required', Rule::in(['Paid','Collect'])],
                'payment_type'    => ['required', Rule::in(['Collect','Unpaid','Cash','Online','Card','QRPay'])],
            ]);
        }

        $data = $request->validate($rules);

        // Pull current DB values used when toggles are OFF
        $row = DB::table('extend')
            ->join('sale', 'sale.id', '=', 'extend.sale_id')
            ->where('extend.id', $extend->id)
            ->select([
                'extend.extend_from_date as from_dt',
                'extend.extend_to_date as to_dt',
                'extend.total as extend_total',
                'extend.payment as extend_payment',
                'sale.total_sale',
                'sale.payment_status as sale_payment_status',
                'sale.payment_type as sale_payment_type',
            ])->first();

        abort_if(!$row, 404);

        // Compute final values to write
        // ---- dates & times
        if ($dateEdit) {
            $from = Carbon::parse($request->input('extend_from_date') . ' ' . $request->input('extend_from_time') . ':00');
            $to   = Carbon::parse($request->input('extend_to_date')   . ' ' . $request->input('extend_to_time')   . ':00');
        } else {
            // fallback to existing
            $from = Carbon::parse($row->from_dt);
            $to   = Carbon::parse($row->to_dt);
        }

        // ---- sale figures
        $finalSaleTotal     = $saleEdit ? (float) $request->input('sale')           : (float) $row->total_sale;
        $finalExtendTotal   = $saleEdit ? (float) $request->input('sale')           : (float) $row->extend_total;   // mirrors vanilla “$sale = $total” if not editing
        $finalExtendPayment = $saleEdit ? (float) $request->input('payment_extend') : (float) $row->extend_payment;

        $paymentStatus = $saleEdit ? $request->input('payment_status') : $row->sale_payment_status;
        $paymentType   = $saleEdit ? $request->input('payment_type')   : $row->sale_payment_type;

        // Day diff for sale.total_day (vanilla uses dateDifference ‘%a’)
        $totalDay = $from->copy()->startOfMinute()->diffInDays($to->copy()->startOfMinute());

        DB::transaction(function () use (
            $extend, $saleId, $bookingId, $dateEdit, $saleEdit,
            $from, $to, $finalSaleTotal, $finalExtendTotal, $finalExtendPayment,
            $paymentStatus, $paymentType, $totalDay
        ) {
            // --- UPDATE sale (mirrors your UPDATE sale SET ...)
            // pickup_date/return_date only if date_edit
            $saleUpdate = [
                'total_sale' => $finalSaleTotal,
                'total_day'  => $totalDay,
                'modified'   => now(),
            ];
            if ($dateEdit) {
                $saleUpdate['pickup_date'] = $from->format('Y-m-d H:i:s');
                $saleUpdate['return_date'] = $to->format('Y-m-d H:i:s');
            }
            if ($saleEdit) {
                $saleUpdate['payment_status'] = $paymentStatus;
                $saleUpdate['payment_type']   = $paymentType;
            }
            DB::table('sale')->where('id', $saleId)->update($saleUpdate);

            // --- UPDATE extend (mirrors your UPDATE extend SET ...)
            $extendUpdate = [
                'm_date' => now(),
            ];
            if ($dateEdit) {
                $extendUpdate['extend_from_date'] = $from->format('Y-m-d H:i:s');
                $extendUpdate['extend_from_time'] = $from->format('H:i:s');
                $extendUpdate['extend_to_date']   = $to->format('Y-m-d H:i:s');
                $extendUpdate['extend_to_time']   = $to->format('H:i:s');
            }
            if ($saleEdit) {
                $extendUpdate['total']          = $finalExtendTotal;
                $extendUpdate['payment']        = $finalExtendPayment;
                $extendUpdate['payment_status'] = $paymentStatus;
                $extendUpdate['payment_type']   = $paymentType;
            }
            DB::table('extend')->where('id', $extend->id)->update($extendUpdate);

            // --- DELETE old sale_log (exactly like your code)
            DB::table('sale_log')->where('sale_id', $saleId)->delete();

            // --- (Optional) Rebuild sale_log if needed & payment_status == 'Paid'
            if ($paymentStatus === 'Paid') {
                // Minimal parity: create 1 summary log row for the whole period.
                // If you want the full day-by-day breakdown like your while-loop,
                // swap this with a detailed generator.
                DB::table('sale_log')->insert([
                    'sale_id'    => $saleId,
                    'daily_sale' => $finalSaleTotal,
                    'day'        => $totalDay,
                    'hour'       => $from->diffInHours($to),
                    'type'       => 'extend (summary)',
                    'year'       => $from->year,
                    'date'       => $from->format('Y-m-d H:i:s'),
                    'created'    => now(),
                ]);
            }
        });

        return redirect()
        ->route('reservation.view', $bookingId)   // or ->route('reservation.view', ['booking' => $bookingId])
        ->with('success', 'Extend has been successfully modified');

    }

     public function destroy(Extend $extend)
    {
        // Eager load relations we need
        $extend->loadMissing(['booking', 'sale']);

        // Guard
        $booking = $extend->booking;
        abort_if(!$booking, 404, 'Booking not found for this extend.');

        DB::transaction(function () use ($extend, $booking) {
            // 1) Delete related sale logs + sale (if this extend has a linked sale)
            if ($extend->sale) {
                // assumes Sale has logs() relation ->hasMany(SaleLog::class, 'sale_id')
                $extend->sale->logs()->delete();
                $extend->sale->delete();
            }

            // Keep these BEFORE we delete the extend (we will need its from-date as fallback)
            $extendFrom = Carbon::parse($extend->extend_from_date);

            // 2) Delete the extend itself
            $extend->delete();

            // 3) Recompute the booking's planned return so the next extend baseline is correct
            //    Prefer the latest remaining MANUAL extend's "to" datetime
            $latestTo = Extend::query()
                ->where('booking_trans_id', $booking->id)
                ->where('extend_type', 'manual')
                ->orderByDesc('extend_to_date')
                ->value('extend_to_date'); // full "Y-m-d H:i:s" or null

            if ($latestTo) {
                $to = Carbon::parse($latestTo);
                $booking->return_date = $to->toDateString();
                $booking->return_time = $to->format('H:i');
            } else {
                // No other manual extend remains.
                // Roll the booking's plan back to what it was BEFORE this deleted extend
                // (use the deleted extend's "from" instant).
                $booking->return_date = $extendFrom->toDateString();
                $booking->return_time = $extendFrom->format('H:i');
            }

            // You can also normalize the availability flag if you want:
            // $booking->available = 'Extend'; // or some other state that matches your flow
            $booking->save();

            // 4) OPTIONAL: adjust an existing AUTO extend (if you use them)
            //    Keep auto window aligned with the new planned return (booking->return_*).
            $auto = Extend::query()
                ->where('booking_trans_id', $booking->id)
                ->where('extend_type', 'auto')
                ->first();

            if ($auto) {
                // anchor "from" at the booking's current planned return,
                // and "to" is +1 day at the same clock.
                $from = Carbon::parse($booking->return_date.' '.$booking->return_time);
                $auto->update([
                    'extend_from_date'   => $from->format('Y-m-d H:i:s'),
                    'extend_from_time'   => $from->format('H:i:s'),
                    'extend_to_date'     => $from->copy()->addDay()->format('Y-m-d H:i:s'),
                    'extend_to_time'     => $from->format('H:i:s'),
                    'auto_extend_status' => 'Extend',
                    'm_date'             => now(),
                ]);
            }
        });

        return redirect()
            ->route('reservation.view', $booking->id)
            ->with('success', 'Extend deleted and booking return time normalized.');
    }
}
