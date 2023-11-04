<?php

namespace App\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $table = 'adm_users';

    protected $fillable = [
        'username', 'password', 'realname', 'email', 'branch', 'division', 'id_number',
    ];

    protected $primaryKey = 'id_number';

    public function getJWTIdentifier()
    {
        return 'id_number';
    }

    public function getJWTCustomClaims()
    {
        return [
            'id_number' => $this->id_number,
        ];
    }
}
