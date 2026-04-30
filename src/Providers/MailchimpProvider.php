<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class MailchimpProvider extends AbstractProvider
{
    /**
     * Mailchimp Marketing webhooks do not use a cryptographic signature.
     * Verify by comparing a secret you embed in the configured webhook URL
     * (?secret=...) against the value set in your services config.
     *
     * https://mailchimp.com/developer/marketing/guides/sync-audience-data-webhooks/
     */
    public function verify(Request $request): bool
    {
        return hash_equals((string) $this->secret, (string) $request->query('secret'));
    }

    public function getEvent(Request $request): string|array
    {
        return (string) $request->input('type', '');
    }
}
