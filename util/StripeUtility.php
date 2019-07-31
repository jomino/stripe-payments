<?php

namespace Util;

class StripeUtility
{

    const EVENT_CHARGE_FAILED = 'charge.failed';
    const EVENT_CHARGE_SUCCEEDED = 'charge.succeeded';

    public static function createWebhook($api_key,$wh_url)
    {
        \Stripe\Stripe::setApiKey($api_key);

        $response = \Stripe\WebhookEndpoint::create([
          "url" => $wh_url,
          "enabled_events" => [EVENT_CHARGE_FAILED,EVENT_CHARGE_SUCCEEDED]
        ]);

        return $response;

    }

}