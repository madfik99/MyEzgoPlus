<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;
use App\Models\BookingTrans;
use App\Models\CDWRate;
use App\Models\Company;
use App\Models\OptionalRental;
use App\Models\User;
use Carbon\Carbon;

class AgreementController extends Controller
{
    public function generate($id)
    {
        // Fetch booking with all related models using Eloquent relationships
        $booking = BookingTrans::with([
            'vehicle',
            'customer',
            'checklist',
            'cdwLog.cdw.cdwRate',
            'pickupLocation',
            'returnLocation',
        ])->findOrFail($id);

        // Fetch company info
        $company = Company::first();

        
        $optionalrental = OptionalRental::whereIn('id', [1, 6])
            ->pluck('amount', 'id'); // Returns [1 => amount1, 6 => amount6]

        $additionalDriverPrice = $optionalrental[1] ?? 0;
        $DriverPrice = $optionalrental[6] ?? 0;


        $user = User::find($booking->staff_id);

        // Generate PDF
        $pdf = new Fpdi('P', 'mm', [208, 302]);
        $pdf->AddPage();
        $pdf->setSourceFile(public_path('assets/document/agreement.pdf'));
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx);

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // Company Info
        if ($company && $company->image) {
            $pdf->Image(public_path('assets/img/company/' . $company->image), 9, 10, 16);
        }
        $pdf->SetXY(30, 12.5);
        $pdf->Write(0, $company->company_name ?? '');
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY(30, 14.5);
        $pdf->MultiCell(70, 3.5, $company->address ?? '');
        $pdf->SetXY(30, 24);
        $pdf->Write(0, $company->phone_no ?? '');

        // --- Booking Data ---
        $vehicle = $booking->vehicle;
        $customer = $booking->customer;
        $checklist = $booking->checklist;
        $cdwLog = $booking->cdwLog;

        $fullname = trim(($customer->firstname ?? '') . ' ' . ($customer->lastname ?? ''));

        $pdf->SetXY(135, 12.2);
        $pdf->Write(0, $booking->agreement_no);

        $pdf->SetXY(135, 18);
        $pdf->Write(0, $customer->nric_no ?? '');

        // $pdf->SetXY(140, 18);
        // $pdf->Write(0, $additionalDriverPrice);

        // $pdf->SetXY(150, 18);
        // $pdf->Write(0, $DriverPrice);


        $pdf->SetXY(135, 24.2);
        $pdf->Write(0, $customer->country ?? '');

        $pdf->SetXY(143.5, 29.8);
        $pdf->Write(0, date('d/m/Y', strtotime($customer->license_expiry_date)));

        $pdf->SetXY(170, 29.8);
        $pdf->Write(0, $customer-> license_no);

        $pdf->SetXY(135, 33.0);
        $pdf->MultiCell(63, 3, $customer->address ?? '');
    
        $pdf->SetXY(135, 44.2);
        $pdf->Write(0, $customer->postcode);

        $pdf->SetXY(168, 44.2);
        $pdf->Write(0, $customer->city);

        $pdf->SetXY(192.2, 44.2);
        $pdf->Write(0, $customer->country);

        $pdf->SetXY(135, 49.1);
        $pdf->Write(0, $customer->ref_phoneno ?? '');

        //-- End Right Side ---


        $pdf->SetXY(33.5, 27.5);
        $pdf->MultiCell(60, 3.5 ,$fullname);

        $pdf->SetXY(33.5, 39.8);
        $pdf->Write(0, $customer->phone_no ?? '');

        $pdf->SetXY(33.5, 44);
        $pdf->Write(0, $customer->email ?? '');

        //-- End Left Side ---

        // -- Reference Details --

        $pdf->SetXY(33.5, 49);
        $pdf->Write(0, $customer->ref_name);

        $pdf->SetXY(86, 49);
        $pdf->Write(0, $customer->ref_relationship);


        $pdf->SetXY(33.5, 54);
        if($customer->drv_name == "" && $customer->drv_name == NULL) {
            $pdf->Write(0, "No additional Driver");
        }
        else {
            $pdf->Write(0, $customer->drv_name);
        }

        $pdf->SetXY(33.5, 58.7);
        $pdf->Write(0, $customer->drv_nric);
        
        $pdf->SetXY(142, 54);
        $pdf->Write(0, $customer->drv_license_no);
        
        $pdf->SetXY(178, 54);
        if($customer->drv_license_exp == NULL && $customer->drv_license_exp != "") {
        $pdf->Write(0, date('d/m/Y', strtotime($customer->drv_license_exp)));
        }
        else {
            $pdf->Write(0, "-");
        }

        $pdf->SetXY(135, 58.7);
        $pdf->Write(0, $customer->drv_phoneno);

        //-- End Reference Details --


        // -- Top Right --
    
        $pdf->SetXY(148.5, 6.7);
        $pdf->Write(0, $vehicle->reg_no ?? '');

        $pdf->SetXY(171.7, 7);
        $pdf->Write(0, date('d/m/Y', strtotime($booking->pickup_date)));

        // -- Staff Details --

        $pdf->SetXY(52, 170.5);
        $pdf->MultiCell(56, 3.5, $fullname, 0);


        // -- Customer Signature --

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(5, 172.5);
        $pdf->Write(0, $user->name);


        // Rental And Payment Details

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(43, 187.3);
        $pdf->Write(0, $vehicle->reg_no);

        $pdf->SetXY(101, 187.3);
        $pdf->Write(0, $vehicle->make . " " . $vehicle->model);

        $pdf->SetXY(32.5, 193.5);
        $pdf->Write(0, date('d/m/Y',strtotime($booking->pickup_date))."   @   ".date('H:i',strtotime($booking->pickup_date)));
        
        $pdf->SetXY(106.7, 193.5);
        $pdf->Write(0, date('d/m/Y',strtotime($booking->return_date))."   @   ".date('H:i',strtotime($booking->return_date)));
    
        $pdf->SetXY(171, 193.5);
    
        if($booking->agent_id != '0') {
            $pdf->Write(0, 'RM '.$booking->sub_total);
        }
        else {
            
            if($booking->discount_amount != '' && $booking->discount_amount != '0' && $booking->discount_amount != NULL) {
                
                $est_total_nodisc = $booking->est_total + $booking->discount_amount;
                
                $pdf->Write(0, 'RM '.$est_total_nodisc.".00");
            }
            else {
                $pdf->Write(0, 'RM '.$booking->est_total);
            }
        }

        $pdf->SetXY(32.5, 199.7);
        $pdf->Write(0, $booking->discount_coupon);

        $pdf->SetXY(171, 199.7);
        if($booking->discount_amount != '' && $booking->discount_amount != '0' && $booking->discount_amount != NULL) {
            $pdf->Write(0, "- RM " . $booking->discount_amount);
        }


        if($checklist->car_add_driver == 'Y') {
        
            $pdf->SetXY(25.3, 230);
            $pdf->Write(0, 'Y');
        }
        
        if($checklist->car_add_driver=="Y") {
        $priceAddDriver = $additionalDriverPrice;
        }
        else {
            $priceAddDriver = 0;
        }




        $pickup = Carbon::parse($booking->pickup_date);
        $return = Carbon::parse($booking->return_date);
        $day = $pickup->diffInDays($return);
        $time = $pickup->diffInHours($return);

        if($checklist->car_driver=="Y") {
        
            $priceDriver = ($DriverPrice)/8;
            $priceDayDriver = ($day*24)*$priceDriver;
            $priceHourDriver = $time*$priceDriver;
            $priceDriverTotal= $priceDayDriver + $priceHourDriver;
        }
        else {
            $priceDriverTotal = 0;
        }
        
        if($checklist->car_out_child_seat=="Y") {
            $priceChildSeat = $day * 5;
        }
        else {
            $priceChildSeat = 0;
        }
        
        $priceFirstRow = $priceAddDriver + $priceDriverTotal;

        $pdf->SetXY(171, 230);
        $pdf->Write(0, "RM ".number_format($priceFirstRow,2));


        if($checklist->car_driver == 'Y') {
        
            $pdf->SetXY(93.6, 230);
            $pdf->Write(0, 'Y');
        }

        $pdf->SetXY(25.3, 235);
        $pdf->Write(0, "Y");


        $cdw_name = $booking->cdwLog->cdw->rate->name ?? null;

        $pdf->SetXY(50.3, 235);
        if($cdw_name == "Bronze") {
            $pdf->Write(0, "CDW ".$cdw_name);
        }
        else {
            $pdf->Write(0, "SCDW ".$cdw_name);
        }

        $cdw_amount = $booking->cdwLog->amount ?? null;

        $pdf->SetXY(171, 235);
        $pdf->Write(0, "RM ".number_format($cdw_amount,2));


        if($checklist->car_out_sticker_p == 'Y') {
        
            $pdf->SetXY(25.3, 239.8);
            $pdf->Write(0, 'Y');
        }

        $pdf->SetXY(171, 239.8);
        $pdf->Write(0, "RM 0.00");


        if($checklist->car_out_usb_charger == 'Y') {
            
            $pdf->SetXY(93.6, 239.8);
            $pdf->Write(0, 'Y');
        }
        
        if($checklist->car_out_touch_n_go == 'Y') {
            
            $pdf->SetXY(25.3, 244.7);
            $pdf->Write(0, 'Y');
        }

        $pdf->SetXY(171, 244.7);
        $pdf->Write(0, 'RM 0.00');
    
        if($checklist->car_out_smart_tag == 'Y') {
            
            $pdf->SetXY(93.6, 244.7);
            $pdf->Write(0, 'Y');
        }
        
        if($checklist->car_out_child_seat == 'Y') {
            
            $pdf->SetXY(93.6, 249.5);
            $pdf->Write(0, 'Y');
        }

        $pdf->SetXY(171, 249.5);
        $pdf->Write(0, "RM ".number_format($priceChildSeat,2));


        $totalall = $priceChildSeat + $priceFirstRow + $cdw_amount;

        $pdf->SetXY(171, 254.5);
        $pdf->Write(0, "RM ".number_format($totalall,2));

        if($booking->p_cost >= 1) {
            
            $pdf->SetXY(25, 208.8);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(60.5, 204.6);
            $pdf->Write(0, $booking->p_address);
            $pdf->SetXY(25, 221.3);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(60.5, 217.1);
            $pdf->Write(0, $booking->p_address);
            $pdf->SetXY(180, 208.8);
            $pdf->Write(0, 'RM '.$booking->p_cost);
        }
        else if($booking->r_cost >= 1) {
            
            $pdf->SetXY(25, 212.9);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(60.5, 204.6);
            $pdf->Write(0, $booking->r_address);
            $pdf->SetXY(25, 225.6);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(60.5, 217.1);
            $pdf->Write(0, $booking->r_address);
            $pdf->SetXY(180, 221.3);
            $pdf->Write(0, 'RM '.$booking->r_cost);
        }
        else {
            
            $pdf->SetXY(25, 204.6);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(60.5, 204.6);
            $pdf->Write(0, $booking->pickupLocation->description);
            $pdf->SetXY(25, 217.1);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(60.5, 217.1);
            $pdf->Write(0, $booking->returnLocation->description);
        }


        if($booking->refund_dep_payment == "Cash") {
        
            $pdf->SetXY(25, 259);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(47, 263.2);
            $pdf->Write(0,'RM '.$booking->refund_dep);
        }
        else if($booking->refund_dep_payment == "Online") {
            
            $pdf->SetXY(25, 263.2); $pdf->Write(0, 'Y');
            $pdf->SetXY(47, 263.2); $pdf->Write(0,'RM '.$booking->refund_dep);
        }
        else if($booking->refund_dep_payment == "Card") {
            
            $pdf->SetXY(25, 267.5);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(47, 263.2);
            $pdf->Write(0,'RM '.$booking->refund_dep);
        }
        else {
            
            $pdf->SetXY(47, 263.2);
            $pdf->Write(0,' ');
        }



        if($booking->payment_type == 'Cash') {
        
            $pdf->SetXY(99.5, 259);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(120, 263.2);
            if($booking->agent_id != '0') {
                $pdf->Write(0, 'RM '.$booking->sub_total." (Paid)");
            }
            else {
                $pdf->Write(0, 'RM '.$booking->est_total." (Paid)");
            }
        }
        else if($booking->payment_type == 'Online') {
            
            $pdf->SetXY(99.5, 263.2);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(126, 263.2);
            
            if($booking->agent_id != '0') {
                $pdf->Write(0, 'RM '.$booking->sub_total." (Paid)");
            }
            else {
                $pdf->Write(0, 'RM '.$booking->est_total." (Paid)");
            }
        }
        else if($booking->payment_type == 'Card') {
            
            $pdf->SetXY(99.5, 267.5);
            $pdf->Write(0, 'Y');
            $pdf->SetXY(126, 263.2);
            
            if($booking->agent_id != '0') {
                $pdf->Write(0, 'RM '.$booking->sub_total." (Paid)");
            }
            else {
                $pdf->Write(0, 'RM '.$booking->est_total." (Paid)");
            }
        }
        else {
            
            if($booking->agent_id != '0') {
                $balancenew = $booking->sub_total - $booking->balance;
                
                $sub_total_display = $booking->sub_total;
            }
            else {
                $balancenew = $booking->est_total - $booking->balance;
                
                $sub_total_display = $booking->est_total;
            }
            
            if($balancenew == '0' || $balancenew < '0') {
                
            // 	$balancenew = "Fully Paid";
                $pdf->SetXY(122, 263.2);
                $pdf->Write(0,$sub_total_display." (Paid)");
            }
            else if($balancenew > '0') {
                
                $balancenew = "(Collect RM ".number_format($balancenew,2).")";
                $pdf->SetXY(120, 263.2);
                $pdf->Write(0,$balancenew);
            }
        }

        if($booking->agent_id != '0') {
        // $total = ($refund_dep + $sub_total + $p_cost + $r_cost);
        $total = ($booking->sub_total + $booking->p_cost + $booking->r_cost + $cdw_amount);
        }
        else {
            // $total = ($refund_dep + $est_total + $p_cost + $r_cost);
            $total = ($booking->est_total + $booking->p_cost + $booking->r_cost + $cdw_amount);
        }

        if($total >= 1) {
            $pdf->SetXY(171, 263.2);
            $pdf->Write(0, 'RM '.number_format($total,2));
        }
        else {
            $pdf->SetXY(171, 263.2); $pdf->Write(0, ' ');
        }

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(5, 276);
        $pdf->Write(0, $user->name);
        $pdf->SetXY(52, 273.5);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->MultiCell(56, 3.5, $fullname, 0, 'L');


        // --- Second Page ---

        $pdf->AddPage();
        $tplIdx = $pdf->importPage(2);
        $pdf->useTemplate($tplIdx);

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(55, 17.5);
        $pdf->Write(0, $fullname);

        $pdf->SetXY(55, 26);
        $pdf->Write(0, $customer->nric_no ?? '');

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(55, 31);
        $pdf->MultiCell(70, 3, $customer->address ?? '');

        $pdf->SetXY(55, 41.5);
        $pdf->Write(0, $customer->postcode ?? '');

        $pdf->SetXY(92, 41.5);
        $pdf->Write(0, $customer->city ?? '');

        $pdf->SetXY(119, 41.5);
        $pdf->Write(0, $customer->country ?? '');

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(153, 32.5);
        $pdf->Write(0, date('d/m/Y', strtotime($booking->pickup_date)));

        $pdf->SetXY(75, 119);
        $pdf->Write(0, date('d/m/Y',strtotime($booking->pickup_date)));
        $pdf->SetXY(125, 119);
        $pdf->Write(0, date('H:i',strtotime($booking->pickup_date)));
        
        $pdf->SetXY(145, 95);
        $pdf->Write(0, $vehicle->reg_no ?? '');

        $pdf->SetXY(49, 157);
        $pdf->Write(0, $vehicle->reg_no);


        // Output PDF
        return response()->streamDownload(function () use ($pdf, $booking) {
            $pdf->Output();
        }, 'Agreement_' . $booking->agreement_no . '.pdf');
    }
}
