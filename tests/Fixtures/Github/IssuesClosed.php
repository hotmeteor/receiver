<?php

namespace Receiver\Tests\Fixtures\Github;

use Illuminate\Support\Facades\Log;
use Receiver\Providers\Webhook;

class IssuesClosed
{
    public function __construct(public string $name, public array $data, public Webhook $webhook)
    {
    }

    public function handle()
    {
        Log::info('Webhook handled.');
    }
}
