<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    protected $table = 'checklist';
    public $timestamps = false;

    protected $fillable = [
        'booking_trans_id',
        'car_in_start_engine', 'car_in_engine_condition', 'car_in_test_gear', 'car_in_no_alarm',
        'car_in_air_conditioner', 'car_in_radio', 'car_in_wiper', 'car_in_window_condition',
        'car_in_power_window', 'car_in_perfume', 'car_in_carpet', 'car_in_sticker_p', 'car_in_jack',
        'car_in_tools', 'car_in_signage', 'car_in_tyre_spare', 'car_in_child_seat', 'car_in_lamp',
        'car_in_tyres_condition', 'car_in_image', 'car_in_sign_image', 'car_in_gps', 'car_in_usb_charger',
        'car_in_touch_n_go', 'car_in_smart_tag', 'car_in_seat_condition', 'car_in_cleanliness', 'car_in_fuel_level',
        'car_in_remark', 'car_in_return_person_nric_no', 'car_in_return_person_name',
        'car_in_return_person_relationship', 'car_in_checkby', 'car_in_mileage',

        'car_out_start_engine', 'car_out_engine_condition', 'car_out_test_gear', 'car_out_no_alarm',
        'car_out_air_conditioner', 'car_out_radio', 'car_out_wiper', 'car_out_window_condition',
        'car_out_power_window', 'car_out_perfume', 'car_out_carpet', 'car_out_sticker_p', 'car_out_jack',
        'car_out_tools', 'car_out_signage', 'car_out_tyre_spare', 'car_out_child_seat', 'car_out_lamp',
        'car_out_tyres_condition', 'car_out_gps', 'car_out_image', 'car_out_sign_image', 'car_out_usb_charger',
        'car_out_touch_n_go', 'car_out_smart_tag', 'car_out_seat_condition', 'car_out_cleanliness', 'car_out_fuel_level',
        'car_out_remark', 'car_out_checkby', 'car_out_mileage', 'car_add_driver', 'car_driver'
    ];

    // Relationship to BookingTrans
    public function booking()
    {
        return $this->belongsTo(BookingTrans::class, 'booking_trans_id');
    }
}
