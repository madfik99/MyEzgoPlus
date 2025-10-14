<?php 

// app/Http/Controllers/DeleteRequestController.php
namespace App\Http\Controllers;

use App\Models\BookingTrans; // your Eloquent model for booking_trans
use App\Models\Company;
use Illuminate\Http\Request;

class DeleteRequestController extends Controller
{
    public function create(BookingTrans $booking, Request $request)
    {
        // You can gate/authorize here if needed
        $agreementNo = $request->query('agreement_no', $booking->agreement_no);
        return view('reservation.delete-reason', compact('booking', 'agreementNo'));
    }

    public function store(Request $request, BookingTrans $booking)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        // Update booking_trans columns
        $booking->delete_status = 'pending'; // previously 'active'
        $booking->reason = $validated['reason'];
        $booking->save();

        return redirect()
            ->route('reservation.reservation_list')
            ->with('success', 'Delete request submitted. Status is now pending.');
    }

    // (Optional) minimal list to verify it works; you can style later
    public function index(Request $request)
    {
        $pending = BookingTrans::query()
            ->leftJoin('vehicle', 'vehicle.id', '=', 'booking_trans.vehicle_id')
            ->where('booking_trans.delete_status', 'pending')
            ->select('booking_trans.*', 'vehicle.reg_no')
            ->get();

        return view('reservation.delete-approval-list', compact('pending'));
    }

    public function show(BookingTrans $booking)
    {
        // Make sure these relationship names exist on BookingTrans (see model snippet below)
        $allowedPositions = [
        'pickup_interior', 'return_interior',
        'pickup_exterior', 'return_exterior',
        ];

        $booking->load([
            'customer:id,firstname,lastname,nric_no,address,phone_no,email,license_no',
            'vehicle:id,make,model,reg_no',
            'staff:id,name',
            'checklist',
            'uploads' => fn ($q) => $q
                ->whereIn('position', $allowedPositions)
                ->whereNotNull('file_name')
                ->where('file_size', '!=', 0)
                ->orderByDesc('id')
                ->limit(10),   // now this returns up to 10 matching images
            'extensions'  => fn ($q) => $q->orderBy('c_date'),
        ]);

        // Company info
        $companyRow = Company::first();
        $company = [
            // Serve a usable URL (fallback to a public asset)
            'logo'        => $companyRow && $companyRow->image
                                ? (str_starts_with($companyRow->image, 'http')
                                    ? $companyRow->image
                                    : asset('assets/img/company/'.$companyRow->image))
                                : asset('assets/img/logo.png'),
            'name'        => $companyRow->company_name     ?? 'Your Company Name',
            'website'     => $companyRow->website_name     ?? 'example.com',
            'address'     => $companyRow->address          ?? 'Your Address',
            'phone'       => $companyRow->phone_no         ?? '0123456789',
            'registration'=> $companyRow->registration_no  ?? '123456-X',
        ];

        return view('reservation.delete-approval-view', compact('booking', 'company'));
    }

    public function confirm(BookingTrans $booking, Request $request)
    {
        $booking->delete_status = 'approved'; // adjust if your status differs
        $booking->save();

        return redirect()
            ->route('delete.request.index')
            ->with('success', 'Delete confirmed.');
    }

    public function decline(BookingTrans $booking, Request $request)
    {
        $booking->delete_status = 'active'; // revert
        $booking->save();

        return redirect()
            ->route('delete.request.index')
            ->with('success', 'Delete request declined.');
    }
}
