<?php

namespace App\Models;

class User extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'stripe_users';
    protected $fillable = ['active','uuid','name','email','pkey','skey','wkey'];
    protected $casts = [
        'active' => 'integer',
        'uuid' => 'string',
        'name' => 'string',
        'email' => 'string',
        'pkey' => 'string',
        'skey' => 'string',
        'wkey' => 'string'
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

}
