<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tax_group_detail extends Model
{
    protected $table = 'tax_group_details';

    protected $fillable = [
        'tax_group_id',
        'tax_master_id',
        'sequence',
    ];
}
