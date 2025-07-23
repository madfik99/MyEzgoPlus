<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    // Table name (if not default plural form)
    protected $table = 'company';

    // Primary Key
    protected $primaryKey = 'id';

    // No auto-incrementing or timestamps by default for your schema:
    public $incrementing = true;
    public $timestamps = false;

    // Mass-assignable fields (safe to use with $company->fill([...]))
    protected $fillable = [
        'company_name',
        'website_name',
        'registration_no',
        'address',
        'phone_no',
        'image',
        'email',
        'mid',
        'modified',
        'cid',
        'created',
    ];

    // Optional: Date casting for datetime columns
    protected $dates = [
        'modified',
        'created',
    ];

    // If you want to automatically handle created_at/updated_at, set these to match your columns (else leave as is)
    // const CREATED_AT = 'created';
    // const UPDATED_AT = 'modified';
}
