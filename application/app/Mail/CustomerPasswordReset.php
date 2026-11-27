<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content,Envelope};
class CustomerPasswordReset extends Mailable {
 public function __construct(public string $resetUrl){}
 public function envelope():Envelope {return new Envelope(subject:'Reset your Minitz password');}
 public function content():Content {return new Content(view:'emails.password-reset',text:'emails.password-reset-text');}
}
