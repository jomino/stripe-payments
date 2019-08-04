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
        $assets = $this->get('settings')['assets'];
        $resource = $assets['path'] . '/' . $args['path'] . '/' . $args['file'];
        if (!is_file($resource)) {
            $notFoundHandler = $this->get('notFoundHandler');
            return $notFoundHandler($request, $response);
        }elseif(in_array($args['file'],$this->filter)){
            return $response->write($this->applyTemplate($request,$resource))
                ->withHeader('Content-Type', $this->paths[$args['path']]);
        }else{
            return $response->write(file_get_contents($resource))
                ->withHeader('Content-Type', $this->paths[$args['path']]);
        }
    }

    private function applyTemplate($request,$resource){
        return '';
    }

}