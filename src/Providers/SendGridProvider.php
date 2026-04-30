<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class SendGridProvider extends AbstractProvider
{
    /**
     * SendGrid Event Webhook signature verification using ECDSA SHA-256.
     * The webhook_secret should be the PEM-format public key from the
     * SendGrid dashboard (Settings → Mail Settings → Event Webhook).
     *
     * Verification is opt-in: when no public key is configured, all requests pass.
     *
     * https://docs.sendgrid.com/for-developers/tracking-events/getting-started-event-webhook-security-features
     */
    public function verify(Request $request): bool
    {
        if (empty($this->secret)) {
            return true;
        }

        $signature = $request->header('X-Twilio-Email-Event-Webhook-Signature');
        $timestamp = $request->header('X-Twilio-Email-Event-Webhook-Timestamp');

        if (! $signature || ! $timestamp) {
            return false;
        }

        $payload = $timestamp.$request->getContent();

        return openssl_verify($payload, base64_decode($signature), $this->secret, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * SendGrid sends a JSON array of events per request.
     * Returns an event-type => first-event-of-that-type map for multi-dispatch.
     */
    public function getEvent(Request $request): string|array
    {
        $events = json_decode($request->getContent(), true) ?? [];

        $result = [];
        foreach ($events as $event) {
            $type = $event['event'] ?? null;
            if ($type && ! isset($result[$type])) {
                $result[$type] = $event;
            }
        }

        return $result ?: '';
    }

    public function getData(Request $request): array
    {
        return json_decode($request->getContent(), true) ?? [];
    }
}
