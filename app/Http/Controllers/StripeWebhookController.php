<?php

namespace App\Http\Controllers;

use App\Http\Handlers\PaymentSucceededWebhookHandler;
use Exception;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    // In case if we need to use webhooks in future, we can define them here
    public static function getWebhooks()
    {
        return [
            [
                'name' => 'invoice.payment_succeeded',
                'handler' => PaymentSucceededWebhookHandler::class,
                'callback' => \secure_url('/api/webhooks'),
            ],
        ];
    }

    public function __invoke(Request $request)
    {
        $this->validateSecretKey($request);
        $payload = $request->getContent();
        $event = json_decode($payload, true);

        $type = $event['type'] ?? null;
        $handler = $this->getHandlerForEvent($type ?? '');
        if ($handler) {
            app($handler)->handle($event);
        } else {
            logger()->info('Unhandled webhook event type.', ['event' => $type ?? '']);
        }

        return response()->json(['status' => 'success']);

    }

    protected function validateSecretKey(Request $request)
    {
        if (empty($request->getContent())) {
            throw new Exception('No content received in the webhook request.');
        }
        $sigHeader = $request->header('Stripe-Signature');
        $payload = $request->getContent();
        $secret = config('cashier.webhook.secret');

        try {
            Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            throw new Exception("Webhook signature verification failed: {$e->getMessage()}");
        }
    }

    protected function getHandlerForEvent(string $eventType)
    {
        $webhooks = self::getWebhooks();
        foreach ($webhooks as $webhook) {
            if ($webhook['name'] === $eventType) {
                return $webhook['handler'];
            }
        }

        return null;
    }
}
