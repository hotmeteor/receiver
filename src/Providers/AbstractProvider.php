<?php

namespace Receiver\Providers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Receiver\Contracts\Provider as ProviderContract;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractProvider implements ProviderContract, Responsable
{
    /**
     * The cached webhook instance.
     *
     * @var Webhook|null
     */
    protected Webhook|null $webhook;

    /**
     * @var Request|null
     */
    protected Request|null $request;

    /**
     * @var string|null
     */
    protected string|null $response;

    /**
     * @param string|null $secret
     */
    public function __construct(protected ?string $secret = null)
    {
    }

    /**
     * @param Request $request
     * @return string
     */
    abstract public function getEvent(Request $request): string;

    /**
     * @param Request $request
     * @return array
     */
    public function getData(Request $request): array
    {
        return $request->all();
    }

    /**
     * Set the scopes of the requested access.
     *
     * @param Request $request
     * @return $this
     */
    public function receive(Request $request): static
    {
        if (method_exists(static::class, 'challenge')) {
            $this->response = call_user_func([$this, 'challenge'], $request);

            return $this;
        }

        if (method_exists(static::class, 'verify')) {
            call_user_func([$this, 'verify'], $request);
        }

        $this->request = $request;
        $this->webhook = $this->mapWebhook();

        $this->handle();

        return $this;
    }

    /**
     * @return JsonResponse|Response
     */
    public function ok(): JsonResponse|Response
    {
        return $this->toResponse($this->request);
    }

    /**
     * @param $request
     * @return JsonResponse|Response
     */
    public function toResponse($request): JsonResponse|Response
    {
        return response()->json($this->response, 200);
    }

    /**
     * @return Webhook
     */
    public function webhook(): Webhook
    {
        return $this->webhook;
    }

    /**
     * @return Webhook
     */
    protected function mapWebhook(): Webhook
    {
        return (new Webhook())->setRaw([
            'event' => $this->getEvent($this->request),
            'data' => $this->getData($this->request),
        ]);
    }

    /**
     * @return void
     */
    protected function handle(): void
    {
        $class = $this->getClass($event = $this->webhook->getEvent());

        if (class_exists($class)) {
            $instance = new $class($event, $this->webhook);

            dispatch($instance);
        }
    }

    /**
     * @param string $event
     * @return string
     */
    protected function getClass(string $event): string
    {
        $className = Str::studly($event);

        $basepath = rtrim($this->handlerPath(), '\\');

        return implode('\\', [$basepath, Str::studly($this->driver), $className]);
    }

    /**
     * @return string
     */
    protected function handlerPath(): string
    {
        return '\\App\\Http\\Handlers';
    }
}
