<?php

namespace App\Controllers;

use \App\Models\User;
use \App\Models\Event;

class StripeWebhookController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $ip = $request->getServerParam('REMOTE_ADDR');

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
                    if($event=$this->getEventFromSource($token,$object)){
                        if($event->status==\Util\StripeUtility::STATUS_PENDING){
                            if($type==\Util\StripeUtility::EVENT_SOURCE_CHARGEABLE){
                                if($charge=$this->createChargeFromSource($api_key,$object)){
                                    $this->logger->info('['.$ip.'] EVENT_SOURCE_RECEIVE -> STATUS_'.(\Util\StripeUtility::STATUS_CHARGEABLE));
                                    $event->ckey = $charge->id;
                                    $event->status = \Util\StripeUtility::STATUS_CHARGEABLE;
                                    $event->save();
                                }else{
                                    $this->logger->info('['.$ip.'] EVENT_SOURCE_ERROR -> CANNOT_CREATE_CHARGE');
                                }
                            }
                            if($type==\Util\StripeUtility::EVENT_SOURCE_CANCELED || $type==\Util\StripeUtility::EVENT_SOURCE_FAILED){
                                $event->status = \Util\StripeUtility::STATUS_FAILED;
                                $event->save();
                                $error = $type==\Util\StripeUtility::EVENT_SOURCE_CANCELED ? 'Payement annulé' : 'Payement rejeté';
                                $this->logger->info('['.$ip.'] EVENT_SOURCE_PROBLEM -> '.$type);
                                $send = $this->sendClientMail($event,$user,$error);
                                if(is_string($send)){
                                    $this->logger->info('['.$ip.'] ERROR_CLIENT_EMAIL',[$send]);
                                }else{
                                    $this->logger->info('['.$ip.'] CLIENT_EMAIL_SENDED: ADDRESS-> '.$event->email);
                                }
                            }
                        }else{
                            $result = [
                                'status' => 'failed',
                                'error' => 'source_already_charged'
                            ];
                            $this->logger->info('['.$ip.'] EVENT_SOURCE_ERROR',[$result]);
                        }
                    }else{
                        $result = [
                            'status' => 'failed',
                            'error' => 'event_not_found'
                        ];
                        $this->logger->info('['.$ip.'] EVENT_SOURCE_ERROR',[$result]);
                    }
                break;
                case \Util\StripeUtility::EVENT_OBJECT_CHARGE:
                    if($event=$this->getEventFromCharge($token,$object)){
                        if($event->status==\Util\StripeUtility::STATUS_CHARGEABLE){
                            if($type==\Util\StripeUtility::EVENT_CHARGE_SUCCEEDED){
                                $this->logger->info('['.$ip.'] EVENT_CHARGE_RECEIVE -> STATUS_'.(\Util\StripeUtility::STATUS_SUCCEEDED));
                                $event->status = \Util\StripeUtility::STATUS_SUCCEEDED;
                                $event->save();
                                $send = $this->sendUserMail($event,$user);
                                if(is_string($send)){
                                    $this->logger->info('['.$ip.'] ERROR_USER_EMAIL',[$send]);
                                }else{
                                    $this->logger->info('['.$ip.'] USER_EMAIL_SENDED: ADDRESS-> '.$user->email);
                                }
                            }
                            if($type==\Util\StripeUtility::EVENT_CHARGE_PENDING){
                                $this->logger->info('['.$ip.'] EVENT_CHARGE_RECEIVE -> STATUS_'.(\Util\StripeUtility::STATUS_WAITING));
                                $event->status = \Util\StripeUtility::STATUS_WAITING;
                                $event->save();
                            }
                            if($type==\Util\StripeUtility::EVENT_CHARGE_FAILED){
                                $this->logger->info('['.$ip.'] EVENT_CHARGE_PROBLEM -> STATUS_'.(\Util\StripeUtility::STATUS_FAILED));
                                $event->status = \Util\StripeUtility::STATUS_FAILED;
                                $error = 'Payement rejeté';
                                $event->save();
                            }
                            $send = $this->sendClientMail($event,$user,$error);
                            if(is_string($send)){
                                $this->logger->info('['.$ip.'] ERROR_CLIENT_EMAIL',[$send]);
                            }else{
                                $this->logger->info('['.$ip.'] CLIENT_EMAIL_SENDED: ADDRESS-> '.$event->email);
                            }
                        }else{
                            $result = ['status'=>'failed','error'=>'charge_already_succeeded'];
                            $this->logger->info('['.$ip.'] EVENT_CHARGE_ERROR',[$result]);
                        }
                    }else{
                        $result = ['status'=>'failed','error'=>'event_not_found'];
                        $this->logger->info('['.$ip.'] EVENT_CHARGE_ERROR',[$result]);
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
            $this->logger->info('['.$ip.'] FATAL_EVENT_ERROR: CANNOT_CREATE_EVENT_RECEPT -> RETURN 403');
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

    private function createNewEvent($uuid,$object)
    {
        try{
            $event = new \App\Models\Event();
            $event->status = \Util\StripeUtility::STATUS_PENDING;
            $event->uuid = $uuid;
            $event->name = $object['owner']['name']??'';
            $event->email = $object['owner']['email']??'';
            $event->amount = $object['amount']??0;
            $event->product = $object['metadata']['product']??'none';
            $event->method = $object['type'];
            $event->token = \Util\UuidGenerator::v4();
            $event->skey = $object['id'];
            $event->save();
            return $event;
        }catch(\Exception $e){
            return null;
        }
    }

    private function getEventFromSource($token,$obj)
    {
        if(!($event=$this->getEvent($token,$obj['id']))){
            $event = $this->createNewEvent($token,$obj);
        }
        return $event;
    }

    private function getEventFromCharge($token,$obj)
    {
        return $this->getEvent($token,$obj['payment_method']);
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

        $event_date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $event->updated_at);

        $amount = number_format((float) $event->amount/100, 2, ',', ' ');
        
        $data = [
            'name' => $event->name,
            'product' => $event->product,
            'method' => ucfirst($event->method),
            'client_name' => $user->name,
            'client_email' => $user->email,
            'amount' => $amount.' &euro;',
            'token' => $event->token,
            'datetime' => $event_date->format('d/m/Y H:i:s'),
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

        $event_date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $event->updated_at);

        $amount = number_format((float) $event->amount/100, 2, ',', ' ');
        
        $data = [
            'product' => $event->product,
            'method' => ucfirst($event->method),
            'client_name' => $event->name,
            'client_email' => $event->email,
            'amount' => $amount.' &euro;',
            'token' => $event->token,
            'datetime' => $event_date->format('d/m/Y H:i:s')
        ];
        
        $content = $this->view->fetch($template,$data);


        $mailer = new \Util\PhpMailer();
        return $mailer->send($user->email,$subject,$content);
    }
}
