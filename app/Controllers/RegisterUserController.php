<?php

namespace App\Controllers;

use \App\Models\User;

class RegisterUserController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $uri = $request->getUri();
        $token = (string) ltrim($uri->getQuery(),'?');
        if(empty($token) || strlen($token)<2){
            $token = ltrim($args['token'],'?');
        }
        $user_id = (int) $args['id'];
        $this->logger->info('['.self::class.'::__invoke] user_id=>'.$user_id);
        $this->logger->info('['.self::class.'::__invoke] token=>'.$token);
        if($user=$this->validAndSave($token,$user_id)){
            return $this->view->render($response, 'Home/register.html.twig',[
                'agence' => $user->name,
                'email' => $user->email,
                'token' => $user->uuid
            ]);
        }else{
            return $this->view->render($response, 'Home/register-fail.html.twig',[
                'agence' => $user->name??'unknow',
                'email' => $user->email??'unknow',
                'token' => $user->uuid??'unknow'
            ]);
        }
    }

    private function validAndSave($token,$user_id)
    {
        try{
            $user = User::find($user_id);
            if($user->uuid == $token){
                $user->active = 1;
                $user->save();
                return $user;
            }else{
                return null;
            }
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return null;
        }
    }
}
