<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EwaBillImportBatch extends Model
{
    protected $fillable = ['batch_id', 'rows'];

    protected $casts = [
        'rows' => 'array',
    ];
}
