<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable {

    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'updated_at', 'created_at', 'role'
    ];

    public function verificationCode() {
        return $this->hasMany('App\VerificationCodes', 'user_id', 'user_id');
    }

    public function token() {
        return $this->hasMany('App\Tokens', 'id', 'user_id');
    }

}
