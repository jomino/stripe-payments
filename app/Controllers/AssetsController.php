<?php

namespace App\Controllers;

class AssetsController extends \Core\Controller
{
    private $paths = [
      'js' => 'text/javascript',
      'css' => 'text/css',
      'images' => FILEINFO_MIME_TYPE
    ];

    private $filter = ['application.js'];

    public function __invoke($request, $response, $args)
    {
        $assets = $this->settings['assets'];
        $resource = $assets['path'] . '/' . $args['path'] . '/' . $args['file'];
        $content_type = $this->paths[$args['path']];
        if (!is_file($resource)) {
            $notFoundHandler = $this->notFoundHandler;
            return $notFoundHandler($request, $response);
        }elseif(in_array($args['file'],$this->filter)){
            return $response->write($this->applyTemplate($request,$resource))
                ->withHeader('Content-Type', $content_type);
        }else{
            return $response->write(file_get_contents($resource))
                ->withHeader('Content-Type', $content_type);
        }
    }

    private function applyTemplate($request,$resource){
        return '';
    }

}