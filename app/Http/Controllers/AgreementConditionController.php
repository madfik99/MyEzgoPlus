<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BookingTrans;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;


class AgreementConditionController extends Controller
{
    public function showInspectionForm(Request $request)
    {
        $request->validate([
            'language'   => 'required|in:english,malay',
            'booking_id' => 'required|exists:booking_trans,id',
            'step'       => 'required|numeric',
        ]);

        $language  = $request->language;
        $step      = $request->step;
        $bookingId = $request->booking_id;

        $booking = BookingTrans::with(['customer.nricSelfieImage', 'vehicle'])
                            ->findOrFail($bookingId);

        return view('reservation.agreement_condition', [
            'booking'         => $booking,
            'customer'        => $booking->customer,
            'vehicle'         => $booking->vehicle,
            'language'        => $language,
            'step'            => $step,
            'nricSelfieImage' => $booking->customer->nricSelfieImage,
        ]);
    }

    public function requestOtp(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customer,id',
            'phone_no'    => 'required',
            'type'        => 'required|in:whatsapp,message',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        $randomnumber = sprintf("%06d", rand(1, 999999));

        // Update OTP in customer table
        $customer->update([
            'otp_code'  => $randomnumber,
            'otp_match' => 'no',
            'otp_type'  => $request->type,
            'otp_time'  => now(),
        ]);

        if ($request->type === "whatsapp") {
            $whatsappMsg = urlencode(
                "SALAM\n\n".
                "Adakah anda sudah memahami Syarat dan Peraturan Sewaan MYEZGO yang diterangkan oleh pekerja kami?\n\n".
                "Jika ya, berikan OTP ini kepada pekerja kami: $randomnumber"
            );

            return redirect("https://wa.me/{$request->phone_no}?text={$whatsappMsg}");
        }

        if ($request->type === "message") {
            $response = Http::asForm()->post('https://api.esms.com.my/sms/send', [
                'user' => '6e22436942df4a9e85e7fa3e13f6ac0c',
                'pass' => '1xhgljxy83bxbqg7t0kiyxnqwqj9fwfivhcfxupwptiupz3hcjgzyjik5fwxew7yrukxi6pqziyt6rd6ifs4rjuhqbd7dkk8gceq',
                'to'   => $request->phone_no,
                'msg'  => 'RM0.00 MYEZGO - Do you understand with the T&C of MYEZGO Rental explained by our staff? '.
                        "If you do, submit this OTP = {$randomnumber} to our staff. TQ.",
            ]);

            if ($response->successful()) {
                return back()->with('success', 'OTP sent successfully via SMS.');
            } else {
                return back()->with('error', 'OTP could not be sent.');
            }
        }

        return back()->with('error', 'Invalid request.');
    }


    public function verifyOtp(Request $request, $booking_id)
    {
        $request->validate([
            'otp_code' => 'required|digits:6',
        ]);

        $booking  = BookingTrans::with('customer')->findOrFail($booking_id);
        $customer = $booking->customer;

        if ($customer->otp_code === $request->otp_code && $customer->otp_match === 'no') {
            $customer->update([
                'otp_match' => 'yes',
                'otp_time'  => now(),
            ]);

            return redirect()->route('pickup.vehicle', ['booking_id' => $booking_id])
                ->with('success', __('OTP successfully verified.'));
        }

        return back()->with('error', __('OTP does not match.'));
    }

}
