![](https://media.giphy.com/media/LoCDk7fecj2dwCtSB3/giphy.gif)

# Receiver

**Receiver is a drop-in webhook handling library for Laravel.**

Webhooks are a powerful part of any API lifecycle. **Receiver** aims to make handling incoming webhooks in your Laravel app a consistent and easy process.

Out of the box, Receiver has built in support for:

- [GitHub Webhooks](https://docs.github.com/en/developers/webhooks-and-events/webhooks/about-webhooks)
- [Postmark Webhooks](https://postmarkapp.com/developer/webhooks/webhooks-overview)
- [Slack Events API](https://api.slack.com/apis/connections/events-api)
- [Stripe Webhooks](https://stripe.com/docs/webhooks)

Of course, Receiver can receive webhooks from any source using custom providers.

![Tests](https://github.com/hotmeteor/receiver/workflows/Tests/badge.svg)
[![Latest Version on Packagist](https://img.shields.io/packagist/vpre/hotmeteor/receiver.svg?style=flat-square)](https://packagist.org/packages/hotmeteor/receiver)
![PHP from Packagist](https://img.shields.io/packagist/php-v/hotmeteor/receiver)

## Installation

Requires:

- PHP ^8.0
- Laravel 8+

```shell
composer require hotmeteor/receiver
```

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
           Receiver::driver('slack')
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
       Receiver::driver($driver)
           ->receive($request)
           ->ok();
   }
}
```

The provided `ReceivesWebhooks` trait will take care of this for you.

_Note: you'll still need to create the route to this action._

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

## Handling Webhooks

### The Basics

Now that webhooks are being received they need to be handled. Receiver will look for designated `Handler` classes for each event type that comes in in the `App\Http\Handlers\[Driver]` namespace. Receiver *does not* provide these handlers -- they are up to you to provide as needed. If Receiver doesn't find a matching handler it simplies ignores the event and responds with a 200 status code.

For example, a Stripe webhook handler would be `App\Http\Handlers\Stripe\CustomerCreated` for the incoming [`customer.created`](https://stripe.com/docs/api/events/types#event_types-customer.created) event.

Each handler is constructed with the `event` (name of the webhook event) and `data` properties.

```php
<?php

namespace App\Http\Handlers\Stripe;

class CustomerCreated
{
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

### Changing handler paths

WIP

## Extending Receiver

### Adding Providers

WIP




## Credits

- [Adam Campbell](https://github.com/hotmeteor)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

