<?php

namespace Receiver\Providers;

use Closure;
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
     * @var Closure|null
     */
    protected Closure|null $fallback = null;

    /**
     * @var bool
     */
    protected mixed $dispatched = false;

    /**
     * @var string
     */
    protected string $handlerNamespace = '\\App\\Http\\Handlers';

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
        if (! $this->dispatched() && $this->fallback) {
            $callback = $this->fallback;

            $callback($this->webhook);
        }

        return $this->toResponse($this->request);
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function fallback(Closure $closure): static
    {
        $this->fallback = $closure;

        return $this;
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
     * @return bool
     */
    public function dispatched(): bool
    {
        return $this->dispatched;
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
     * @return AbstractProvider
     */
    protected function handle(): static
    {
        $class = $this->getClass($event = $this->webhook->getEvent());

        if (class_exists($class)) {
            $class::dispatch($event, $this->webhook->getData());

            $this->dispatched = true;
        }

        return $this;
    }

    /**
     * @param string $event
     * @return string
     */
    protected function getClass(string $event): string
    {
        $className = $this->prepareHandlerClassname($event);
        $driverName = $this->prepareDriverClassname();

        $basepath = rtrim($this->getHandlerNamespace(), '\\');

        return implode('\\', [$basepath, $driverName, $className]);
    }

    /**
     * @param string $event
     * @return string
     */
    protected function prepareHandlerClassname(string $event): string
    {
        return (string) Str::of($event)->replaceMatches('/[^A-Za-z0-9]++/', ' ')->studly();
    }

    /**
     * @return string
     */
    protected function prepareDriverClassname(): string
    {
        return Str::replace('Provider', '', class_basename(static::class));
    }

    /**
     * @param string $namespace
     * @return $this
     */
    public function setHandlerNamespace(string $namespace): static
    {
        $this->handlerNamespace = $namespace;

        return $this;
    }

    /**
     * @return string
     */
    public function getHandlerNamespace(): string
    {
        return $this->handlerNamespace;
    }
}
