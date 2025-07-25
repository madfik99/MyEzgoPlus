<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customer';

    public $timestamps = false; // using `cdate`, `mdate` instead

    protected $fillable = [
        'title', 'firstname', 'lastname', 'dob', 'race', 'gender',
        'nric_type', 'nric_no', 'license_no', 'license_exp',
        'identity_photo_front', 'identity_photo_back', 'utility_photo', 'working_photo',
        'country_code', 'phone_no', 'phone_no2', 'email', 'email_verify', 'address',
        'postcode', 'city', 'country', 'status', 'account_no', 'account_bank',
        'account_name', 'ref_name', 'ref_phoneno', 'age', 'image',
        'drv_name', 'drv_nric', 'drv_address', 'drv_phoneno', 'drv_license_no', 'drv_license_exp',
        'gl_code', 'ref_relationship', 'ref_address', 'reason_blacklist', 'date_blacklist',
        'cid_blacklist', 'password', 'reset_password', 'survey_type', 'survey_details',
        'otp_code', 'otp_match', 'otp_type', 'otp_time',
        'provider_id', 'provider', 'type', 'affliliate_status', 'affiliate_id',
        'remark', 'cid', 'cdate', 'mid', 'mdate'
    ];

    // Relationships
    public function bookings()
    {
        return $this->hasMany(BookingTrans::class);
    }

    public function uploads()
    {
        return $this->hasMany(UploadData::class,'customer_id');
    }

    public function nricSelfieImage()
    {
        return $this->hasOne(UploadData::class, 'customer_id')->where('no', 1);
    }

    
}
