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
            $referrer = $request->getAttribute('referrer');
            return $response->write($this->getStripePkey($referrer))->withHeader('Content-Type', $content_type);
        }elseif(is_file($resource) && is_readable($resource)){
            return $response->write(file_get_contents($resource))
                ->withHeader('Content-Type', $content_type);
        }else{
            $notFoundHandler = $this->notFoundHandler;
            return $notFoundHandler($request, $response);
        }
    }

    private function getStripePkey($name)
    {
        if($user=$this->getUser($name)){
            $content = 'window.STRIPE_PUBLISHABLE_KEY="'.$user->pkey.'";'."\n";
            $content .= 'window.PUBLISHABLE_KEY_ERROR=false;';
        }else{
            $content = 'window.PUBLISHABLE_KEY_ERROR=true;'."\n";
            $content .= 'window.KEY_ERROR_VALUE="user_not_found";';
        }
        return $content;
    }

    private function getUser($name)
    {
        try{
            $user = \App\Models\User::where('name',$name)->first();
            return $user;
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return null;
        }
    }

}