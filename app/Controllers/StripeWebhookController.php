<?php

namespace App\Controllers;

use \App\Models\User;
use \App\Models\Event;

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

            $result = ['status' => 'success'];

            switch($object['object']){
                case \Util\StripeUtility::EVENT_OBJECT_SOURCE:
                    if($event=$this->getSourceEvent($token,$object)){
                        if($event->status==\Util\StripeUtility::STATUS_PENDING){
                            if($type==\Util\StripeUtility::EVENT_SOURCE_CHARGEABLE){
                                if($charge=$this->createChargeFromSource($api_key,$object)){
                                    $event->ckey = $charge->id;
                                    $event->status = \Util\StripeUtility::STATUS_CHARGEABLE;
                                    $event->save();
                                }
                            }
                            if($type==\Util\StripeUtility::EVENT_SOURCE_CANCELED || $type==\Util\StripeUtility::EVENT_SOURCE_FAILED){
                                $event->status = \Util\StripeUtility::STATUS_FAILED;
                                $event->save();
                            }
                        }else{
                            $result = [
                                'status' => 'failed',
                                'error' => 'source_already_charged'
                            ];
                        }
                    }else{
                        $result = [
                            'status' => 'failed',
                            'error' => 'database_not_ready'
                        ];
                    }
                break;
                case \Util\StripeUtility::EVENT_OBJECT_CHARGE:
                    if($event=$this->getSourceEvent($token,$object)){
                        if($event->status==\Util\StripeUtility::STATUS_CHARGEABLE){
                            if($type==\Util\StripeUtility::EVENT_CHARGE_SUCCEEDED){
                                $event->status = \Util\StripeUtility::STATUS_SUCCEEDED;
                                $event->save();
                            }
                            if($type==\Util\StripeUtility::EVENT_CHARGE_FAILED){
                                $event->status = \Util\StripeUtility::STATUS_FAILED;
                                $event->save();
                            }
                        }else{
                            $result = [
                                'status' => 'failed',
                                'error' => 'charge_already_succeeded'
                            ];
                        }
                    }else{
                        $result = [
                            'status' => 'failed',
                            'error' => 'database_not_ready'
                        ];
                    }
                break;
                default:
                    $result = [
                        'status' => 'success',
                        'error' => 'event_not_registered'
                    ];
            }

            $this->logger->info(self::class,$result);
        
            return $response->withJson($result)->withStatus(200);

        }else{
            $result = [
                'status' => 'failed',
                'error' => 'api_key_notfound'
            ];
            return $response->withJson($result)->withStatus(403);
        }
    }

    private function createChargeFromSource($api_key,$object)
    {
        try{
            $owner = $object['owner'];
            $amount = $object['amount'];
            $currency = \Util\StripeUtility::DEFAULT_CURRENCY;
            $src_key = $object['id'];
            $descr = $owner['email'].' '.$owner['name'];
            $response = \Util\StripeUtility::createCharge($api_key,$amount,$currency,$src_key,$descr);
            return $response;
        }catch (\Exception $e) {
            return null;
        }

    }

    private function getSourceEvent($token,$object)
    {
        if($event=Event::where('skey',$object['id'])->first()){
            if($event->uuid==$token && $event->amount==$object['amount']){
                return $event;
            }
        }
        return null;
    }

    private function setSourceEvent($event,$charge)
    {

    }
}
