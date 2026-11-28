MINITZ — {{ $heading }}
Hello {{ $order->name }},
{{ $updateText }}
Order MZ-{{ str_pad($order->id,5,'0',STR_PAD_LEFT) }} · {{ mz_money($order->total) }}
{{ $extra['note']??'' }}
@if(!empty($extra['refund_id']))Refund reference: {{ $extra['refund_id'] }}@endif
@if(!empty($extra['tracking']))Tracking number: {{ $extra['tracking'] }}@endif
@if(!empty($extra['tracking_url']) && \App\Services\ShipmentTracking::safeUrl($extra['tracking_url']))Track parcel: {{ $extra['tracking_url'] }}@endif
View order: {{ $orderUrl }}
This private order link works for 14 days. Keep it private. You can also sign in to your customer account.
Support: {{ rtrim(config('app.url'),'/') }}/pages/contact
