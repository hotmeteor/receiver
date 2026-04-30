<?php

namespace Receiver\Contracts;

interface Webhook
{
    /**
     * @return string|array
     */
    public function getEvent(): string|array;

    /**
     * @return array
     */
    public function getData(): array;
}
