<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLog extends Model
{
    protected $table = 'journal_log';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'title',   // default 'Pickup' at DB level
        'cid',
        'created',
    ];

    protected $casts = [
        'created' => 'datetime',
    ];

    // If you have a Sale model, keep this. Otherwise, comment it out.
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
