<?php

namespace App\Controllers;

class StripePaymentController extends \Core\Controller
{
    public function start($request, $response, $args)
    {
        $uri = $request->getUri();
        $amount = $args['amount'];
        $product = $args['product'];
        $token = (string) ltrim($uri->getQuery(),'?');
        $ip = $request->getServerParam('REMOTE_ADDR');
        if(empty($token) || strlen($token)<2){ $token = ltrim($args['token'],'?'); }
        $this->setSessionVar(\Util\StripeUtility::SESSION_REFERRER,$token);
        $this->setSessionVar(\Util\StripeUtility::SESSION_REMOTE,$ip);
        if($this->isValidUser()){
            $this->logger->info('['.$ip.'] PAYMENT_START_SUCCEED');
            $this->setSessionVar(\Util\StripeUtility::SESSION_AMOUNT,$amount);
            $this->setSessionVar(\Util\StripeUtility::SESSION_PRODUCT,$product);
            $display_amount = number_format((float) $amount/100, 2, ',', ' ');
            return $this->view->render($response, 'Home/paystart.html.twig',[
                'product' => $product,
                'amount' => $display_amount.' &euro;'
            ]);
        }else{
            $this->logger->info('['.$ip.'] PAYMENT_START_ERROR -> INVALID_USER');
            return $response->write($this->getSecurityAlert());
        }
    }

    public function identify($request, $response, $args)
    {
        $ip = $this->session->get(\Util\StripeUtility::SESSION_REMOTE);
        if(false === $request->getAttribute('csrf_status')){
            $this->logger->info('['.$ip.'] PAYMENT_CSRF_ERROR -> EXIT_WITH_403');
            return $response->write($this->getSecurityAlert())->withStatus(403);
        }
        $payment_type = $request->getParsedBodyParam('payment-type');
        $this->setSessionVar(\Util\StripeUtility::SESSION_METHOD,$payment_type);
        $this->logger->info('['.$ip.'] PAYMENT_START_IDENTIFY -> METHOD_TYPE: '.$payment_type);
        switch($payment_type){
            case \Util\StripeUtility::METHOD_CARD:
                $intent = $this->getIntent();
                if($intent->client_secret){
                    $this->setSessionVar(\Util\StripeUtility::SESSION_CLIENT_SECRET,$intent->client_secret);
                    $s_token = $this->session->get(\Util\StripeUtility::SESSION_TOKEN);
                    $data = [
                        'post_url' => '#',
                        'redir_url' => $this->getReturnUrl($request->getUri(),$s_token)
                    ];
                }else{
                    return $response->write($this->getSecurityAlert())->withStatus(403);
                }
            break;
            case \Util\StripeUtility::METHOD_IBAN:
                $data = [ 'post_url' => $this->router->pathFor('payment_charge')];
            break;
            default:
                $data = [ 'post_url' => $this->router->pathFor('payment_source')];
        }
        return $this->view->render($response, 'Home/payidentify.html.twig', $data);
    }

    public function source($request, $response, $args)
    {
        $ip = $this->session->get(\Util\StripeUtility::SESSION_REMOTE);
        if(false === $request->getAttribute('csrf_status')){
            $this->logger->info('['.$ip.'] PAYMENT_CSRF_ERROR -> EXIT_WITH_403');
            return $response->write($this->getSecurityAlert())->withStatus(403);
        }
        $method = $this->session->get(\Util\StripeUtility::SESSION_METHOD);
        if(in_array($method.'-selection',array_keys($request->getParsedBody()))){
            $this->setSessionVar(\Util\StripeUtility::SESSION_SELECTION,$request->getParsedBodyParam($method.'-selection'));
        }
        $name = $request->getParsedBodyParam('name');
        $email = $request->getParsedBodyParam('email');
        $forced = !empty($request->getParsedBodyParam('forced'));
        if(!empty($name) && !empty($email)){
            if($user=$this->getCurrentUser()){
                if($forced){
                    $name = base64_decode($name);
                    $email = base64_decode($email);
                }
                if($source=$this->getSource($request,$user,$email,$name)){
                    if($source->redirect->status==\Util\StripeUtility::STATUS_PENDING){
                        $this->logger->info('['.$ip.'] SOURCE_OBJ_CREATED -> STATUS_'.(\Util\StripeUtility::STATUS_PENDING));
                        $redir_url = $source->redirect->url;
                        return $this->view->render($response, 'Home/payredir.html.twig',[
                            'redir_url' => $redir_url
                        ]);
                    }elseif($forced){
                        $this->logger->info('['.$ip.'] SOURCE_OBJ_ALERT -> FORCED_MODE_RETRY');
                        $source = $this->createNewSource($request,$user,$email,$name);
                        $redir_url = $source->redirect->url;
                        return $this->view->render($response, 'Home/payredir.html.twig',[
                            'redir_url' => $redir_url
                        ]);
                    }else{
                        $event = $this->getCurrentEvent();
                        $dt_evt = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $event->updated_at);
                        $dt_str = $dt_evt->format('l d/m/Y à H:i:s');
                        $alert = '<h4 class="result mid-yelo">Message préventif  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></h4>';
                        $message = 'Tout semble indiqué qu\'un achat similaire a déjà été effectué ce '.$dt_str.'.Vous pouvez fermez cette page ou poursuivre votre achat en cliquant sur le bouton "Continuer".';
                        $message .= '<input type="hidden" name="forced" value="force">'."\n";
                        $message .= '<input type="hidden" name="name" value="'.base64_encode($name).'">'."\n";
                        $message .= '<input type="hidden" name="email" value="'.base64_encode($email).'">'."\n";
                        $message .= '<br><button type="submit" class="btn btn-default btn-sm bold">Continuer</button>';
                        $this->logger->info('['.$ip.'] SOURCE_OBJ_ALERT -> PRODUCT_ALREADY_SUCCEEDED');
                    }
                }else{
                    $alert = '<h4 class="result mid-red">Une erreur est survenue  <span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span></h4>';
                    $message = $this->getDefaultError($user);
                    $this->logger->info('['.$ip.'] CANNOT_CREATE_SOURCE_OBJ -> NO_USER_NOR_DB');
                }
            }else{
                $alert = '<h4 class="result mid-red">Impossible de retrouver le profil client  <span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span></h4>';
                $message = $this->getDefaultError();
                $this->logger->info('['.$ip.'] PAYMENT_IDENTITY_ERROR -> NO_USER_NOR_DB');
            }
        }else{
            $alert = '<h4 class="result mid-red">Votre nom et e-mail sont requis  <span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span></h4>';
            $message = $this->getDefaultError();
            $this->logger->info('['.$ip.'] PAYMENT_IDENTITY_ERROR -> NO_NAME_NOR_EMAIL');
        }
        return $this->view->render($response, 'Home/paymess.html.twig',[
            'alert' => $alert,
            'message' => $message
        ]);
    }

    public function charge($request, $response, $args)
    {
        $ip = $this->session->get(\Util\StripeUtility::SESSION_REMOTE);
        if(false === $request->getAttribute('csrf_status')){
            $this->logger->info('['.$ip.'] PAYMENT_CSRF_ERROR -> EXIT_WITH_403');
            return $response->write($this->getSecurityAlert())->withStatus(403);
        }
        $method = $this->session->get(\Util\StripeUtility::SESSION_METHOD);
        if(in_array($method.'-selection',array_keys($request->getParsedBody()))){
            $method_selection = $request->getParsedBodyParam($method.'-selection');
            $this->setSessionVar(\Util\StripeUtility::SESSION_SELECTION,$method_selection);
        }
        $name = $request->getParsedBodyParam('name');
        $email = $request->getParsedBodyParam('email');
        if(!empty($name) && !empty($email)){

            if(!($event=$this->getEventFromSource($method_selection))){
                $s_token = \Util\UuidGenerator::v4();
                $status = \Util\StripeUtility::STATUS_PENDING;
                $uuid = $this->session->get(\Util\StripeUtility::SESSION_REFERRER);
                $amount = $this->session->get(\Util\StripeUtility::SESSION_AMOUNT);
                $product = $this->session->get(\Util\StripeUtility::SESSION_PRODUCT);
                $event = $this->createNewEvent($status,$uuid,$name,$email,$amount,$product,$method,$method_selection,$s_token);
            }

            $redir_url = $this->getReturnUrl($request->getUri(),$s_token);

            return $this->view->render($response, 'Home/payredir.html.twig',[
                'redir_url' => $redir_url
            ]);

        }
    }

    public function result($request, $response, $args)
    {
        $ip = $request->getServerParam('REMOTE_ADDR');
        $event = $this->getCurrentEvent($args['token']);
        $user = $this->getCurrentUser($event->uuid);
        $method = $event->method;
        $event_date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $event->updated_at);
        $amount = number_format((float) $event->amount/100, 2, ',', ' ');
        $message = '<strong>Produit:</strong> '.$event->product.'<br>';
        $message .= '<strong>Methode:</strong> '.ucfirst($method).'<br>';
        $message .= '<strong>Date:</strong> '.$event_date->format('d/m/Y H:i:s').'<br>';
        $message .= '<strong>Bénéficiaire:</strong> '.$user->name.'<br>';
        $message .= '<strong>Montant:</strong> '.$amount.' &euro;<br>';
        $message .= '<strong>ID transaction:</strong> '.$event->token;
        $this->logger->info('['.$ip.'] RECEIVE_PAYMENT_RESULT');
        return $this->view->render($response, 'Home/payresult.html.twig',[
            'bank_logo' => $method,
            'message' => $message,
            'status' => $event->status,
            'check_url' => $event->token
        ]);
    }

    public function check($request, $response, $args)
    {
        $ip = $this->session->get(\Util\StripeUtility::SESSION_REMOTE);
        $event = $this->getCurrentEvent($args['token']);
        $status = $event->status;
        $title = '';
        if($status==\Util\StripeUtility::STATUS_SUCCEEDED){
            $title = 'Merci, votre payement nous est bien arrivé.';
        }
        if($status==\Util\StripeUtility::STATUS_WAITING){
            $title = 'Merci, votre payement est en cour de traitement.';
        }
        if($status==\Util\StripeUtility::STATUS_FAILED){
            $title = 'Désolé, votre payement ne nous est pas parvenu.';
        }
        $this->logger->info('['.$ip.'] CHECK_PAYMENT_RESPONSE: STATUS -> '.$status);
        return $response->withJson([
            'status' => $title
        ]);
    }

    public function print($request, $response, $args)
    {
        $ip = $this->session->get(\Util\StripeUtility::SESSION_REMOTE);
        $this->logger->info('['.$ip.'] PRINT_PAYMENT_RESULT');
        $event = $this->getCurrentEvent($args['token']);
        $user = $this->getCurrentUser();
        $html = $this->getPrintContent($event,$user);
        return $response->withJson([
            'html' => \base64_encode($html)
        ]);
    }

    private function getPrintContent($event,$user)
    {
        $status = $event->status;
        $event_tpl = [
            \Util\StripeUtility::STATUS_SUCCEEDED => 'Email/email-pay-succeed.html.twig',
            \Util\StripeUtility::STATUS_WAITING => 'Email/email-pay-pending.html.twig',
            \Util\StripeUtility::STATUS_FAILED => 'Email/email-pay-rejected.html.twig'
        ];

        $template = $event_tpl[$status];

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
            'error' => ''
        ];
        
        $content = $this->view->fetch($template,$data);

        return $content;

    }

    private function getDefaultError($user='')
    {
        $message = 'Une erreur inattendue est survenue. ';
        $message .= 'Nous sommes actuellement dans l\'incapacité de ';
        $message .= 'vous redirigé vers la page de votre banque.';
        $message .= 'Veuillez ré-essayer plus tard, merci.<br><br>';
        if(!empty($user)){
            $message .= '<a href="//:'.$user->name.'" title="'.$user->name.'">';
            $message .= 'Retournez vers le site marchant';
            $message .= '</a>';
        }
        return $message;
    }

    private function getSecurityAlert()
    {
        $alert = '<h4 class="result mid-red">Alerte de sécurité  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></h4>';
        $message = 'Il nous est impossible de valider votre demande.<br>';
        $message .= 'Cela peut arriver dans les cas suivants:<br>';
        $message .= '&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;Une tentative de ré-utilisation d\'un formulaire.<br>';
        $message .= '&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;Un autre problème d\'ordre technique.<br>';
        $message .= 'Vous pouvez contacter nos services à l\'adresse <a href="mailto:info@ipefix.com">info@ipefix.com</a>';
        $content = $this->view->fetch('Home/paymess.html.twig',[
            'alert' => $alert,
            'message' => $message
        ]);
        return $content;
    }

    private function setSessionVar($name,$value)
    {
        if($this->session->exists($name)){
            $this->session->delete($name);
        }
        $this->session->set($name,$value);
    }

    private function getCurrentUser($token='')
    {
        if(!empty($token)){
            $s_token = $token;
        }elseif($this->session->exists(\Util\StripeUtility::SESSION_REFERRER)){
            $s_token = $this->session->get(\Util\StripeUtility::SESSION_REFERRER);
        }
        try{
            $user = \App\Models\User::where('uuid',$s_token)->firstOrFail();
            return $user;
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return null;
        }
    }

    private function isValidUser()
    {
        if($this->session->exists(\Util\StripeUtility::SESSION_DOMAIN)){
            $domain = $this->session->get(\Util\StripeUtility::SESSION_DOMAIN);
            if($user=$this->getCurrentUser()){
                return $user->name==$domain && (int) $user->active==1;
            }
        }
        return false;
    }

    private function getCurrentEvent($token='')
    {
        if(!empty($token)){
            $s_token = $token;
        }elseif($this->session->exists(\Util\StripeUtility::SESSION_TOKEN)){
            $s_token = $this->session->get(\Util\StripeUtility::SESSION_TOKEN);
        }
        try{
            $event = \App\Models\Event::where('token',$s_token)->firstOrFail();
            return $event;
        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return null;
        }
        return null;
    }

    private function getEventFromSource($skey)
    {
        try{
            $event = \App\Models\Event::where('skey',$skey)->firstOrFail();
            return $event;
        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return null;
        }
        return null;
    }

    private function getIntent()
    {
        if($user=$this->getCurrentUser()){
            $s_token = \Util\UuidGenerator::v4();
            $method = $this->session->get(\Util\StripeUtility::SESSION_METHOD);
            $amount = $this->session->get(\Util\StripeUtility::SESSION_AMOUNT);
            $product = $this->session->get(\Util\StripeUtility::SESSION_PRODUCT);
            $currency = \Util\StripeUtility::DEFAULT_CURRENCY;
            $intent = \Util\StripeUtility::createIntent($user->skey,$amount,$currency);
            $intent_id = $intent->id;
            $intent_status = $intent->status;
            $this->createNewEvent($intent_status,$user->uuid,'','',$amount,$product,$method,$intent_id,$s_token);
            return $intent;
        }
        return null;
    }

    private function getSource($request,$user,$email,$name)
    {
        if($event=$this->getCurrentEvent()){
            $source = \Util\StripeUtility::retrieveSource($user->skey,$event->skey);
        }else{
            $source = $this->createNewSource($request,$user,$email,$name);
        }
        return $source;
    }

    private function getReturnUrl($uri,$token)
    {
        $route_name = 'payment_result';
        return $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor($route_name,[
            'token' => $token
        ]).'?l='.$this->language;
    }

    private function getSourceOptions($bank,$user)
    {
        $statement_descriptor = \strtoupper($user->name);
        switch($bank){
            case \Util\StripeUtility::METHOD_BANCONTACT:
                return [
                    \Util\StripeUtility::METHOD_BANCONTACT => [
                        'preferred_language' => $this->language,
                        'statement_descriptor' => $statement_descriptor
                    ]
                ];
            case \Util\StripeUtility::METHOD_SOFORT:
                return [
                    \Util\StripeUtility::METHOD_SOFORT => [
                        'country' => \Util\StripeUtility::DEFAULT_COUNTRY,
                        'preferred_language' => $this->language,
                        'statement_descriptor' => $statement_descriptor
                    ]
                ];
            case \Util\StripeUtility::METHOD_IDEAL:
                return [
                    \Util\StripeUtility::METHOD_IDEAL => [
                        'bank' => $this->session->get(\Util\StripeUtility::SESSION_SELECTION),
                        'statement_descriptor' => $statement_descriptor
                    ]
                ];
        }
    }

    private function createNewSource($request,$user,$email,$name)
    {
        $s_token = \Util\UuidGenerator::v4();
        $method = $this->session->get(\Util\StripeUtility::SESSION_METHOD);
        $amount = $this->session->get(\Util\StripeUtility::SESSION_AMOUNT);
        $product = $this->session->get(\Util\StripeUtility::SESSION_PRODUCT);
        $currency = \Util\StripeUtility::DEFAULT_CURRENCY;
        $ret_url = $this->getReturnUrl($request->getUri(),$s_token);
        $options = $this->getSourceOptions($method,$user);
        $source = \Util\StripeUtility::createSource($user->skey,$method,$amount,$currency,$email,$name,$ret_url,$options);
        $src_id = $source->id;
        $src_status = $source->status==\Util\StripeUtility::STATUS_PENDING ? \Util\StripeUtility::STATUS_PENDING : \Util\StripeUtility::STATUS_FAILED;
        $this->createNewEvent($src_status,$user->uuid,$name,$email,$amount,$product,$method,$src_id,$s_token);
        return $source;
    }

    private function createNewEvent($status,$uuid,$name='',$email='',$amount,$product,$method,$skey,$s_token)
    {
        try{
            $event = new \App\Models\Event();
            $event->status = $status;
            $event->uuid = $uuid;
            $event->name = $name;
            $event->email = $email;
            $event->amount = $amount;
            $event->product = $product;
            $event->method = $method;
            $event->token = $s_token;
            $event->skey = $skey;
            $event->save();
            $this->setSessionVar(\Util\StripeUtility::SESSION_TOKEN,$s_token);
            return true;
        }catch(\Exception $e){
            return false;
        }
    }
}
