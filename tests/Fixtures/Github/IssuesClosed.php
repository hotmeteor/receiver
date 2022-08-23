<?php

namespace Receiver\Tests\Fixtures\Github;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Receiver\Providers\Webhook;

class IssuesClosed implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public string $name, public array $data, public Webhook $webhook)
    {
    }

    public function handle()
    {
        Log::info('Webhook handled.');
    }
}
