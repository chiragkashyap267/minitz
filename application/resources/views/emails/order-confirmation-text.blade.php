MINITZ — ORDER CONFIRMED
Hello {{ $order->name }},
We have received your payment for order MZ-{{ str_pad($order->id,5,'0',STR_PAD_LEFT) }}.

@foreach(json_decode($order->items,true)?:[] as $item)
{{ $item['name'] }} × {{ $item['quantity'] }} — {{ mz_money($item['price']*$item['quantity']) }}
@endforeach

Subtotal: {{ mz_money($order->subtotal) }}
Delivery: {{ $order->shipping?mz_money($order->shipping):'Included' }}
@if($order->discount)Discount: −{{ mz_money($order->discount) }}@endif
Paid: {{ mz_money($order->total) }}

Delivery address:
{{ $order->name }}
{{ $order->address }}

View your order: {{ rtrim(config('app.url'),'/') }}/orders/{{ $order->token }}
Reply to this email if you need help.
This is an order confirmation, not a tax invoice.
