<?php

namespace Receiver\Tests\Fixtures;

use Illuminate\Http\Request;
use Receiver\Providers\AbstractProvider;

class TestProvider extends AbstractProvider
{
    /**
     * @param Request $request
     * @return string
     */
    public function getEvent(Request $request): string
    {
        return $request->input('event');
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getData(Request $request): array
    {
        return $request->input('data');
    }

    /**
     * @param string $event
     * @return string
     */
    protected function getClass(string $event): string
    {
        $className = $this->prepareHandlerClassname($event);

        return "Receiver\\Tests\\Fixtures\\{$className}";
    }
}
