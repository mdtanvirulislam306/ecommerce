<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\UpdateOrderNotificationSettingsRequest;
use Modules\Ecommerce\Mail\NewOnlineOrderMail;
use Modules\Ecommerce\Mail\OnlineOrderCustomerMail;
use Modules\Ecommerce\Models\OnlineOrder;
use Modules\Ecommerce\Services\OrderNotificationService;

class OrderNotificationSettingsController extends Controller
{
    public const PREVIEWS = ['customer-placed', 'customer-confirmed', 'customer-cancelled', 'staff-placed'];

    public function index(OrderNotificationService $notifications): Response
    {
        return Inertia::render('Ecommerce/Notifications/Index', [
            'settings' => $notifications->preferences(),
            'ownerEmail' => $notifications->staffEmail(),
            'smsLive' => config('services.sms.driver') !== 'log',
            'hasOrders' => OnlineOrder::query()->exists(),
        ]);
    }

    public function update(UpdateOrderNotificationSettingsRequest $request, OrderNotificationService $notifications): RedirectResponse
    {
        $notifications->savePreferences($request->validated());

        return back()->with('success', 'Notification settings saved.');
    }

    public function preview(string $template, OrderNotificationService $notifications): HttpResponse
    {
        $order = OnlineOrder::query()->with('items')->latest('id')->first() ?? $notifications->sampleOrder();
        $snapshot = $notifications->snapshot($order);
        $shop = $notifications->shop();

        $mailable = $template === 'staff-placed'
            ? new NewOnlineOrderMail($snapshot, $shop)
            : new OnlineOrderCustomerMail($snapshot, $shop, str_replace('customer-', '', $template));

        return response($mailable->render());
    }
}
