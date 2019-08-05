<?php

namespace App\Controllers;

class AssetsController extends \Core\Controller
{
    private $paths = [
      'js' => 'text/javascript',
      'css' => 'text/css',
      'fonts' => 'application/octet-stream',
      'images' => FILEINFO_MIME_TYPE
    ];

    public function __invoke($request, $response, $args)
    {
        $assets = $this->settings['assets'];
        $resource = $assets['path'] . '/' . $args['path'] . '/' . $args['file'];
        $content_type = $this->paths[$args['path']];
        if($args['file']=='stripe-pkey.js'){
            $referrer = $this->session->get('referrer');
            return $response->write($this->getStripePkey($referrer))->withHeader('Content-Type', $content_type);
        }elseif(is_file($resource) && is_readable($resource)){
            return $response->write(file_get_contents($resource))
                ->withHeader('Content-Type', $content_type);
        }else{
            $notFoundHandler = $this->notFoundHandler;
            return $notFoundHandler($request, $response);
        }
    }

    private function getStripePkey($token)
    {
        if($user=$this->getUser($token)){
            $content = 'window.STRIPE_PUBLISHABLE_KEY="'.$user->pkey.'";'."\n";
            $content .= 'window.PUBLISHABLE_KEY_ERROR=false;';
        }else{
            $content = 'window.PUBLISHABLE_KEY_ERROR=true;'."\n";
            $content .= 'window.KEY_ERROR_VALUE="user_not_found";'."\n";
            $content .= 'window.KEY_ERROR_NAME="'.$token.'";';
        }
        return $content;
    }

    private function getUser($token)
    {
        try{
            $user = \App\Models\User::where('uuid',$token)->first();
            return $user;
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return null;
        }
    }

}