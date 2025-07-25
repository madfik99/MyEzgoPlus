<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BookingTrans;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;


class PickupController extends Controller
{

    public function show($booking_id)
{
    $booking = BookingTrans::with(['customer', 'vehicle'])->findOrFail($booking_id);

    // Optional check: Ensure vehicle is returned
    if (in_array($booking->available, ['Out', 'Extend'])) {
        return back()->with('error', 'This vehicle is currently not available for pickup.');
    }

    return view('reservation.pickup_vehicle', compact('booking'));
}

}