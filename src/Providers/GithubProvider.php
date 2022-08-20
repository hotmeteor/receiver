<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class GithubProvider extends AbstractProvider
{
    /**
     * @param Request $request
     * @return string
     */
    public function getEvent(Request $request): string
    {
        return implode('_', [$request->header('X-GitHub-Event'), $request->input('action')]);
    }
}
