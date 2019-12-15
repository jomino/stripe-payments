<?php

namespace Util;

class StripeUtility
{
    const METHOD_BANCONTACT = 'bancontact';
    const METHOD_SOFORT = 'sofort';
    const METHOD_IDEAL = 'ideal';
    const METHOD_IBAN = 'iban';
    const METHOD_CARD = 'card';

    const DEFAULT_IDEAL_BANK = 'ing';

    const DEFAULT_CURRENCY = 'eur';
    const DEFAULT_COUNTRY = 'BE';

    const SESSION_LOGIN = 'client_login';
    const SESSION_REMOTE = 'remote';
    const SESSION_DOMAIN = 'domain';
    const SESSION_REFERRER = 'referrer';
    const SESSION_SELECTION = 'selection';
    const SESSION_AMOUNT = 'amount';
    const SESSION_PRODUCT = 'product_ref';
    const SESSION_METHOD = 'payment_type';
    const SESSION_TOKEN = 'event_token';
    const SESSION_CLIENT_SECRET = 'client_secret';
    const SESSION_SUCCESS_URL = 'success_url';
    const SESSION_CANCEL_URL = 'cancel_url';

    const STATUS_PENDING = 'pending';
    const STATUS_CHARGEABLE = 'chargeable';
    const STATUS_WAITING = 'waiting';
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_FAILED = 'failed';
    
    const WEBHOOK_STATUS_ENABLED = 'enabled';

    public static function createWebhook($api_key,$wh_url)
    {
        \Stripe\Stripe::setApiKey($api_key);

        try {
            $response = \Stripe\WebhookEndpoint::create([
                'url' => $wh_url,
                'enabled_events' => [
                    static::EVENT_CHARGE_FAILED,
                    static::EVENT_CHARGE_PENDING,
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

    public static function retrieveSource($api_key,$skey)
    {
        \Stripe\Stripe::setApiKey($api_key);

        try{
            $response = \Stripe\Source::retrieve($skey);
            return $response;
        } catch (\Exception $e) {
            return null;
        }

    }

    public static function createCharge($api_key,$amount,$currency,$src_key,$options=[])
    {
        \Stripe\Stripe::setApiKey($api_key);

        $data = [
            'amount' => $amount,
            'currency' => $currency,
            'description' => 'IPEFIX SOLUTION PAYMENT',
            'source' => $src_key
        ];

        if(!empty($options)){
            $data = array_merge_recursive($data,$options);
        }

        try{
            $response = \Stripe\Charge::create($data);
            return $response;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function createIntent($api_key,$amount,$currency,$options=[])
    {
        \Stripe\Stripe::setApiKey($api_key);

        $data = [
            'amount' => $amount,
            'currency' => $currency,
            'payment_method_types' => ['card']
        ];

        if(!empty($options)){
            $data = array_merge_recursive($data,$options);
        }

        try{
            $response = \Stripe\PaymentIntent::create($data);
            return $response;
        } catch (\Exception $e) {
            return null;
        }
    }

}