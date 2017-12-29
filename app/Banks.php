<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Banks extends Model
{
    use Notifiable;
    
        protected $hidden = [
            'updated_at', 
            'created_at', 
            'role'
            ];
        
    protected $fillable = [
        'name', 'email', 'password',
    ];

}
