<?php

namespace App\Models;

class User extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'users';
    protected $fillable = ['name','email','password'];
    protected $casts = [
        'name' => 'string',
        'email' => 'string',
        'password' => 'string',
    ];
}
