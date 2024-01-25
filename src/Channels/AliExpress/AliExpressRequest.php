<?php

namespace App\Channels\AliExpress;

use Exception;

class AliExpressRequest
{
    public $headerParams = [];

    public $udfParams = [];

    public $fileParams = [];

    public $simplify = 'false';

    public $format = 'json';



    public function __construct(public $apiName, public $httpMethod = 'POST')
    {
        if($this->startWith($this->apiName, "//")) {
            throw new Exception("api name is invalid. It should be start with /");
        }
    }


    public function addApiParams($params)
    {
        foreach($params as $key=>$param){
            $this->addApiParam($key, $param);
        }
    }




    public function addApiParam($key, $value)
    {

        if(!is_string($key)) {
            throw new Exception("api param key should be string");
        }

        if(is_object($value)|| is_array($value)) {
            $this->udfParams[$key] = json_encode($value);
        } else {
            $this->udfParams[$key] = $value;
        }
    }

    public function addFileParam($key, $content, $mimeType = 'application/octet-stream')
    {
        if(!is_string($key)) {
            throw new Exception("api file param key should be string");
        }

        $file = ['type' => $mimeType, 'content' => $content, 'name' => $key];
        $this->fileParams[$key] = $file;
    }

    public function addHttpHeaderParam($key, $value)
    {
        if(!is_string($key)) {
            throw new Exception("http header param key should be string");
        }

        if(!is_string($value)) {
            throw new Exception("http header param value should be string");
        }

        $this->headerParams[$key] = $value;
    }

    public function startWith($str, $needle)
    {
        return str_starts_with((string) $str, (string) $needle);
    }
}
