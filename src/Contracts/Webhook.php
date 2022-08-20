<?php

namespace Receiver\Contracts;

interface Webhook
{
    /**
     * @return string
     */
    public function getEvent(): string;

    /**
     * @return array
     */
    public function getData(): array;
}
