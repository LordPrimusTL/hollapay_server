<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Transactions extends Model {

    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
    ];
    protected $hidden = [
        'updated_at',
        'created_at'
    ];

    public function transactionLog() {
        return $this->hasMany('App\TransactionLog', 'transaction_id', 'id');
    }
    
    public function user() {
        return $this->belongsTo('App\User', 'user_id', 'user_id');
    }
    
    public function beneficiary() {
        return $this->belongsTo('App\User', '_to', 'user_id');
    }
    
    public function sender() {
        return $this->belongsTo('App\User', '_from', 'user_id');
    }

}
