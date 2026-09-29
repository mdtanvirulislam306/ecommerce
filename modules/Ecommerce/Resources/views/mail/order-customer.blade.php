@extends('ecommerce::mail.layout')

@php
    $badge = match ($event) {
        'confirmed' => ['label' => 'Confirmed', 'bg' => '#E6F7F7', 'fg' => '#1F8A8C'],
        'cancelled' => ['label' => 'Cancelled', 'bg' => '#FEF2F2', 'fg' => '#B91C1C'],
        default => ['label' => 'Order received', 'bg' => '#FEF1EA', 'fg' => '#C2551F'],
    };
@endphp

@section('title', $headline)
@section('preheader', $intro)

@section('content')
    <span style="display:inline-block; padding:4px 12px; border-radius:999px; background-color:{{ $badge['bg'] }}; color:{{ $badge['fg'] }}; font-size:12px; font-weight:700; letter-spacing:0.3px;">
        {{ $badge['label'] }}
    </span>

    <h1 class="headline" style="margin:16px 0 8px 0; font-size:26px; line-height:32px; font-weight:800; color:#2C4B60; letter-spacing:-0.4px;">
        {{ $headline }}
    </h1>
    <p style="margin:0; font-size:15px; line-height:24px; color:#4B5563;">{{ $intro }}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0; background-color:#F9FAFB; border-radius:14px;">
        <tr>
            <td class="stack" width="33%" style="padding:16px 18px; vertical-align:top;">
                <p style="margin:0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#9CA3AF;">Order</p>
                <p style="margin:4px 0 0 0; font-size:14px; font-weight:700; color:#2C4B60;">{{ $order['number'] }}</p>
            </td>
            <td class="stack" width="33%" style="padding:16px 18px; vertical-align:top;">
                <p style="margin:0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#9CA3AF;">Placed on</p>
                <p style="margin:4px 0 0 0; font-size:14px; color:#374151;">{{ $order['placed_at'] }}</p>
            </td>
            <td class="stack" width="34%" style="padding:16px 18px; vertical-align:top;">
                <p style="margin:0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#9CA3AF;">Total</p>
                <p style="margin:4px 0 0 0; font-size:14px; font-weight:700; color:#2C4B60;">{{ $order['totals']['grand_total'] }}</p>
            </td>
        </tr>
    </table>

    @if ($order['tracking_url'])
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px 0;">
            <tr>
                <td style="border-radius:12px; background-color:{{ $event === 'cancelled' ? '#2C4B60' : '#F27D42' }};">
                    <a href="{{ $order['tracking_url'] }}" style="display:inline-block; padding:13px 26px; font-size:15px; font-weight:700; color:#FFFFFF; text-decoration:none; border-radius:12px;">
                        {{ $event === 'cancelled' ? 'View order details' : 'Track your order' }} &rarr;
                    </a>
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:0 0 4px 0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#9CA3AF;">Order summary</p>
    @include('ecommerce::mail.partials.items')

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
        <tr>
            <td class="stack" width="50%" style="padding-right:12px; vertical-align:top;">
                <div style="border:1px solid #E5E7EB; border-radius:14px; padding:16px 18px;">
                    <p style="margin:0 0 6px 0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#9CA3AF;">Delivery to</p>
                    <p style="margin:0; font-size:14px; line-height:21px; color:#374151;">
                        <strong style="color:#1F2937;">{{ $order['customer_name'] }}</strong><br>
                        @if ($order['customer_phone']){{ $order['customer_phone'] }}<br>@endif
                        {!! nl2br(e($order['shipping_address'])) !!}
                    </p>
                </div>
            </td>
            <td class="stack" width="50%" style="padding-left:12px; vertical-align:top;">
                <div style="border:1px solid #E5E7EB; border-radius:14px; padding:16px 18px;">
                    <p style="margin:0 0 6px 0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#9CA3AF;">Payment</p>
                    <p style="margin:0; font-size:14px; line-height:21px; color:#374151;">
                        <strong style="color:#1F2937;">{{ $order['payment_method'] }}</strong><br>
                        @if ($event === 'cancelled')
                            Nothing to pay for this order.
                        @elseif ($order['is_paid'])
                            Paid in full — nothing to pay on delivery.
                        @elseif ($order['is_cash_on_delivery'])
                            Please keep {{ $order['totals']['grand_total'] }} ready when your parcel arrives.
                        @endif
                    </p>
                </div>
            </td>
        </tr>
    </table>
@endsection

@section('footer')
    <p style="margin:0;">
        @if ($shop['support_email'])
            Questions about your order? Just reply to this email or write to
            <a href="mailto:{{ $shop['support_email'] }}" style="color:#6B7280;">{{ $shop['support_email'] }}</a>.
        @else
            Questions about your order? Just reply to this email.
        @endif
    </p>
    <p style="margin:6px 0 0 0;">You're receiving this because you placed an order at {{ $shop['name'] }}.</p>
@endsection
