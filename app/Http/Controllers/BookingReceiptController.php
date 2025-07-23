<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\DB;
use App\Models\BookingTrans;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\CDWLog;
use App\Models\CDW;
use App\Models\CDWRate;
use Carbon\Carbon;

class BookingReceiptController extends Controller
{
    public function generateReceipt($id)
    {
        // Get Booking with Relationships
        $booking = BookingTrans::with([
            'vehicle',
            'customer',
            'checklist',
            'pickupLocation',
            'returnLocation',
            'cdwLog.cdw.cdwRate',
            'pickupLocation',
            'returnLocation',
        ])->findOrFail($id);

        $vehicle = $booking->vehicle;
        $customer = $booking->customer;
        $pickupLocation = $booking->pickupLocation;
        $returnLocation = $booking->returnLocation;
        $cdwLog = $booking->cdwLog;
        $cdwName = optional(optional($cdwLog)->cdw->rate)->name;
        $cdwAmount = optional($cdwLog)->amount ?? 0;

        $company = Company::first();
        $nickname = optional(User::find($booking->staff_id))->name;

        $day = Carbon::parse($booking->pickup_date)->diffInDays(Carbon::parse($booking->return_date));

        // --- PDF Setup ---
        $pdf = new Fpdi('P', 'mm', [203, 305]);
        $pdf->AddPage();
        $pdf->setSourceFile(public_path('assets/document/bookingreceipt.pdf'));
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx);

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // --- Company Logo ---
        if ($company && $company->image && file_exists(public_path('assets/img/company/' . $company->image))) {
            $pdf->Image(public_path('assets/img/company/' . $company->image), 6.7, 11, 16);
            $pdf->Image(public_path('assets/img/company/' . $company->image), 6.7, 156.5, 16);
        }

        // --- Company Info ---
        $pdf->SetXY(25, 13);
        $pdf->Write(0, $company->company_name ?? '');
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(25, 15.7);
        $pdf->MultiCell(70, 3.5, $company->address ?? '', 0);
        $pdf->SetXY(25, 25);
        $pdf->Write(0, $company->phone_no ?? '');

        // --- Customer & Booking Info ---
        $fullname = trim(($customer->firstname ?? '') . ' ' . ($customer->lastname ?? ''));
        $pdf->SetXY(129, 12.4); $pdf->Write(0, $booking->agreement_no);
        $pdf->SetXY(129, 15.4); $pdf->MultiCell(70, 3.5, $fullname, 0);
        $pdf->SetXY(129, 26.3); $pdf->Write(0, $customer->phone_no ?? '');
        $pdf->SetXY(25, 30.9); $pdf->Write(0, $vehicle->reg_no ?? '');
        $pdf->SetXY(101, 30.9); $pdf->Write(0, $vehicle->model ?? '');
        $pdf->SetXY(29, 37.8); $pdf->Write(0, Carbon::parse($booking->pickup_date)->format('d/m/Y @ h:i A'));
        $pdf->SetXY(103.5, 37.8); $pdf->Write(0, Carbon::parse($booking->return_date)->format('d/m/Y @ h:i A'));

        // --- Subtotal ---
        $pdf->SetXY(170, 35.5);
        $pdf->Write(0, "RM " . number_format($booking->sub_total, 2));

        // --- Discount ---
        if ($booking->discount_coupon) {
            $pdf->SetXY(70, 44.5);
            $pdf->Write(0, $booking->discount_coupon);
            $pdf->SetXY(170, 44.5);
            $pdf->Write(0, "- RM " . number_format($booking->discount_amount, 2));
        }

        // --- Pickup/Return Logic (Address/Cost or Location Fallback) ---
        // You can replicate the pickup_cost and return_cost logic here like the legacy if ($p_cost >= 1), etc.

        // --- CDW Type & Charges ---
        if ($cdwName) {
            $label = $cdwName == "Bronze" ? "CDW" : "SCDW";
            $pdf->SetXY(48, 86); // Adjust if multiple CDW types
            $pdf->Write(0, $label . " " . $cdwName);
            $pdf->SetXY(170, 86);
            $pdf->Write(0, "RM " . number_format($cdwAmount, 2));
        }

        // --- Payment Summary ---
        $balance = $booking->balance;
        $refund = $booking->refund_dep;
        $refundMethod = $booking->refund_dep_payment;

        $grandTotal = ($booking->agent_id == 0) ? $booking->est_total + $cdwAmount : $booking->sub_total + $cdwAmount;
        $paymentNote = $grandTotal - $balance <= 0 ? "(Paid)" : "(Collect RM " . number_format($grandTotal - $balance, 2) . ")";

        $pdf->SetXY(119.5, 107);
        $pdf->Write(0, "Payment: RM " . number_format($balance, 2));
        $pdf->SetXY(123.5, 110.9);
        $pdf->Write(0, $paymentNote);
        $pdf->SetXY(170, 109);
        $pdf->Write(0, "RM " . number_format($grandTotal, 2));

        // --- Signature ---
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(5, 122);
        $pdf->Write(0, $nickname ?? '');

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(12, 127.2);
        $pdf->Write(0, Carbon::parse($booking->created)->format('d/m/Y H:i'));

        $pdf->SetXY(52, 119.5);
        $pdf->MultiCell(70, 3.3, $fullname, 0, 'L');

        // Output PDF
        return response()->streamDownload(function () use ($pdf, $booking) {
            $pdf->Output();
        }, 'BookingReceipt_' . $booking->agreement_no . '.pdf');
    }
}
