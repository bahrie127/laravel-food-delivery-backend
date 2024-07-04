<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [

        'order_id',
        'payment_type',
        'payment_provider',
        'amount',
        'status',
        'xendit_id',
    ];
}
