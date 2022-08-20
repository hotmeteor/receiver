<?php

namespace Receiver\Contracts;

interface Provider
{
    /**
     * Get the webhook instance.
     *
     * @return Webhook
     */
    public function webhook(): Webhook;
}
