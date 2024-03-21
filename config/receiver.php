<?php

return [
    'postmark' => [
        /**
         * Set the verification types to be used to verify Postmark webhook.
         * The order of the verification types determines which one will be run first.
         * All verification types need to pass in order for a webhook request to be verified.
         *
         * Supported types: "auth", "headers", "ips"
         */
        'verification_types' => [
            'auth',
            'headers',
            'ips',
        ],

        /**
         * Set the combination of key-value pairs of headers to be verified against
         * the webhook request. Currently, Postmark will send the "User-Agent" header with
         * value of "Postmark" by default.
         *
         * Additional headers can be configured in the Postmark webhook management page.
         */
        'headers' => [
            'User-Agent' => 'Postmark',
        ],

        /**
         * Set a list of IPs that is allowed to make the webhook request.
         * By default, this options is populated with IPs provided by Postmark
         * on https://postmarkapp.com/support/article/800-ips-for-firewalls#webhooks
         */
        'ips' => [
            '3.134.147.250',
            '50.31.156.6',
            '50.31.156.77',
            '18.217.206.57',
        ],
    ],
];
