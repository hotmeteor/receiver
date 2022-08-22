# Receiver

## A drop-in webhook handling library for Laravel.

Webhooks are a powerful part of any API lifecycle. **Receiver** aims to make handling incoming webhooks in your Laravel app a consistent and easy process.

### Installation

```shell
composer require hotmeteor/receiver
```

### Receiving Webhooks

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
