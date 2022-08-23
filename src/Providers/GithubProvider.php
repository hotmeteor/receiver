<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class GithubProvider extends AbstractProvider
{
    /**
     * @param Request $request
     * @return bool
     */
    public function verify(Request $request): bool
    {
        $header = $request->header('HTTP_X_HUB_SIGNATURE_256');
        $signature = hash_hmac('sha256', $request->getContent(), $this->secret);

        return hash_equals($header, $signature);
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getEvent(Request $request): string
    {
        return implode('_', [$request->header('X-GitHub-Event'), $request->input('action')]);
    }
}
