<?php

namespace Util;

class StripeUtility
{
    const METHOD_BANCONTACT = 'bancontact';
    const METHOD_SOFORT = 'sofort';
    const METHOD_IDEAL = 'ideal';

    const DEFAULT_IDEAL_BANK = 'ing';

    const DEFAULT_CURRENCY = 'eur';
    const DEFAULT_COUNTRY = 'BE';

    const SESSION_REFERRER = 'referrer';
    const SESSION_AMOUNT = 'amount';
    const SESSION_METHOD = 'payment_type';

    const STATUS_PENDING = 'pending';
    const STATUS_CHARGEABLE = 'chargeable';
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_FAILED = 'failed';

    const EVENT_CHARGE_FAILED = 'charge.failed';
    const EVENT_CHARGE_SUCCEEDED = 'charge.succeeded';
    const EVENT_SOURCE_CHARGEABLE = 'source.chargeable';
    const EVENT_SOURCE_CANCELED = 'source.canceled';
    const EVENT_SOURCE_FAILED = 'source.failed';

    public static function createWebhook($api_key,$wh_url)
    {
        \Stripe\Stripe::setApiKey($api_key);

        try {
            $response = \Stripe\WebhookEndpoint::create([
                'url' => $wh_url,
                'enabled_events' => [
                    static::EVENT_CHARGE_FAILED,
                    static::EVENT_CHARGE_SUCCEEDED,
                    static::EVENT_SOURCE_CHARGEABLE,
                    static::EVENT_SOURCE_CANCELED,
                    static::EVENT_SOURCE_FAILED
                ]
            ]);
            return $response;
        }catch (\Exception $e) {
            return null;
        }

    }

    public static function createEvent($api_key,$wh_skey,$wh_sig,$wh_evt)
    {
        \Stripe\Stripe::setApiKey($api_key);

        try {
            $event = \Stripe\Webhook::constructEvent( $wh_evt, $wh_sig, $wh_skey );
            return $event;
        } catch (\Exception $e) {
            return null;
        }

    }

    public static function createSource($api_key,$type,$amount,$currency,$email,$name,$ret_url,$options=[])
    {
        \Stripe\Stripe::setApiKey($api_key);

        $data = [
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
            'owner' => [
                'email' => $email,
                'name' => $name
            ],
            'redirect' => [
                'return_url' => $ret_url
            ]
        ];

        if(!empty($options)){
            $data = array_merge_recursive($data,$options);
        }

        try{
            $response = \Stripe\Source::create($data);
            return $response;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function createCharge($api_key,$amount,$currency,$wh_skey,$descr='')
    {
        \Stripe\Stripe::setApiKey($api_key);

        $response = \Stripe\Charge::create([
            'amount' => $amount,
            'currency' => $currency,
            'description' => 'IPEFIX SOLUTION PAYMENT',
            'source' => $wh_skey,
            'statement_descriptor' => $descr
        ]);

        return $response;

    }

}