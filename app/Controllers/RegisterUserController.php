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
            $datas = [
                'agence' => $user->name,
                'email' => $user->email,
                'generated_link' => $uri->getScheme().'://'.rtrim($uri->getHost(),'/').$this->router->pathFor('register',[
                    'id' => $user->id,
                    'token' => '?'.$token
                ]
            )];
            if($request->isGet()){
                return $this->view->render($response, 'Home/register.html.twig', $datas);
            }else{
                if(false === $request->getAttribute('csrf_status')){
                    return $response->withStatus(498);
                }else{
                    $body = $request->getParsedBody();
                    if($this->register($user,$body)){ $_tpl = 'registered'; }
                    else{ $_tpl = 'registered-fail'; }
                    return $this->view->render($response, sprintf('Home/%s.html.twig',$_tpl), $datas);
                }
            }
        }else{
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
                return null;
            }
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
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

        return false;

    }
}
