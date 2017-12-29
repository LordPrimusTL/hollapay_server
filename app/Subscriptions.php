<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class Subscriptions extends Model {

    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
    ];
}
