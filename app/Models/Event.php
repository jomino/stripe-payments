<?php

namespace App\Models;

class Event extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'stripe_events';

    protected $fillable = [
        'status',
        'uuid',
        'name',
        'email',
        'amount',
        'product',
        'method',
        'token',
        'skey',
        'ckey'
    ];

    protected $casts = [
        'status' => 'string',
        'uuid' => 'string',
        'name' => 'string',
        'email' => 'string',
        'amount' => 'integer',
        'product' => 'string',
        'method' => 'string',
        'token' => 'string',
        'skey' => 'string', // token: source.id [src_xxx...] or payment_intent.id [pi_xxx...]
        'ckey' => 'string' // token: charge.id [py_xxx...]
    ];
}