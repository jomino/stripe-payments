<?php

namespace Util;

class StripeUtility
{

    private static $WEBHOOK_ENABLE_EVENTS = ["charge.failed", "charge.succeeded"];

    public static function createWebhook($api_key,$wh_url)
    {
        \Stripe\Stripe::setApiKey($api_key);

        $raw = \Stripe\WebhookEndpoint::create([
          "url" => $wh_url,
          "enabled_events" => self::$WEBHOOK_ENABLE_EVENTS
        ]);

        return $raw;

    }

}