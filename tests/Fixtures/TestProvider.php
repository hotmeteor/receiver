<?php

namespace Receiver\Tests\Fixtures;

use Illuminate\Http\Request;
use Receiver\Providers\AbstractProvider;

class TestProvider extends AbstractProvider
{
    public function getEvent(Request $request): string|array
    {
        return $request->input('event');
    }

    public function getData(Request $request): array
    {
        return $request->input('data');
    }

    protected function getClass(string $event): string
    {
        $className = $this->prepareHandlerClassname($event);

        return "Receiver\\Tests\\Fixtures\\{$className}";
    }
}
