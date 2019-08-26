<?php

namespace App\Controllers;

class LoginController extends \Core\Controller  
{
    private $errors = [];

    public function __invoke($request, $response, $args)
    {
        $ip = $request->getServerParam('REMOTE_ADDR');
        $email = $request->getParsedBodyParam('login');
        $pwd = $request->getParsedBodyParam('pwd');
        if($client=$this->getClient($email,$pwd)){
            $this->session->set(\Util\StripeUtility::SESSION_LOGIN,$client->login);
            $pass_phrase = $client->email.'-'.\App\Parameters::SECURITY['secret'];
            $response = \Dflydev\FigCookies\FigResponseCookies::set($response, \Dflydev\FigCookies\SetCookie::create(\App\Parameters::SECURITY['cookie'])
                ->withPath('/')
                ->withValue(hash('sha256', $pass_phrase))
                ->withMaxAge(30*60)
                ->withDomain($request->getUri()->getHost())
                ->withSecure(true)
                ->withHttpOnly(true)
            );
            $this->logger->info('['.$ip.'] ADMIN_LOGIN_SUCCESS -> LOGIN:'.$client->login);
            return $response->withRedirect($this->router->pathFor('adduser'));
        }
        $this->logger->info('['.$ip.'] ADMIN_LOGIN_ERROR -> ERRORS:'.implode(',',$this->errors));
        return $response->withRedirect($this->router->pathFor('home'));
    }

    private function getClient($email,$pwd)
    {
        try{
            $client = \App\Models\Client::where('email',$email)->firstOrFail();
            if($client->pwd==hash('sha256', $pwd)){
                if($client->activ==1){
                    return $client;
                }else{
                    $this->errors[] = 'Vous n\'êtes pas un utilisateur enregistrer chez nous';
                    return null;
                }
            }else{
                $this->errors[] = 'Le mot de passe est incorrecte';
                return null;
            }
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            $this->errors[] = 'Vous n\'êtes pas un utilisateur enregistrer chez nous';
            return null;
        }catch(\Exception $e){
            $this->errors[] = 'Erreur inattendue';
            return null;
        }
    }
}