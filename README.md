![Receiver](./art/logo.png)

# Receiver

**Receiver is a drop-in webhook handling library for Laravel.**

Webhooks are a powerful part of any API lifecycle. **Receiver** aims to make handling incoming webhooks in your Laravel app a consistent and easy process.

Out of the box, Receiver has built in support for:

- [GitHub Webhooks](https://docs.github.com/en/developers/webhooks-and-events/webhooks/about-webhooks)
- [Hubspot Webhooks](https://developers.hubspot.com/docs/api/webhooks)
- [Postmark Webhooks](https://postmarkapp.com/developer/webhooks/webhooks-overview)
- [Slack Events API](https://api.slack.com/apis/connections/events-api)
- [Stripe Webhooks](https://stripe.com/docs/webhooks)

Of course, Receiver can receive webhooks from any source using [custom providers](#extending-receiver).

![Tests](https://github.com/hotmeteor/receiver/workflows/Tests/badge.svg)
[![Latest Version on Packagist](https://img.shields.io/packagist/vpre/hotmeteor/receiver.svg?style=flat-square)](https://packagist.org/packages/hotmeteor/receiver)
![PHP from Packagist](https://img.shields.io/packagist/php-v/hotmeteor/receiver)

## Table of Contents

- [Installation](#installation)
- [Receiving Webhooks](#receiving-webhooks)
    - [The Basics](#the-basics)
    - [Receiving from multiple apps](#receiving-from-multiple-apps)
- [Handling Webhooks](#handling-webhooks)
    - [The Basics](#the-basics-1)
    - [Queueing handlers](#queueing-handlers)
- [Extending Receiver](#extending-receiver)
    - [Adding custom providers](#adding-custom-providers)
    - [Defining attributes](#defining-attributes)
    - [Securing webhooks](#securing-webhooks)
    - [Handshakes](#handshakes)
- [Community Receivers](#share-your-receivers)
- [Credits](#credits)
- [License](#license)

## Installation

Requires:

- PHP ^8.0
- Laravel 8+

```shell
composer require hotmeteor/receiver
```

Optional:

**Stripe** support requires [`stripe/stripe-php`](https://github.com/stripe/stripe-php)

## Receiving Webhooks

### The Basics

Webhooks require an exposed endpoint to POST to. Receiver aims to make this a one-time setup that supports any of your incoming webhooks.

1. Create a controller and route for the webhooks you expect to receive.
2. Receive the webhook and handle it, as necessary:
    ```php
    <?php
    
    namespace App\Http\Controllers\Webhooks;
    
    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    
    class WebhooksController extends Controller
    {
       public function store(Request $request)
       {
           return Receiver::driver('slack')
               ->receive($request)
               ->ok();
       }
    }
    ``` 

The methods being used are simple:

- Define the `driver` that should process the webhook
- `receive` the request for handling
- Respond back to the sender with a `200` `ok` response


### Receiving from multiple apps

Maybe you have webhooks coming in from multiple services -- handle them all from one controller with a driver variable from your route.

```php
<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebhooksController extends Controller
{
   public function store(Request $request, string $driver)
   {
       return Receiver::driver($driver)
           ->receive($request)
           ->ok();
   }
}
```

The provided `ReceivesWebhooks` trait will take care of this for you.

```php
<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Receiver\ReceivesWebhooks;

class WebhooksController extends Controller
{
   use ReceivesWebhooks;
}
```

_Note: you'll still need to create the route to this action._ Example:

```php
Route::post('/hooks/{driver}', [\App\Http\Controllers\Webhooks\WebhooksController::class, 'store'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
```

### Advanced Usage

#### Fallbacks

Receiver allows you to safely handle webhooks for events you do *not* handle. Add a `fallback` method before `ok` – it takes a callback that is passed the webhook object.

```php
<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Receiver\Providers\Webhook;

class WebhooksController extends Controller
{
   public function store(Request $request, string $driver)
   {
       return Receiver::driver($driver)
           ->receive($request)
           ->fallback(function(Webhook $webhook) {
               // Do whatever you like here...
           })
           ->ok();
   }
}
```

## Handling Webhooks

### The Basics

Now that webhooks are being received they need to be handled. Receiver will look for designated `Handler` classes for each event type that comes in in the `App\Http\Handlers\[Driver]` namespace. Receiver *does not* provide these handlers -- they are up to you to provide as needed. If Receiver doesn't find a matching handler it simplies ignores the event and responds with a 200 status code.

For example, a Stripe webhook handler would be `App\Http\Handlers\Stripe\CustomerCreated` for the incoming [`customer.created`](https://stripe.com/docs/api/events/types#event_types-customer.created) event.

Each handler is constructed with the `event` (name of the webhook event) and `data` properties.

Each handler must also use the `Dispatchable` trait.

```php
<?php

namespace App\Http\Handlers\Stripe;

use Illuminate\Foundation\Bus\Dispatchable;

class CustomerCreated
{
    use Dispatchable;
    
    public function __construct(public string $event, public array $data)
    {
    }

    public function handle()
    {
        // Your code here
    }
}
```

### Queueing Handlers

Of course, since your app may be receiving a lot of webhooks it might be better practice to queue these handlers. That way your app can efficiently respond back to the service that the webhook was received and requests aren't being blocked as events are handled.

Receiver attempts to `dispatch` every handled event, so queueing handlers is simply a matter of setting them up like any [Laravel queued job](https://laravel.com/docs/9.x/queues):


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
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public string $event, public array $data)
    {
    }

    public function handle()
    {
        // Your code here
    }
}
```

## Extending Receiver

As mentioned previously, Receiver can handle webhooks from any source. Even though there are a few providers distributed with the package, Receiver can easily be extended to work with other apps. 

### Adding Custom Providers

The easiest way to add a new provider is to use the included Artisan command:

```shell
php artisan receiver:make <name>
```

This command will generate a new provider with the name you defined. This class will be created in the `App\Http\Receivers` namespace.

If your provider needs to be able to verify webhook signatures simply add the `--verified` flag to the command:

```shell
php artisan receiver:make <name> --verified
```

Once you've created your new provider you can simply extend Receiver in your `AppServiceProvider` so that Receiver can use it:

```php
<?php

namespace App\Providers;

use App\Http\Receivers\MailchimpProvider;
use App\Http\Receivers\MailgunProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $receiver = app('receiver');

        $receiver->extend('mailchimp', function ($app) {
            return new MailchimpProvider(
                config('services.mailchimp.webhook_secret')
            );
        });
        
        $receiver->extend('mailgun', function ($app) {
            return new MailgunProvider(
                config('services.mailgun.webhook_secret')
            );
        });
    }
}

```

### Defining Attributes

Receiver needs two pieces of information to receive and handle webhook events:

- The event `name`
- The event `data`

Since these are found in different attributes or headers depending on the webhook, Receiver makes it simple ways to define them in your provider.

```php
<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomProvider extends AbstractProvider
{
    /**
     * @param Request $request
     * @return string
     */
    public function getEvent(Request $request): string
    {
        return $request->input('event.name');
    }
    
    /**
     * @param Request $request
     * @return array
     */
    public function getData(Request $request): array
    {
        return $request->all();
    }
}
```

The *`getEvent()`* method is used to return the name of the webhook event, ie. `customer.created`.

The *`getData()`* method is used to return the payload of data that can be used within your handler. By default this is set to `$request->all()`.

### Securing Webhooks

Many webhooks have ways of verifying their authenticity as they are received, most commonly through signatures or basic authentication. No matter the strategy, Receiver allows you to write custom verification code as necessary. Simply implement the `verify` method in your provider and return true or false if it passes.

A `false` return will result in a 401 response being returned to the webhook sender.

```php
<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomProvider extends AbstractProvider
{
    public function verify(Request $request): bool
    {
        // return result of verification
    }
}
```

### Handshakes

Some webhooks want to perform a "handshake" to check if your endpoint exists and returns a valid response when it's first set up. To facilitate this, implement the `handshake` method in your provider:

```php
<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomProvider extends AbstractProvider
{
    public function handshake(Request $request): array
    {
        // return result of handshake
    }
}
```

Unlike the `verify` method, `handshake` expects an array to be returned, since many times the webhook sender is expecting a specific "challenge" response. The return of the handshake method is sent back to the webhook sender.

## Share your Receivers!

**Have you created a custom Receiver?** Share it with the community in our **[Receivers Discussion topic](https://github.com/hotmeteor/receiver/discussions/categories/receivers)**!

## Credits

- [Adam Campbell](https://github.com/hotmeteor)
- [All Contributors](../../contributors)

<a href = "https://github.com/hotmeteor/receiver/graphs/contributors">
  <img src = "https://contrib.rocks/image?repo=hotmeteor/receiver"/>
</a>

Made with [contributors-img](https://contrib.rocks).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

![](https://media.giphy.com/media/LoCDk7fecj2dwCtSB3/giphy.gif)
