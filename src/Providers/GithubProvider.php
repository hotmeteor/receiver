<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class GithubProvider extends AbstractProvider
{
    /**
     * https://docs.github.com/en/developers/webhooks-and-events/webhooks/securing-your-webhooks.
     */
    public function verify(Request $request): bool
    {
        $header = $request->header('X-Hub-Signature-256');
        $signature = hash_hmac('sha256', $request->getContent(), $this->secret);

        return hash_equals(substr($header, 7), $signature);
    }

    /**
     * @return string
     */
    public function getEvent(Request $request): string|array
    {
        return implode('_', array_filter([$request->header('X-GitHub-Event'), $request->input('action')]));
    }
}
