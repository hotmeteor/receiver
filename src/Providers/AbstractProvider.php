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
    protected Webhook|null $webhook = null;

    /**
     * @var Request|null
     */
    protected Request|null $request = null;

    /**
     * @var mixed
     */
    protected mixed $response = null;

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
        $this->request = $request;

        if (method_exists(static::class, 'handshake')) {
            if ($this->response = call_user_func([$this, 'handshake'], $request)) {
                return $this;
            }
        }

        if (method_exists(static::class, 'verify')) {
            if (! call_user_func([$this, 'verify'], $request)) {
                abort(401, 'Unauthorized');
            }
        }

        $this->webhook = $this->mapWebhook($request);

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
     * @return Webhook|null
     */
    public function webhook(): ?Webhook
    {
        return $this->webhook;
    }

    /**
     * @param Request $request
     * @return Webhook
     */
    protected function mapWebhook(Request $request): Webhook
    {
        return (new Webhook())->setRaw($request->all())->map([
            'event' => $this->getEvent($request),
            'data' => $this->getData($request),
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
        $driverName = Str::replace('Provider', '', class_basename(static::class));

        $basepath = rtrim($this->handlerPath(), '\\');

        return implode('\\', [$basepath, $driverName, $className]);
    }

    /**
     * @return string
     */
    protected function handlerPath(): string
    {
        return '\\App\\Http\\Handlers';
    }
}
