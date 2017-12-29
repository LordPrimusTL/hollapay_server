<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportEmail extends Mailable {

    use Queueable,
        SerializesModels;

    public $name;
    public $subject;
    public $m;
    public $phone;

    public function __construct($user, $subject, $message) {
        $this->name = $user->name;
        $this->subject = $subject;
        $this->m = $message;
        $this->phone = $user->user_id;
    }

    public function build() {
        return $this->view('emails.support');
    }

}
