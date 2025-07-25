<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\DB;
use App\Models\BookingTrans;
use App\Models\Company;
use App\Models\User;
use App\Models\OptionalRental;
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
        ])->findOrFail($id);


        $vehicle = $booking->vehicle;
        $customer = $booking->customer;
        $pickupLocation = $booking->pickupLocation;
        $returnLocation = $booking->returnLocation;
        $cdwLog = $booking->cdwLog;
        $cdwName = optional(optional($cdwLog)->cdw->rate)->name;
        $cdwAmount = optional($cdwLog)->amount ?? 0;
        $checklist = $booking->checklist;
        
        // Get CDW Rate names by id
        $cdwRates = CDWRate::orderBy('id')->pluck('name', 'id'); // [0 => 'Bronze', 1 => 'Silver', ...]

        $company = Company::first();
        $nickname = optional(User::find($booking->staff_id))->name;

        $optionalrental = OptionalRental::whereIn('id', [1, 6])
            ->pluck('amount', 'id'); // Returns [1 => amount1, 6 => amount6]

        $additionalDriverPrice = $optionalrental[1] ?? 0;
        $DriverPrice = $optionalrental[6] ?? 0;



        $day = Carbon::parse($booking->pickup_date)->diffInDays(Carbon::parse($booking->return_date));
        $time = Carbon::parse($booking->pickup_date)->diffInHours(Carbon::parse($booking->return_date));

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


        //1st page

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


        if($checklist->car_driver=="Y") {
        
            $priceDriver = ($DriverPrice)/8;
            $priceDayDriver = ($day*24)*$priceDriver;
            $priceHourDriver = $time*$priceDriver;
            $priceDriverTotal= $priceDayDriver + $priceHourDriver;
        }
        else {
            $priceDriverTotal = 0;
        }

        if($checklist->car_add_driver=="Y") {
        $priceAddDriver = $additionalDriverPrice;
        }
        else {
            $priceAddDriver = 0;
        }

        if($checklist->car_out_child_seat=="Y") {
            $priceChildSeat = $day * 5;
        }
        else {
            $priceChildSeat = 0;
        }

        $priceFirstRow = $priceAddDriver;
        $priceSecondRow = $priceDriverTotal;
        $priceThirdRow =  $TravelInsurance = 0;
        $priceFourthRow = $priceChildSeat;
        $cdw_amount = $booking->cdwLog->amount ?? null;


        if($cdwName == "Bronze" || $cdwName == NULL) {
            $pdf->SetXY(25, 86);
            $pdf->Write(0, "Y");
            $pdf->SetXY(25, 231.6);
            $pdf->Write(0, "Y");
            $priceFirstRow = $priceFirstRow + $cdw_amount;
        }
        else if($cdwName == "Silver") {
            $pdf->SetXY(25, 90.4);
            $pdf->Write(0, "Y");
            $pdf->SetXY(25, 236.2);
            $pdf->Write(0, "Y");
            $priceSecondRow = $priceSecondRow + $cdw_amount;
        }
        else if($cdwName == "Gold") {
            $pdf->SetXY(25, 95.2);
            $pdf->Write(0, "Y");
            $pdf->SetXY(25, 240.9);
            $pdf->Write(0, "Y");
            $priceThirdRow = $priceThirdRow + $cdw_amount;
        }
        else if($cdwName == "Platinum") {
            $pdf->SetXY(25, 99.7);
            $pdf->Write(0, "Y");
            $pdf->SetXY(25, 245.4);
            $pdf->Write(0, "Y");
            $priceFourthRow = $priceFourthRow + $cdw_amount;
        }


        $pdf->SetXY(48, 86);
        $pdf->Write(0, "CDW " . ($cdwRates[1] ?? ''));
        $pdf->SetXY(48, 231.6);
        $pdf->Write(0, "CDW " . ($cdwRates[1] ?? ''));

        $pdf->SetXY(48, 90.4);
        $pdf->Write(0, "SCDW " . ($cdwRates[2] ?? ''));
        $pdf->SetXY(48, 236.2);
        $pdf->Write(0, "SCDW " . ($cdwRates[2] ?? ''));

        $pdf->SetXY(48.4, 95.2);
        $pdf->Write(0, "SCDW " . ($cdwRates[3] ?? ''));
        $pdf->SetXY(48.4, 240.9);
        $pdf->Write(0, "SCDW " . ($cdwRates[3] ?? ''));

        $pdf->SetXY(46, 99.7);
        $pdf->Write(0, "SCDW " . ($cdwRates[4] ?? ''));
        $pdf->SetXY(46, 245.4);
        $pdf->Write(0, "SCDW " . ($cdwRates[4] ?? ''));

        if($booking->p_cost >= 1) {
            
            $pdf->SetXY(25.3, 53.8);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(25.3, 199.5);
            $pdf->Write(0, 'Y');
            
            $pdf->SetXY(60.1, 47.4);
            $pdf->MultiCell(90, 4.35, $booking->p_address, 0, 'L');
            $pdf->SetXY(60.1, 192.8);
            $pdf->MultiCell(90, 4.35, $booking->p_address, 0, 'L');
            
            $pdf->SetXY(25.3, 72.2);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(25.3, 217.9);
            $pdf->Write(0, 'Y');
            
            $pdf->SetXY(60.1, 65.5);
            $pdf->MultiCell(90, 4.35, $booking->p_address, 0, 'L');
            $pdf->SetXY(60.1, 211.5);
            $pdf->MultiCell(90, 4.35, $booking->p_address, 0, 'L');
            
            $pdf->SetXY(170, 65.3);
            $pdf->Write(0, 'RM '.$booking->p_cost);
            $pdf->SetXY(170, 214.7);
            $pdf->Write(0, 'RM '.$booking->p_cost);
        }
        else if($booking->r_cost >= 1) {
            
            $pdf->SetXY(25.3, 63);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(25.3, 208.7);
            $pdf->Write(0, 'Y');
            
            $pdf->SetXY(60.1, 65.5);
            $pdf->MultiCell(90, 4.35, $booking->r_address, 0, 'L');
            $pdf->SetXY(60.1, 192.8);
            $pdf->MultiCell(90, 4.35, $booking->r_address, 0, 'L');
            
            $pdf->SetXY(25.3, 81.35);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(25.3, 227);
            $pdf->Write(0, 'Y');
            
            $pdf->SetXY(60.1, 65.5);
            $pdf->MultiCell(90, 4.35, $booking->r_address, 0, 'L');
            $pdf->SetXY(60.1, 211.5);
            $pdf->MultiCell(90, 4.35, $booking->r_address, 0, 'L');
            
            $pdf->SetXY(170, 65.3);
            $pdf->Write(0, 'RM '.$booking->r_cost);
            $pdf->SetXY(170, 214.7);
            $pdf->Write(0, 'RM '.$booking->r_cost);
        }
        else {
            
            $pdf->SetXY(25.3, 49.25);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(25.3, 194.9);
            $pdf->Write(0, 'Y');
            
            $pdf->SetXY(60.1, 47.4);
            $pdf->MultiCell(90, 4.35, $booking->pickupLocation->description);
            $pdf->SetXY(60.1, 192.8);
            $pdf->MultiCell(90, 4.35, $booking->pickupLocation->description);
            
            $pdf->SetXY(25.3, 67.5);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(25.3, 213.2);
            $pdf->Write(0, 'Y');
            
            $pdf->SetXY(60.1, 65.5);
            $pdf->MultiCell(90, 4.35, $booking->pickupLocation->description);
            $pdf->SetXY(60.1, 211.5);
            $pdf->MultiCell(90, 4.35, $booking->pickupLocation->description);
            
            $pdf->SetXY(170, 65.3);
            $pdf->Write(0, 'RM 0.00');
            $pdf->SetXY(170, 210.7);
            $pdf->Write(0, 'RM 0.00');
        }

        if($checklist->car_add_driver == 'Y') {
    
            $pdf->SetXY(94, 86);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(94, 231.6);
            $pdf->Write(0, 'Y');
        }

        $pdf->SetXY(170, 86);
        $pdf->Write(0, "RM ".number_format($priceFirstRow,2));
        $pdf->SetXY(170, 231.6);
        $pdf->Write(0, "RM ".number_format($priceFirstRow,2));

        if($checklist->car_driver == 'Y') {
    
            $pdf->SetXY(94, 90.4);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(94, 236.2);
            $pdf->Write(0, 'Y');
        }

        $pdf->SetXY(170, 90.4);
        $pdf->Write(0, "RM ".number_format($priceSecondRow,2));
        $pdf->SetXY(170, 236.2);
        $pdf->Write(0, "RM ".number_format($priceSecondRow,2));

        if($TravelInsurance == 'Y') {
    
            $pdf->SetXY(94, 95.2);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(94, 240.9);
            $pdf->Write(0, 'Y');
        }

        $pdf->SetXY(170, 95.2);
        $pdf->Write(0, "RM ".number_format($priceThirdRow,2));
        $pdf->SetXY(170, 240.9);
        $pdf->Write(0, "RM ".number_format($priceThirdRow,2));


        if($checklist->car_out_child_seat == 'Y') {
    
            $pdf->SetXY(94, 99.7);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(94, 245.4);
            $pdf->Write(0, 'Y');
        }

        $pdf->SetXY(170, 99.7);
        $pdf->Write(0, "RM ".number_format($priceFourthRow,2));
        $pdf->SetXY(170, 245.4);
        $pdf->Write(0, "RM ".number_format($priceFourthRow,2));

        $totalall = $priceFirstRow + $priceSecondRow + $priceThirdRow + $priceFourthRow;

        $refund_dep_balance = $booking->refund_dep - $booking->balance;

        if($refund_dep_balance <= 0) {
            $refund_dep_balance = $booking->refund_dep;
        }

        if($booking->refund_dep_payment == "Cash") {
            
            $pdf->SetXY(25.3, 104.45);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(25.3, 250.1);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(50, 109);
            $pdf->Write(0,'RM '.number_format($refund_dep_balance,2));
            $pdf->SetXY(50, 254.65);
            $pdf->Write(0,'RM '.number_format($refund_dep_balance,2));
        }
        else if($booking->refund_dep_payment == "Collect") {
            
            $pdf->SetXY(46, 109);
            $pdf->Write(0,'Collect RM '.number_format($refund_dep_balance,2));
            $pdf->SetXY(46, 254.65);
            $pdf->Write(0,'Collect RM '.number_format($refund_dep_balance,2));
        }
        else if($booking->refund_dep_payment == "Online") {
            
            $pdf->SetXY(25.3, 109);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(25.3, 254.65);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(50, 109);
            $pdf->Write(0,'RM '.number_format($refund_dep_balance,2));
            $pdf->SetXY(50, 254.65);
            $pdf->Write(0,'RM '.number_format($refund_dep_balance,2));
        }
        else if($booking->refund_dep_payment == "Card") {
            
            $pdf->SetXY(25.3, 113.5);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(25.3, 259.32);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(50, 109);
            $pdf->Write(0,'RM '.number_format($refund_dep_balance,2));
            $pdf->SetXY(50, 254.65);
            $pdf->Write(0,'RM '.number_format($refund_dep_balance,2));
        }
        else {
            
            $pdf->SetXY(50, 109);
            $pdf->Write(0,' ');
            $pdf->SetXY(50, 254.65);
            $pdf->Write(0,' ');
        }

        if($booking->payment_type == "Cash") {
    
            $pdf->SetXY(100.5, 104.45);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(100.5, 250.1);
            $pdf->Write(0, 'Y');
        }
        else if($booking->payment_type == "Online") {
            
            $pdf->SetXY(100.5, 109);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(100.5, 254.65);
            $pdf->Write(0, 'Y');
        }
        else if($booking->payment_type == "Card") {
            
            $pdf->SetXY(100.5, 113.5);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(100.5, 259.32);
            $pdf->Write(0, 'Y');
        }

        if($booking->agent_id == '0') {
    
            if($booking->refund_dep_payment != "Collect") {
                
                // if($balance == "0") {
                //     $balance = $refund_dep;
                // }
                
                $balancenew = $booking->est_total - $booking->balance;
            }
            else {
                $balancenew = $booking->est_total - $booking->balance;
            }
        }
        else {
            
            if($booking->refund_dep_payment != "Collect") {
                
                // if($balance == "0") {
                //     $balance = $refund_dep;
                // }
                
                $balancenew = $booking->sub_total - $booking->balance;
            }
            else {
                $balancenew = $booking->sub_total - $booking->balance;
            }
        }

        $pdf->SetXY(119.5, 107);
        $pdf->Write(0,"Payment: RM ".$booking->balance);
        $pdf->SetXY(119.5, 252.7);
        $pdf->Write(0,"Payment: RM ".$booking->balance);

        $pdf->SetFont('Helvetica', 'I', 8);

        if($balancenew == '0' || $balancenew < '0') {
            
            $balancenew = "(Paid)";
            $pdf->SetXY(130, 110.9);
            $pdf->Write(0,$balancenew);
            $pdf->SetXY(130, 256.6);
            $pdf->Write(0,$balancenew);
        }
        else if($balancenew > '0') {
            
            $balancenew = "(Collect RM ".number_format($balancenew,2).")";
            $pdf->SetXY(120.5, 110.9);
            $pdf->Write(0,$balancenew);
            $pdf->SetXY(120.5, 256.6);
            $pdf->Write(0,$balancenew);
        }

        if($booking->agent_id == '0') {
            $grandtotal = $booking->est_total + $cdw_amount;
        }
        else {
            $grandtotal = $booking->sub_total + $cdw_amount;
        }

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(170, 109);
        $pdf->Write(0, 'RM '.number_format($grandtotal,2));
        $pdf->SetXY(170, 254.65);
        $pdf->Write(0, 'RM '.number_format($grandtotal,2));





        // --- Payment Summary ---
        // $balance = $booking->balance;
        // $refund = $booking->refund_dep;
        // $refundMethod = $booking->refund_dep_payment;

        // $grandTotal = ($booking->agent_id == 0) ? $booking->est_total + $cdwAmount : $booking->sub_total + $cdwAmount;
        // $paymentNote = $grandTotal - $balance <= 0 ? "(Paid)" : "(Collect RM " . number_format($grandTotal - $balance, 2) . ")";

        // $pdf->SetXY(119.5, 107);
        // $pdf->Write(0, "Payment: RM " . number_format($balance, 2));
        // $pdf->SetXY(120.5, 110.9);
        // $pdf->Write(0, $paymentNote);
        // $pdf->SetXY(170, 109);
        // $pdf->Write(0, "RM " . number_format($grandTotal, 2));

        // --- Signature ---
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(5, 122);
        $pdf->Write(0, $nickname ?? '');
        $pdf->SetXY(5, 267.7);
        $pdf->Write(0, $nickname ?? '');

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(12, 127.2);
        $pdf->Write(0, Carbon::parse($booking->created)->format('d/m/Y H:i'));
        $pdf->SetXY(12, 272.5);
        $pdf->Write(0, Carbon::parse($booking->created)->format('d/m/Y H:i'));

        $pdf->SetXY(52, 119.5);
        $pdf->MultiCell(70, 3.3, $fullname, 0, 'L');
        $pdf->SetXY(52, 265);
        $pdf->MultiCell(70, 3.3, $fullname, 0, 'L');


        // 2nd Page
        $pdf->SetFont('Helvetica', '', 10);

        $pdf->SetXY(25, 158.5);
        $pdf->Write(0, $company->company_name ?? '');
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(25, 161.2);
        $pdf->MultiCell(70, 3.5, $company->address ?? '', 0);
        $pdf->SetXY(25, 170.5);
        $pdf->Write(0, $company->phone_no ?? '');


        // --- Customer & Booking Info ---
        $fullname = trim(($customer->firstname ?? '') . ' ' . ($customer->lastname ?? ''));
        $pdf->SetXY(129, 158.1); $pdf->Write(0, $booking->agreement_no);
        $pdf->SetXY(129, 161.1); $pdf->MultiCell(70, 3.5, $fullname, 0);
        $pdf->SetXY(129, 172); $pdf->Write(0, $customer->phone_no ?? '');
        $pdf->SetXY(25, 176.5); $pdf->Write(0, $vehicle->reg_no ?? '');
        $pdf->SetXY(101, 176.5); $pdf->Write(0, $vehicle->model ?? '');
        $pdf->SetXY(29, 183.2); $pdf->Write(0, Carbon::parse($booking->pickup_date)->format('d/m/Y @ h:i A'));
        $pdf->SetXY(103.5, 183.2); $pdf->Write(0, Carbon::parse($booking->return_date)->format('d/m/Y @ h:i A'));

        // --- Subtotal ---
        $pdf->SetXY(170, 181);
        $pdf->Write(0, "RM " . number_format($booking->sub_total, 2));

        // --- Discount ---
        if ($booking->discount_coupon) {
            $pdf->SetXY(70, 190.28);
            $pdf->Write(0, $booking->discount_coupon);
            $pdf->SetXY(170, 190.28);
            $pdf->Write(0, "- RM " . number_format($booking->discount_amount, 2));
        }


        // Output PDF
        return response()->streamDownload(function () use ($pdf, $booking) {
            $pdf->Output();
        }, 'BookingReceipt_' . $booking->agreement_no . '.pdf');
    }
}
