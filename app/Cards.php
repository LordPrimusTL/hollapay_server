<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class Cards extends Model {

    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
    ];
    protected $hidden = [
        'updated_at',
        'created_at'
    ];

}
