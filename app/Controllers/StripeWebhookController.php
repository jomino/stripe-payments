<?php

namespace App\Controllers;

use \App\Models\User;

class StripeWebhookController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $wh_evt = $request->getBody();
        $parsed_body = $request->getParsedBody();
        $wh_sig = $request->getHeaderLine('stripe-signature');

        $token = ltrim($parsed_body['token'],'?');
        $user = User::where('uuid',$token)->first();
        $api_key = $user->skey;
        $wh_skey = $user->wkey;

        if($event=\Util\StripeUtility::createEvent($api_key,$wh_skey,$wh_sig,$wh_evt)){

            $type = $event['type'];
            $object = $event['data']['object'];

        }else{
            return $response->withJson([ 'status' => 'failed' ])->withStatus(403);
        }
        
        return $response->withJson([ 'status' => 'success' ])->withStatus(200);
    }
}
