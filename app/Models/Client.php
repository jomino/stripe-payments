<?php

namespace App\Models;

class Client extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'stripe_clients';
    protected $fillable = ['active','uuid','pwd','name','email'];
    protected $casts = [
        'active' => 'integer',
        'uuid' => 'string',
        'pwd' => 'string',
        'name' => 'string',
        'email' => 'string'
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

}
