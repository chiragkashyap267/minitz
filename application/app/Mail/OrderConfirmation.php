<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
class OrderConfirmation extends Mailable {
 public function __construct(public object $order){}
 public function envelope():Envelope {return new Envelope(subject:'Minitz order MZ-'.str_pad($this->order->id,5,'0',STR_PAD_LEFT).' confirmed');}
 public function content():Content {return new Content(view:'emails.order-confirmation',text:'emails.order-confirmation-text',with:['orderUrl'=>\App\Services\OrderLinks::email($this->order)]);}
}
