![Receiver](./art/logo.png)

# Receiver

**Receiver is a drop-in webhook handling library for Laravel.**

Receiver gives you a consistent, expressive way to receive, verify, and handle incoming webhooks in your Laravel app. Point a route at a controller, call three methods, and you're done.

Out of the box, Receiver supports:

| Provider | Driver |
|----------|--------|
| [GitHub](https://docs.github.com/en/developers/webhooks-and-events/webhooks/about-webhooks) | `github` |
| [HubSpot](https://developers.hubspot.com/docs/api/webhooks) | `hubspot` |
| [Mailchimp Marketing](https://mailchimp.com/developer/marketing/guides/sync-audience-data-webhooks/) | `mailchimp` |
| [Paddle Billing](https://developer.paddle.com/webhooks/overview) | `paddle` |
| [Postmark](https://postmarkapp.com/developer/webhooks/webhooks-overview) | `postmark` |
| [SendGrid Events](https://docs.sendgrid.com/for-developers/tracking-events/getting-started-event-webhook-security-features) | `sendgrid` |
| [Shopify](https://shopify.dev/docs/apps/webhooks) | `shopify` |
| [Slack Events API](https://api.slack.com/apis/connections/events-api) | `slack` |
| [Stripe](https://stripe.com/docs/webhooks) | `stripe` |
| [Twilio](https://www.twilio.com/docs/usage/webhooks) | `twilio` |

Any other webhook source can be added with a [custom provider](#extending-receiver).

![Tests](https://github.com/hotmeteor/receiver/workflows/Tests/badge.svg)
[![Latest Version on Packagist](https://img.shields.io/packagist/vpre/hotmeteor/receiver.svg?style=flat-square)](https://packagist.org/packages/hotmeteor/receiver)
![PHP from Packagist](https://img.shields.io/packagist/php-v/hotmeteor/receiver)

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Receiving Webhooks](#receiving-webhooks)
    - [Single provider](#single-provider)
    - [Multiple providers](#multiple-providers)
    - [Fallbacks](#fallbacks)
- [Handling Webhooks](#handling-webhooks)
    - [Handler naming](#handler-naming)
    - [Queueing handlers](#queueing-handlers)
- [Extending Receiver](#extending-receiver)
    - [Generating a provider](#generating-a-provider)
    - [Defining getEvent() and getData()](#defining-getevent-and-getdata)
    - [Securing webhooks](#securing-webhooks)
    - [Handshakes](#handshakes)
    - [Multiple events per request](#multiple-events-per-request)
    - [Creating a community provider](#creating-a-community-provider)
- [Share Your Receivers!](#share-your-receivers)
- [Credits](#credits)
- [License](#license)

## Installation

Requires PHP ^8.2 and Laravel 10+.

```shell
composer require hotmeteor/receiver
```

> **Note:** The Stripe provider requires [`stripe/stripe-php`](https://github.com/stripe/stripe-php):
> ```shell
> composer require stripe/stripe-php
> ```

## Configuration

Each provider reads its secret from `config/services.php`. Add an entry for each source you intend to receive from.

Most providers use the same shape:

```php
'github'   => ['webhook_secret' => env('GITHUB_WEBHOOK_SECRET')],
'hubspot'  => ['webhook_secret' => env('HUBSPOT_WEBHOOK_SECRET')],
'paddle'   => ['webhook_secret' => env('PADDLE_WEBHOOK_SECRET')],
'shopify'  => ['webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET')],
'slack'    => ['webhook_secret' => env('SLACK_WEBHOOK_SECRET')],
'stripe'   => ['webhook_secret' => env('STRIPE_WEBHOOK_SECRET')],
'twilio'   => ['webhook_secret' => env('TWILIO_AUTH_TOKEN')],
```

**Mailchimp** — Mailchimp Marketing webhooks are verified via a secret you embed in your webhook URL (`?secret=...`). Configure the same value here so Receiver can compare it:

```php
'mailchimp' => ['webhook_secret' => env('MAILCHIMP_WEBHOOK_SECRET')],
```

**SendGrid** — Signature verification is opt-in. Set `webhook_secret` to the PEM-format public key found in the SendGrid dashboard under Settings → Mail Settings → Event Webhook. Leave it empty to accept all requests without verification.

```php
'sendgrid' => ['webhook_secret' => env('SENDGRID_WEBHOOK_PUBLIC_KEY', '')],
```

**Postmark** — Postmark supports several verification strategies. Configure which ones to use under the `webhook` key:

```php
'postmark' => [
    'token' => env('POSTMARK_TOKEN'),
    'webhook' => [
        // One or more of: 'auth', 'headers', 'ips'
        'verification_types' => ['headers', 'ips'],

        // Header name => expected value pairs (used with 'headers')
        'headers' => [
            'X-Custom-Header' => env('POSTMARK_WEBHOOK_HEADER'),
        ],

        // Allowed source IPs (used with 'ips')
        // https://postmarkapp.com/support/article/800-ips-for-firewalls#webhooks
        'ips' => [
            '3.134.147.250',
            '50.31.156.6',
            '50.31.156.77',
            '18.217.206.57',
        ],
    ],
],
```

| Postmark `verification_type` | Description |
|------------------------------|-------------|
| `auth` | HTTP Basic Auth via `Auth::onceBasic()` |
| `headers` | Validates that specific request headers match expected values |
| `ips` | Validates that the request originates from an allowed IP |

If `verification_types` is empty or not set, all Postmark requests are accepted without verification.

## Receiving Webhooks

### Single provider

Create a controller and route for each webhook source, then call `driver()`, `receive()`, and `ok()`:

```php
<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Receiver\Facades\Receiver;

class StripeWebhookController extends Controller
{
    public function store(Request $request)
    {
        return Receiver::driver('stripe')
            ->receive($request)
            ->ok();
    }
}
```

- `driver()` — selects the provider and reads its config
- `receive()` — verifies the signature and maps the event
- `ok()` — dispatches matched handlers and returns a `200` response

### Multiple providers

If you'd rather handle all webhooks through a single controller, use a `{provider}` route parameter:

```php
// routes/web.php
Route::post('/webhooks/{provider}', [WebhookController::class, 'store'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
```

```php
<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Receiver\Facades\Receiver;

class WebhookController extends Controller
{
    public function store(Request $request, string $provider)
    {
        return Receiver::driver($provider)
            ->receive($request)
            ->ok();
    }
}
```

Or use the included `ReceivesWebhooks` trait, which provides this exact `store()` method for you:

```php
<?php

namespace App\Http\Controllers\Webhooks;

use Receiver\ReceivesWebhooks;

class WebhookController extends Controller
{
    use ReceivesWebhooks;
}
```

### Fallbacks

Receiver silently ignores events it has no handler for. If you'd like to do something with unhandled events, add a `fallback()` callback before `ok()`:

```php
use Receiver\Providers\Webhook;

return Receiver::driver($provider)
    ->receive($request)
    ->fallback(function (Webhook $webhook) {
        Log::info('Unhandled webhook', ['event' => $webhook->getEvent()]);
    })
    ->ok();
```

## Handling Webhooks

Once a webhook is received, Receiver looks for a handler class that matches the event and dispatches it. Handlers live in `App\Http\Handlers\{Driver}\` by default. If no matching handler is found the webhook is silently ignored and a `200` is returned.

### Handler naming

The handler class name is derived from the event name — all non-alphanumeric characters are treated as word separators, then converted to `StudlyCase`:

| Event name | Handler class |
|------------|---------------|
| `customer.created` | `CustomerCreated` |
| `subscription_activated` | `SubscriptionActivated` |
| `orders_created` | `OrdersCreated` |
| `invoice.payment_failed` | `InvoicePaymentFailed` |

For example, Stripe's `customer.created` event dispatches `App\Http\Handlers\Stripe\CustomerCreated`.

Each handler receives the `$event` name and the `$data` array, and must use the `Dispatchable` trait:

```php
<?php

namespace App\Http\Handlers\Stripe;

use Illuminate\Foundation\Bus\Dispatchable;

class CustomerCreated
{
    use Dispatchable;

    public function __construct(
        public string $event,
        public array $data,
    ) {}

    public function handle(): void
    {
        // Your code here
    }
}
```

### Queueing handlers

Because Receiver calls `dispatch()` on each handler, making a handler queued is as simple as implementing `ShouldQueue`:

```php
<?php

namespace App\Http\Handlers\Stripe;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CustomerCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $event,
        public array $data,
    ) {}

    public function handle(): void
    {
        // Your code here
    }
}
```

See the [Laravel queue documentation](https://laravel.com/docs/queues) for full details.

## Extending Receiver

A provider is a PHP class that tells Receiver how to extract the event name, the payload data, and optionally how to verify the request's authenticity.

### Generating a provider

The quickest way to scaffold a new provider is with the `receiver:make` Artisan command:

```shell
# Basic provider
php artisan receiver:make Mailgun

# With signature verification scaffolded
php artisan receiver:make Mailgun --verified
```

The generated class is placed in `App\Http\Receivers`. Once created, register the driver in your `AppServiceProvider`:

```php
public function boot(): void
{
    app('receiver')->extend('mailgun', function () {
        return new \App\Http\Receivers\MailgunProvider(
            config('services.mailgun.webhook_secret')
        );
    });
}
```

### Defining getEvent() and getData()

Implement `getEvent()` to return the event name. Optionally implement `getData()` to return the event payload — by default it returns `$request->all()`.

```php
<?php

namespace App\Http\Receivers;

use Illuminate\Http\Request;
use Receiver\Providers\AbstractProvider;

class MailgunProvider extends AbstractProvider
{
    public function getEvent(Request $request): string|array
    {
        return $request->input('event-data.event');
    }

    public function getData(Request $request): array
    {
        return $request->input('event-data', []);
    }
}
```

### Securing webhooks

Implement a `verify()` method that returns `true` if the request is authentic, or `false` to reject it with a `401` response:

```php
public function verify(Request $request): bool
{
    $signature = $request->header('X-Mailgun-Signature');
    $expected = hash_hmac('sha256', $request->getContent(), $this->secret);

    return hash_equals($expected, (string) $signature);
}
```

The signing secret from `config/services.{driver}.webhook_secret` is available as `$this->secret`.

### Handshakes

Some services send a verification request when a webhook URL is first registered. Implement `handshake()` to respond to it:

```php
public function handshake(Request $request): array
{
    return ['challenge' => $request->input('challenge')];
}
```

When `handshake()` returns a non-empty array, Receiver responds immediately with that payload and skips normal event handling. When it returns an empty array, Receiver processes the request normally.

### Multiple events per request

Some services batch multiple events into a single request. Return an `['event_name' => $eventData]` array from `getEvent()` and Receiver will dispatch a separate handler for each entry:

```php
public function getEvent(Request $request): string|array
{
    $events = [];

    foreach (json_decode($request->getContent(), true) as $event) {
        $type = $event['type'] ?? null;
        if ($type && ! isset($events[$type])) {
            $events[$type] = $event;
        }
    }

    return $events;
}
```

### Creating a community provider

If you're building a reusable provider package to share, add the `--provider` flag to also generate a companion `ServiceProvider` that registers the driver automatically:

```shell
php artisan receiver:make Mailgun --provider
php artisan receiver:make Mailgun --verified --provider
```

This generates:

- `app/Http/Receivers/MailgunProvider.php` — your provider class
- `app/Providers/MailgunReceiverServiceProvider.php` — auto-registers the driver via `Receiver::extend()`

To support [Laravel package auto-discovery](https://laravel.com/docs/packages#package-discovery), add the service provider to your package's `composer.json`:

```json
{
    "extra": {
        "laravel": {
            "providers": [
                "YourVendor\\YourPackage\\MailgunReceiverServiceProvider"
            ]
        }
    }
}
```

Users who install your package will have the driver available immediately, with no manual registration required.

## Share Your Receivers!

**Built a provider for a service not listed above?** Share it with the community in the **[Receivers Discussion topic](https://github.com/hotmeteor/receiver/discussions/categories/receivers)**!

## Credits

- [Adam Campbell](https://github.com/hotmeteor)
- [All Contributors](../../contributors)

<a href="https://github.com/hotmeteor/receiver/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=hotmeteor/receiver"/>
</a>

Made with [contributors-img](https://contrib.rocks).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

![](https://media.giphy.com/media/LoCDk7fecj2dwCtSB3/giphy.gif)
