<?php

namespace Receiver\Contracts;

interface Webhook
{
    public function getEvent(): string|array;

    public function getData(): array;
}
