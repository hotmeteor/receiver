<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class FakeProvider extends AbstractProvider
{
    /**
     * @param Request $request
     * @return string
     */
    public function getEvent(Request $request): string
    {
        return $request->input('type', 'fake');
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getData(Request $request): array
    {
        return $request->input('data', []);
    }
}
