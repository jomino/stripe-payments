<?php

namespace App\Controllers;

use \App\Models\User;
use \App\Models\Event;

class StripeWebhookController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $error = '';

        $wh_evt = $request->getBody();
        $wh_sig = $request->getHeaderLine('stripe-signature');

        $token = $args['token'];

        try{
            $user = User::where('uuid',$token)->firstOrFail();
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            $result = [
                'status' => 'failed',
                'error' => 'wh_not_registered'
            ];
            return $response->withJson($result)->withStatus(200);
        }

        $api_key = $user->skey;
        $wh_skey = $user->wkey;

        if($event_req=\Util\StripeUtility::createEvent($api_key,$wh_skey,$wh_sig,$wh_evt)){

            $type = $event_req['type'];
            $object = $event_req['data']['object'];

            $result = ['status' => 'success'];

            switch($object['object']){
                case \Util\StripeUtility::EVENT_OBJECT_SOURCE:
                    if($event=$this->getEvent($token,$object['id'])){
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
                                $error = $type==\Util\StripeUtility::EVENT_SOURCE_CANCELED ? 'Payement annulé' : 'Payement rejeté';
                                $send = $this->sendClientMail($event,$user,$error);
                                if(is_string($send)){
                                    $this->logger->info('['.(self::class).']',[$send]);
                                }
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
                    if($event=$this->getEvent($token,$object['payment_method'])){
                        if($event->status==\Util\StripeUtility::STATUS_CHARGEABLE){
                            if($type==\Util\StripeUtility::EVENT_CHARGE_SUCCEEDED){
                                $event->status = \Util\StripeUtility::STATUS_SUCCEEDED;
                                $event->save();
                                $send = $this->sendUserMail($event,$user);
                                if(is_string($send)){
                                    $this->logger->info('['.self::class.']',[$send]);
                                }
                            }
                            if($type==\Util\StripeUtility::EVENT_CHARGE_PENDING){
                                $event->status = \Util\StripeUtility::STATUS_WAITING;
                                $event->save();
                            }
                            if($type==\Util\StripeUtility::EVENT_CHARGE_FAILED){
                                $event->status = \Util\StripeUtility::STATUS_FAILED;
                                $error = 'Payement rejeté';
                                $event->save();
                            }
                            $send = $this->sendClientMail($event,$user,$error);
                            if(is_string($send)){
                                $this->logger->info('['.self::class.']',[$send]);
                            }
                        }else{
                            $result = ['status'=>'failed','error'=>'charge_already_succeeded'];
                            $this->logger->info('['.self::class.']',$result);
                        }
                    }else{
                        $result = ['status'=>'failed','error'=>'event_not_found'];
                        $this->logger->info('['.self::class.']',$result);
                    }
                break;
                default:
                    $result = ['status'=>'success','message'=>'event_not_registered'];
            }
        
            return $response->withJson($result)->withStatus(200);

        }else{
            $result = [
                'status' => 'failed',
                'error' => 'invalid_request'
            ];
            return $response->withJson($result)->withStatus(403);
        }
    }

    private function createChargeFromSource($api_key,$object)
    {
        try{
            $amount = $object['amount'];
            $currency = \Util\StripeUtility::DEFAULT_CURRENCY;
            $src_key = $object['id'];
            $response = \Util\StripeUtility::createCharge($api_key,$amount,$currency,$src_key);
            return $response;
        }catch (\Exception $e) {
            return null;
        }

    }

    private function getEvent($token,$skey)
    {
        try{
            $event = Event::where('skey',$skey)->firstOrFail();
            if($event->uuid==$token){
                return $event;
            }
            return null;
        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return null;
        }
    }

    private function sendClientMail($event,$user,$error='')
    {
        $status = $event->status;
        $event_tpl = [
            \Util\StripeUtility::STATUS_SUCCEEDED => 'Email/email-pay-succeed.html.twig',
            \Util\StripeUtility::STATUS_WAITING => 'Email/email-pay-pending.html.twig',
            \Util\StripeUtility::STATUS_FAILED => 'Email/email-pay-rejected.html.twig'
        ];

        $subject_tpl = [
            \Util\StripeUtility::STATUS_SUCCEEDED => $user->name.': Merci pour votre achat',
            \Util\StripeUtility::STATUS_WAITING => $user->name.': Votre payement est en cours de traitement',
            \Util\StripeUtility::STATUS_FAILED => $user->name.': '.$error
        ];

        $template = $event_tpl[$status];
        $subject = $subject_tpl[$status];

        $event_date = \Carbon\Carbon::createFromFormat('Y-m-d h:i:s', $event->updated_at);

        $amount = number_format((float) $event->amount/100, 2, ',', ' ');
        
        $data = [
            'name' => $event->name,
            'product' => $event->product,
            'method' => $event->method,
            'client_name' => $user->name,
            'client_email' => $user->email,
            'amount' => $amount.' &euro;',
            'token' => $event->token,
            'datetime' => $event_date->format('d/m/Y h:i:s'),
            'error' => $error
        ];
        
        $content = $this->view->fetch($template,$data);

        $mailer = new \Util\PhpMailer();
        return $mailer->send($event->email,$subject,$content);

    }

    private function sendUserMail($event,$user)
    {
        $template = 'Email/email-pay-recept.html.twig';
        $subject = 'Un nouveau payement est arrivé';

        $event_date = \Carbon\Carbon::createFromFormat('Y-m-d h:i:s', $event->updated_at);

        $amount = number_format((float) $event->amount/100, 2, ',', ' ');
        
        $data = [
            'product' => $event->product,
            'method' => $event->method,
            'client_name' => $event->name,
            'client_email' => $event->email,
            'amount' => $amount.' &euro;',
            'token' => $event->token,
            'datetime' => $event_date->format('d/m/Y h:i:s')
        ];
        
        $content = $this->view->fetch($template,$data);


        $mailer = new \Util\PhpMailer();
        return $mailer->send($user->email,$subject,$content);
    }
}
