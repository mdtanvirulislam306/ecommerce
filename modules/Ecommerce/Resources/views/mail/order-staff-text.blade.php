New order {{ $order['number'] }}: {{ $order['totals']['grand_total'] }}

{{ $order['customer_name'] }} ordered {{ $order['items_count'] }} item(s) on {{ $order['placed_at'] }}.
Payment: {{ $order['payment_method'] }}{{ $order['is_paid'] ? ' (paid, ref '.$order['payment_reference'].')' : '' }}

Customer:
{{ $order['customer_name'] }}
@if ($order['customer_phone'])
{{ $order['customer_phone'] }}
@endif
@if ($order['customer_email'])
{{ $order['customer_email'] }}
@endif

Deliver to{{ $order['delivery_zone'] ? ' ('.$order['delivery_zone'].')' : '' }}:
{{ $order['shipping_address'] }}
@if ($order['notes'])

Customer note: {{ $order['notes'] }}
@endif

@foreach ($order['items'] as $item)
- {{ $item['name'] }} ({{ $item['quantity'] }} x {{ $item['unit_price'] }}): {{ $item['line_total'] }}
@endforeach

Subtotal: {{ $order['totals']['subtotal'] }}
@if ($order['totals']['discount'])
Coupon discount{{ $order['totals']['coupon_code'] ? ' ('.$order['totals']['coupon_code'].')' : '' }}: -{{ $order['totals']['discount'] }}
@endif
Delivery: {{ $order['totals']['shipping'] ?? 'Free' }}
Total: {{ $order['totals']['grand_total'] }}

Review and confirm: {{ $order['admin_url'] }}
