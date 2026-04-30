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
     */
    protected ?Webhook $webhook = null;

    protected ?Request $request = null;

    protected mixed $response = null;

    protected ?Closure $fallback = null;

    protected array $dispatchedEvents = [];

    protected string $handlerNamespace = '\\App\\Http\\Handlers';

    public function __construct(protected ?string $secret = null) {}

    abstract public function getEvent(Request $request): string|array;

    public function getData(Request $request): array
    {
        return $request->all();
    }

    /**
     * Set the scopes of the requested access.
     *
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

    public function ok(): JsonResponse|Response
    {
        if (! $this->dispatched() && $this->fallback) {
            $callback = $this->fallback;

            $callback($this->webhook);
        }

        return $this->toResponse($this->request);
    }

    /**
     * @return $this
     */
    public function fallback(Closure $closure): static
    {
        $this->fallback = $closure;

        return $this;
    }

    public function toResponse($request): JsonResponse|Response
    {
        return response()->json($this->response, 200);
    }

    public function webhook(): ?Webhook
    {
        return $this->webhook;
    }

    /**
     * @param  string|null  $key  Handler class name to check (e.g. MyHandler::class)
     */
    public function dispatched(?string $key = null): bool
    {
        return $key
            ? in_array($key, $this->dispatchedEvents)
            : ! empty($this->dispatchedEvents);
    }

    protected function mapWebhook(Request $request): Webhook
    {
        return (new Webhook)->setRaw($request->all())->map([
            'event' => $this->getEvent($request),
            'data' => $this->getData($request),
        ]);
    }

    protected function handle(): static
    {
        $events = $this->webhook->getEvent();

        if (! is_array($events)) {
            $events = [$events => $this->webhook->getData()];
        }

        foreach ($events as $event => $data) {
            $class = $this->getClass($event);

            if (class_exists($class)) {
                $class::dispatch($event, $data);

                $this->dispatchedEvents[] = $class;
            }
        }

        return $this;
    }

    protected function getClass(string $event): string
    {
        $className = $this->prepareHandlerClassname($event);
        $driverName = $this->prepareDriverClassname();

        $basepath = rtrim($this->getHandlerNamespace(), '\\');

        return implode('\\', [$basepath, $driverName, $className]);
    }

    protected function prepareHandlerClassname(string $event): string
    {
        return (string) Str::of($event)->lower()->replaceMatches('/[^A-Za-z0-9]++/', ' ')->studly();
    }

    protected function prepareDriverClassname(): string
    {
        return Str::replace('Provider', '', class_basename(static::class));
    }

    /**
     * @return $this
     */
    public function setHandlerNamespace(string $namespace): static
    {
        $this->handlerNamespace = $namespace;

        return $this;
    }

    public function getHandlerNamespace(): string
    {
        return $this->handlerNamespace;
    }
}
