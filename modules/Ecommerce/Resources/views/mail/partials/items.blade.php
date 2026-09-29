<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
    @foreach ($order['items'] as $item)
        <tr>
            <td style="padding:14px 0; border-bottom:1px solid #F3F4F6; font-size:14px; line-height:20px; vertical-align:top;">
                <span style="color:#1F2937; font-weight:600;">{{ $item['name'] }}</span><br>
                <span style="color:#9CA3AF; font-size:12px;">
                    {{ $item['quantity'] }} × {{ $item['unit_price'] }}@if ($item['sku']) &nbsp;·&nbsp; {{ $item['sku'] }}@endif
                </span>
            </td>
            <td align="right" style="padding:14px 0 14px 16px; border-bottom:1px solid #F3F4F6; font-size:14px; line-height:20px; color:#1F2937; font-weight:600; white-space:nowrap; vertical-align:top;">
                {{ $item['line_total'] }}
            </td>
        </tr>
    @endforeach
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:12px; font-size:14px; line-height:20px;">
    <tr>
        <td style="padding:4px 0; color:#6B7280;">Subtotal</td>
        <td align="right" style="padding:4px 0; color:#374151;">{{ $order['totals']['subtotal'] }}</td>
    </tr>
    @if ($order['totals']['discount'])
        <tr>
            <td style="padding:4px 0; color:#059669;">
                Coupon discount @if ($order['totals']['coupon_code'])<span style="display:inline-block; margin-left:4px; padding:1px 8px; border-radius:999px; background-color:#ECFDF5; font-size:11px; font-weight:700; letter-spacing:0.4px;">{{ $order['totals']['coupon_code'] }}</span>@endif
            </td>
            <td align="right" style="padding:4px 0; color:#059669;">−{{ $order['totals']['discount'] }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding:4px 0; color:#6B7280;">
            Delivery @if ($order['delivery_zone'])<span style="color:#9CA3AF;">· {{ $order['delivery_zone'] }}</span>@endif
        </td>
        <td align="right" style="padding:4px 0; color:#374151;">
            @if ($order['totals']['shipping']){{ $order['totals']['shipping'] }}@else<span style="color:#059669; font-weight:600;">Free</span>@endif
        </td>
    </tr>
    <tr>
        <td style="padding:14px 0 0 0; border-top:1px solid #E5E7EB; color:#2C4B60; font-size:16px; font-weight:700;">Total</td>
        <td align="right" style="padding:14px 0 0 0; border-top:1px solid #E5E7EB; color:#2C4B60; font-size:20px; font-weight:800;">{{ $order['totals']['grand_total'] }}</td>
    </tr>
</table>
