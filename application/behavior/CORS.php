<?php
namespace app\behavior;
use think\Response;
class CORS {

    public function appInit(&$params)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE');
        header('Access-Control-Max-Age: 1728000');
        if(request()->isOptions()){
            return Response::create()->send();
        }
    }
}
?>