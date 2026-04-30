<?php

namespace Receiver;

use ArrayAccess;
use Receiver\Contracts\Webhook;

abstract class AbstractWebhook implements ArrayAccess, Webhook
{
    /**
     * The normalized name of the webhook event. May be an array of [event => data]
     * pairs when the provider returns multiple events in a single payload.
     */
    public string|array|null $event = null;

    /**
     * The payload of the webhook event.
     */
    public array $data = [];

    /**
     * The webhook's raw attributes.
     */
    public array $webhook = [];

    public function getEvent(): string|array
    {
        return $this->event ?? '';
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the raw webhook array.
     */
    public function getRaw(): array
    {
        return $this->webhook;
    }

    /**
     * Set the raw webhook array from the provider.
     *
     * @return $this
     */
    public function setRaw(array $webhook): static
    {
        $this->webhook = $webhook;

        return $this;
    }

    /**
     * Map the given array onto the webhook's properties.
     *
     * @return $this
     */
    public function map(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    /**
     * Determine if the given raw webhook attribute exists.
     *
     * @param  string  $offset
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->webhook);
    }

    /**
     * Get the given key from the raw webhook.
     *
     * @param  string  $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->webhook[$offset];
    }

    /**
     * Set the given attribute on the raw webhook array.
     *
     * @param  string  $offset
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->webhook[$offset] = $value;
    }

    /**
     * Unset the given value from the raw webhook array.
     *
     * @param  string  $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->webhook[$offset]);
    }
}
