<?php

namespace App\Controllers;

use \App\Models\User;

class RegisterUserController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $uri = $request->getUri();
        $token = (string) ltrim($uri->getQuery(),'?');
        if(empty($token) || strlen($token)<2){ $token = ltrim($args['token'],'?'); }
        $user_id = (int) $args['id']??0;
        if(!empty($token) && ($user=$this->validate($token,$user_id) )){
            $datas = [ 'agence' => $user->name, 'email' => $user->email ];
            if($request->isGet()){
                $template_name = $route_name = 'register';
                array_merge( $datas, [
                    'generated_link' => $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor($route_name,[
                        'id' => $user->id,
                        'token' => '?'.$user->uuid
                    ])
                ]);
            }else{
                if(false === $request->getAttribute('csrf_status')){
                    return $response->withStatus(498);
                }else{
                    $datas = [];
                    $route_name = 'webhook';
                    if($this->register($user,$request->getParsedBody())){
                        $template_name = 'registered';
                        $webhook_url = $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor( $route_name, [
                            'token' => '?'.$user->uuid
                        ]);
                        /* if(!$this->setupWebhook($user,$webhook_url)){
                            $template_name = 'registered-fail';
                        } */
                    }else{ $template_name = 'registered-fail'; }
                }
            }
            return $this->view->render($response, sprintf('Home/%s.html.twig',$template_name), $datas);
        }else{
            //todo return error info
            return $this->view->render($response, 'Home/register-fail.html.twig',[
                'agence' => $user->name??'unknow',
                'email' => $user->email??'unknow',
                'token' => $user->uuid??'unknow'
            ]);
        }
    }

    private function validate($token,$user_id)
    {
        try{
            $user = User::find($user_id);
            if($user->uuid == $token){
                //todo: add date max ctrl
                //todo: add active==0 ctrl
                return $user;
            }else{
                //todo return error info
                return null;
            }
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            //todo return error info
            return null;
        }
    }

    private function register($user,$args)
    {

        $pkey = $args['pkey'];
        $skey = $args['skey'];

        if(!empty($pkey) && !empty($skey)){
            if(preg_match('#^pk_#',$pkey) && preg_match('#^sk_#',$skey)){
                $user->pkey = $pkey;
                $user->skey = $skey;
                $user->active = 1;
                $user->save();
                return true;
            }
        }
        //todo return error info
        return false;

    }

    private function setupWebhook($user,$url)
    {
        $key = $user->skey;
        $response = \Util\StripeUtility::createWebhook($key,$url);
        if(!empty($response)){
            $user->wkey = $response->secret;
            $user->save();
            return true;
        }
        return false;
    }
}
