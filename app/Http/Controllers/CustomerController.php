<?php

namespace App\Http\Controllers;


use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    

    public function lookupByNric(Request $request)
    {
        $nric = trim((string) $request->query('nric_no', ''));
        if ($nric === '') {
            return response()->json(['found' => false]);
        }

        $c = Customer::where('nric_no', $nric)->first();

        if (!$c) {
            return response()->json(['found' => false]);
        }

        // Optional: if blacklisted, you might want to block immediately
        if ($c->status === 'B') {
            return response()->json([
                'found' => true,
                'blacklisted' => true,
                'reason_blacklist' => $c->reason_blacklist ?? null,
            ]);
        }

        // Limit to fields you actually show on the form
        return response()->json([
            'found' => true,
            'blacklisted' => false,
            'customer' => [
                'nric_type'        => $c->nric_type,
                'title'            => $c->title,
                'firstname'        => $c->firstname,
                'lastname'         => $c->lastname,
                'gender'           => $c->gender,
                'race'             => $c->race,
                'dob'              => optional($c->dob)->format('Y-m-d'),
                'phone_no'         => $c->phone_no,
                'phone_no2'        => $c->phone_no2,
                'email'            => $c->email,
                'license_no'       => $c->license_no,
                'license_exp'      => $c->license_exp ? \Carbon\Carbon::parse($c->license_exp)->format('Y-m-d') : null,
                'address'          => $c->address,
                'postcode'         => $c->postcode,
                'city'             => $c->city,
                'country'          => $c->country,
                'ref_relationship' => $c->ref_relationship,
                'ref_name'         => $c->ref_name,
                'ref_address'      => $c->ref_address,
                'ref_phoneno'      => $c->ref_phoneno,
                'survey_type'      => $c->survey_type,
                'survey_details'   => $c->survey_details,
            ],
        ]);
    }


}