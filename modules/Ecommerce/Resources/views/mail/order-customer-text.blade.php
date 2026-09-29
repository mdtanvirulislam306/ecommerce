{{ $headline }}

{{ $intro }}

Order: {{ $order['number'] }}
Placed on: {{ $order['placed_at'] }}

@foreach ($order['items'] as $item)
- {{ $item['name'] }} ({{ $item['quantity'] }} x {{ $item['unit_price'] }}): {{ $item['line_total'] }}
@endforeach

Subtotal: {{ $order['totals']['subtotal'] }}
@if ($order['totals']['discount'])
Coupon discount{{ $order['totals']['coupon_code'] ? ' ('.$order['totals']['coupon_code'].')' : '' }}: -{{ $order['totals']['discount'] }}
@endif
Delivery{{ $order['delivery_zone'] ? ' ('.$order['delivery_zone'].')' : '' }}: {{ $order['totals']['shipping'] ?? 'Free' }}
Total: {{ $order['totals']['grand_total'] }}

Delivery to:
{{ $order['customer_name'] }}
@if ($order['customer_phone'])
{{ $order['customer_phone'] }}
@endif
{{ $order['shipping_address'] }}

Payment: {{ $order['payment_method'] }}{{ $order['is_paid'] ? ' (paid in full)' : '' }}
@if ($order['tracking_url'])

{{ $event === 'cancelled' ? 'View order details' : 'Track your order' }}: {{ $order['tracking_url'] }}
@endif

Questions? Reply to this email{{ $shop['support_email'] ? ' or write to '.$shop['support_email'] : '' }}.
{{ $shop['name'] }} - {{ $shop['url'] }}
