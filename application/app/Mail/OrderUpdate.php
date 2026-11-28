<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
class OrderUpdate extends Mailable {
 public const TEXT=[
 'payment_failed'=>['Payment attempt unsuccessful','Your payment attempt was unsuccessful. Your order is not confirmed yet. You can retry payment from your order page while it is still open.'],
 'expired'=>['Payment window closed','Your unpaid order has expired and its reserved stock has been released. If you still want these products, place a fresh order. Contact us if your bank shows a debit.'],
 'cancel_requested'=>['Cancellation request received','We have received your cancellation request. The team will review it before dispatch.'],
 'cancel_rejected'=>['Cancellation request update','The team could not approve your cancellation request. See the note below or contact support.'],
 'cancelled'=>['Order cancelled','Your order has been cancelled. If payment was captured, its refund is tracked separately; this message does not confirm a completed refund.'],
 'processing'=>['We are preparing your order','The team is preparing your order for dispatch.'],
 'shipped'=>['Your order has shipped','Your parcel has been dispatched. Use the courier tracking link below for updates.'],
 'delivered'=>['Order marked delivered','Your order has been marked delivered. Contact our team if you have not received it.'],
 'return_requested'=>['Return request received','Your return request has been received. Please wait for the team to review it before sending the product back.'],
 'return_approved'=>['Return request approved','Your return has been approved. Follow the instructions below and coordinate the return with our team. Refund processing starts after the returned items are received and checked.'],
 'return_rejected'=>['Return request update','The team could not approve your return request. See their note below.'],
 'return_received'=>['Returned items received','The team has confirmed receipt of your returned items. Your full refund has been queued for processing.'],
 'refund_pending'=>['Refund initiated','A full refund has been submitted to the payment provider. It has not been confirmed as completed yet. Your bank may take additional time to reflect the credit.'],
 'refunded'=>['Refund processed','The payment provider has confirmed that your full refund was processed. The time for the credit to appear depends on your bank.'],
 'refund_failed'=>['Refund needs attention','The payment provider reported that the refund could not be processed. Our team needs to follow up. Please contact us with your order number.'],
 'payment_review'=>['Payment received after order closure','A payment was confirmed after this order had closed. We will not dispatch this closed order. A full refund has been queued for processing.'],
 ];
 public function __construct(public object $order,public string $event,public array $extra=[]){}
 public function envelope():Envelope {return new Envelope(subject:'Minitz MZ-'.str_pad($this->order->id,5,'0',STR_PAD_LEFT).' — '.(self::TEXT[$this->event][0]??'Order update'));}
 public function content():Content {return new Content(view:'emails.order-update',text:'emails.order-update-text',with:['orderUrl'=>\App\Services\OrderLinks::email($this->order),'heading'=>self::TEXT[$this->event][0]??'Order update','updateText'=>self::TEXT[$this->event][1]??'Open your order for the latest update.']);}
}
