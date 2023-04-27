<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Auth;

class AuthModel extends Model
{
    use HasFactory;

    // protected $table = 'users';

    protected $fillable = [
        'username', 'email', 'password', 'remember_token'
    ];
}
