<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class SlackProvider extends AbstractProvider
{
    /**
     * @param Request $request
     * @return array|null
     */
    public function handshake(Request $request): array|null
    {
        return $request->has('challenge') ? $request->only('challenge') : null;
    }

    /**
     * https://api.slack.com/authentication/verifying-requests-from-slack#verifying-requests-from-slack-using-signing-secrets__a-recipe-for-security__step-by-step-walk-through-for-validating-a-request.
     *
     * @param  Request  $request
     * @return bool
     */
    public function verify(Request $request): bool
    {
        $timestamp = $request->header('X-Slack-Request-Timestamp');

        if ((now()->unix() - $timestamp) < 60 * 5) {
            $signature = implode(':', ['v0', $timestamp, $request->getContent()]);
            $signature = hash_hmac('sha256', $signature, $this->secret);
            $signature = 'v0='.$signature;

            return hash_equals($signature, $request->header('X-Slack-Signature'));
        }

        return false;
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getEvent(Request $request): string
    {
        return $request->input('event.type');
    }
}
