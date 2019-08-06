<?php

namespace App\Controllers;

use \App\Models\User;

class StripeWebhookController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $wh_evt = $request->getBody();
        $wh_sig = $request->getHeaderLine('stripe-signature');

        $token = $args['token'];
        $user = User::where('uuid',$token)->first();
        $api_key = $user->skey;
        $wh_skey = $user->wkey;

        if($event=\Util\StripeUtility::createEvent($api_key,$wh_skey,$wh_sig,$wh_evt)){

            $type = $event['type'];
            $object = $event['data']['object'];

            $result = [
                'object' => $object['object'],
                'type' => $type,
                'status' => $object['redirect']['status']
            ];
        
            return $response->withJson($result)->withStatus(200);

        }else{
            $result = [
                'status' => 'failed',
                'signature' => $wh_sig,
                'uuid' => $token,
                'skey' => $api_key,
                'whsec' => $wh_skey
            ];
            return $response->withJson($result)->withStatus(403);
        }
    }
}
