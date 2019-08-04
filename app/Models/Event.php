<?php

namespace App\Models;

class Event extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'stripe_events';
    protected $fillable = ['status','uuid','name','email','amount','skey','ckey'];
    protected $casts = [
        'status' => 'string',
        'uuid' => 'string',
        'name' => 'string',
        'email' => 'string',
        'amount' => 'integer',
        'skey' => 'string',
        'ckey' => 'string'
    ];
}