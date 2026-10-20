<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class MailtrapProvider extends AbstractProvider
{
    /**
     * Mailtrap signs every webhook request (inbound email and sending events)
     * with a hex HMAC-SHA256 of the raw body, sent in the Mailtrap-Signature header.
     * The webhook_secret is the signing secret from the webhook's details page.
     *
     * https://docs.mailtrap.io/email-api-smtp/advanced/webhooks
     */
    public function verify(Request $request): bool
    {
        if (empty($this->secret)) {
            return false;
        }

        $signature = hash_hmac('sha256', $request->getContent(), $this->secret);

        return hash_equals($signature, (string) $request->header('Mailtrap-Signature'));
    }

    /**
     * Mailtrap batches events into a single request: {"events": [...]}.
     * Returns an event-type => list-of-events map, so every event in the
     * batch reaches its handler (e.g. several inbound emails at once).
     */
    public function getEvent(Request $request): string|array
    {
        $payload = json_decode($request->getContent(), true);

        $result = [];
        foreach ($payload['events'] ?? [] as $event) {
            $type = $event['event'] ?? null;
            if (is_string($type) && $type !== '') {
                $result[$type][] = $event;
            }
        }

        return $result ?: '';
    }
}
