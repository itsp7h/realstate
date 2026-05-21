<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'tenant_type',
        'id_cr_number',
        'phone',
        'email',
        'nationality_country',
    ];
}
