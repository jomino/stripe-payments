<?php

namespace App\Models;

class User extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'stripe_users';
    protected $fillable = ['active','name','email','pkey','skey','uuid'];
    protected $casts = [
        'active' => 'integer',
        'uuid' => 'string',
        'name' => 'string',
        'email' => 'string',
        'pkey' => 'string',
        'skey' => 'string'
    ];
}
