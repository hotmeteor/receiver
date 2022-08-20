<?php

namespace Receiver\Providers;

use Illuminate\Http\Request;

class SlackProvider extends AbstractProvider
{
    /**
     * @param Request $request
     * @return string|null
     */
    public function challenge(Request $request): string|null
    {
        return $this->getEvent($request) === 'url_verification' ? $request->input('challenge') : null;
    }

    /**
     * https://api.slack.com/authentication/verifying-requests-from-slack#verifying-requests-from-slack-using-signing-secrets__a-recipe-for-security__step-by-step-walk-through-for-validating-a-request.
     *
     * @param  Request  $request
     * @return void
     */
    public function verify(Request $request): void
    {
        $timestamp = $request->header('X-Slack-Request-Timestamp');

        if ((time() - $timestamp) < 60 * 5) {
            $signature = implode(':', ['v0', $timestamp, $request->getContent()]);
            $signature = hash_hmac('sha256', $signature, $this->secret);
            $signature = 'v0='.$signature;

            if (! hash_equals($signature, $request->header('X-Slack-Signature'))) {
                abort(401);
            }
        }
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
