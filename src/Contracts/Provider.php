<?php

namespace Receiver\Contracts;

use Illuminate\Http\Request;

interface Provider
{
    public function receive(Request $request): static;

    /**
     * Get the webhook instance.
     */
    public function webhook(): ?Webhook;
}
