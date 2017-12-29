<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model {

    use Notifiable;
    
    protected $table = 'transaction_log';
    
    public function transaction() {
        return $this->belongsTo('App\Transactions', 'id', 'transaction_id');
    }

}
