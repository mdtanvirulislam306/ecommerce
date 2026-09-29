@extends('ecommerce::mail.layout')

@section('title', 'New order '.$order['number'])
@section('preheader', $order['customer_name'].' ordered '.$order['items_count'].' item(s) for '.$order['totals']['grand_total'].'.')

@section('content')
    <span style="display:inline-block; padding:4px 12px; border-radius:999px; background-color:#FEF1EA; color:#C2551F; font-size:12px; font-weight:700; letter-spacing:0.3px;">
        New order · {{ $order['number'] }}
    </span>

    <p style="margin:18px 0 0 0; font-size:13px; font-weight:600; color:#6B7280;">You just made a sale</p>
    <h1 class="headline" style="margin:4px 0 6px 0; font-size:34px; line-height:40px; font-weight:800; color:#2C4B60; letter-spacing:-0.6px;">
        {{ $order['totals']['grand_total'] }}
    </h1>
    <p style="margin:0; font-size:15px; line-height:24px; color:#4B5563;">
        {{ $order['customer_name'] }} ordered {{ $order['items_count'] }} {{ $order['items_count'] === 1 ? 'item' : 'items' }}
        · {{ $order['payment_method'] }}{{ $order['is_paid'] ? ' (paid)' : '' }} · {{ $order['placed_at'] }}
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 28px 0;">
        <tr>
            <td style="border-radius:12px; background-color:#2C4B60;">
                <a href="{{ $order['admin_url'] }}" style="display:inline-block; padding:13px 26px; font-size:15px; font-weight:700; color:#FFFFFF; text-decoration:none; border-radius:12px;">
                    Review &amp; confirm order &rarr;
                </a>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
        <tr>
            <td class="stack" width="50%" style="padding-right:12px; vertical-align:top;">
                <div style="background-color:#F9FAFB; border-radius:14px; padding:16px 18px;">
                    <p style="margin:0 0 6px 0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#9CA3AF;">Customer</p>
                    <p style="margin:0; font-size:14px; line-height:21px; color:#374151;">
                        <strong style="color:#1F2937;">{{ $order['customer_name'] }}</strong><br>
                        @if ($order['customer_phone'])
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $order['customer_phone']) }}" style="color:#1F8A8C; text-decoration:none;">{{ $order['customer_phone'] }}</a><br>
                        @endif
                        @if ($order['customer_email'])
                            <a href="mailto:{{ $order['customer_email'] }}" style="color:#1F8A8C; text-decoration:none;">{{ $order['customer_email'] }}</a>
                        @endif
                    </p>
                </div>
            </td>
            <td class="stack" width="50%" style="padding-left:12px; vertical-align:top;">
                <div style="background-color:#F9FAFB; border-radius:14px; padding:16px 18px;">
                    <p style="margin:0 0 6px 0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#9CA3AF;">
                        Deliver to @if ($order['delivery_zone'])· {{ $order['delivery_zone'] }}@endif
                    </p>
                    <p style="margin:0; font-size:14px; line-height:21px; color:#374151;">{!! nl2br(e($order['shipping_address'])) !!}</p>
                </div>
            </td>
        </tr>
    </table>

    @if ($order['notes'])
        <div style="margin-bottom:24px; border-left:3px solid #F27D42; background-color:#FFF8F3; border-radius:0 12px 12px 0; padding:12px 16px;">
            <p style="margin:0 0 4px 0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#C2551F;">Customer note</p>
            <p style="margin:0; font-size:14px; line-height:21px; color:#374151;">{!! nl2br(e($order['notes'])) !!}</p>
        </div>
    @endif

    <p style="margin:0 0 4px 0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#9CA3AF;">Items</p>
    @include('ecommerce::mail.partials.items')
@endsection

@section('footer')
    <p style="margin:0;">You're receiving this because new-order alerts are turned on for {{ $shop['name'] }}. Change this in Ecommerce &rsaquo; Order Notifications.</p>
@endsection
