<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class FakeProvider extends AbstractProvider
{
    /**
     * @return string
     */
    public function getEvent(Request $request): string|array
    {
        return $request->input('type', 'fake');
    }

    public function getData(Request $request): array
    {
        return $request->input('data', []);
    }
}
