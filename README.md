# Receiver

## A drop-in webhook handling library for Laravel.

Webhooks are a powerful part of any API lifecycle. Receiver aims to make handling incoming webhooks a consistent and easy process within your app.

### Installation

```shell
composer require hotmeteor/receiver
```

### Usage

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
   
Maybe you have multiple webhooks coming in. You can make the reception dynamic as long as you support all the providers.

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