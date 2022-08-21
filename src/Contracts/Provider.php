<?php

namespace Receiver\Contracts;

interface Provider
{
    /**
     * Get the webhook instance.
     *
     * @return Webhook|null
     */
    public function webhook(): ?Webhook;
}
