<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Tenant extends Model
{
    use Auditable;
    protected $fillable = [
        'name',
        'tenant_type',
        'id_cr_number',
        'phone',
        'email',
        'nationality_country',
    ];
}
