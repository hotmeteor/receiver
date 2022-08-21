<?php

namespace Receiver;

use ArrayAccess;
use Receiver\Contracts\Webhook;

abstract class AbstractWebhook implements ArrayAccess, Webhook
{
    /**
     * The normalized name of the webhook event.
     *
     * @var string|null
     */
    public string|null $event = null;

    /**
     * The payload of the webhook event.
     *
     * @var array
     */
    public array $data = [];

    /**
     * The webhook's raw attributes.
     *
     * @var array
     */
    public array $webhook = [];

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the raw webhook array.
     *
     * @return array
     */
    public function getRaw(): array
    {
        return $this->webhook;
    }

    /**
     * Set the raw webhook array from the provider.
     *
     * @param  array  $webhook
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
     * @param  array  $attributes
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
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->webhook);
    }

    /**
     * Get the given key from the raw webhook.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->webhook[$offset];
    }

    /**
     * Set the given attribute on the raw webhook array.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->webhook[$offset] = $value;
    }

    /**
     * Unset the given value from the raw webhook array.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->webhook[$offset]);
    }
}
