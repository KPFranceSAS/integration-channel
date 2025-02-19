<?php

namespace App\Channels\Arise;

use App\Channels\Arise\AriseRequest;
use Exception;
use Psr\Log\LoggerInterface;

class AriseClient
{
    public $loggerInterface;

    public $appkey;

    public $accessToken;

    public $refreshToken;

    public $secretKey;

    public $gatewayUrl;

    public $connectTimeout=20;

    public $readTimeout=20;

    protected $signMethod = "sha256";

    protected $sdkVersion = "iop-sdk-php-20200227";


    public $logger;

    public function getAppkey()
    {
        return $this->appkey;
    }


    public function addParams(LoggerInterface $loggerInterface, $clientId, $clientSecret, $clientAccessToken)
    {
        $this->gatewayUrl = 'https://api.miravia.es/rest';
        $this->appkey = $clientId;
        $this->secretKey = $clientSecret;
        $this->accessToken = $clientAccessToken;
        $this->logger = $loggerInterface;
    }



    public function getRefreshedToken()
    {
        if (!$this->accessToken) {
            $request = new AriseRequest('/auth/token/refresh');
            $request->addApiParam('refresh_token', $this->refreshToken);
            $reponse = $this->execute($request, false);
            if (property_exists($reponse, 'access_token')) {
                $this->accessToken = $reponse->access_token;
            } else {
                $this->logger->critical("Error on getting access token ".json_encode($reponse));
                throw new Exception("Error on getting access token ".json_encode($reponse));
            }
        }
        return $this->accessToken;
    }

    

    protected function generateSign($apiName, $params)
    {
        ksort($params);

        $stringToBeSigned = '';
        $stringToBeSigned .= $apiName;
        foreach ($params as $k => $v) {
            $stringToBeSigned .= "$k$v";
        }
        unset($k, $v);

        return strtoupper((string) $this->hmac_sha256($stringToBeSigned, $this->secretKey));
    }


    public function hmac_sha256($data, $key)
    {
        return hash_hmac('sha256', (string) $data, (string) $key);
    }

    public function curl_get($url, $apiFields = null, $headerFields = null)
    {
        $ch = curl_init();

        foreach ($apiFields as $key => $value) {
            $url .= "&" ."$key=" . urlencode((string) $value);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        if ($headerFields) {
            $headers = [];
            foreach ($headerFields as $key => $value) {
                $headers[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            unset($headers);
        }

        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }

        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }
        
        curl_setopt($ch, CURLOPT_USERAGENT, $this->sdkVersion);

        //https ignore ssl check ?
        if (strlen((string) $url) > 5 && strtolower(substr((string) $url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $output = curl_exec($ch);
        
        $errno = curl_errno($ch);

        if ($errno) {
            curl_close($ch);
            $this->logger->critical("Curl error code ".$errno);
            if ($errno == 28) {
                throw new Exception("Arise has some timeout to respond on get CURLE_OPERATION_TIMEDOUT", 0);
            }
            $this->logger->critical("Curl error line 138 code ".$errno);
            throw new Exception($errno, 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (200 !== $httpStatusCode) {
                $this->logger->critical("Http error line 143 code ".$output);
                throw new Exception($output, $httpStatusCode);
            }
        }

        return $output;
    }

    public function curl_post($url, $postFields = null, $fileFields = null, $headerFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }

        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }

        if ($headerFields) {
            $headers = [];
            foreach ($headerFields as $key => $value) {
                $headers[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            unset($headers);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, $this->sdkVersion);

        //https ignore ssl check ?
        if (strlen((string) $url) > 5 && strtolower(substr((string) $url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $delimiter = '-------------' . uniqid();
        $data = '';
        if ($postFields != null) {
            foreach ($postFields as $name => $content) {
                $data .= "--" . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"';
                $data .= "\r\n\r\n" . $content . "\r\n";
            }
            unset($name,$content);
        }

        if ($fileFields != null) {
            foreach ($fileFields as $name => $file) {
                $data .= "--" . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $file['name'] . "\" \r\n";
                $data .= 'Content-Type: ' . $file['type'] . "\r\n\r\n";
                $data .= $file['content'] . "\r\n";
            }
            unset($name,$file);
        }
        $data .= "--" . $delimiter . "--";

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            ['Content-Type: multipart/form-data; boundary=' . $delimiter, 'Content-Length: ' . strlen($data)]
        );

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        unset($data);
        
        $errno = curl_errno($ch);
        if ($errno) {
            curl_close($ch);
            $this->logger->critical("Curl error line 228 code ".$errno);
            throw new Exception("Miravia has some timeout to respond on post CURLE_OPERATION_TIMEDOUT >> code ".$errno, 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (200 !== $httpStatusCode) {
                $this->logger->critical("Http error line 234 code ".$errno);
                throw new Exception($response, $httpStatusCode);
            }
        }

        return $response;
    }

    public function execute(AriseRequest $request, $withToken = true)
    {
        $sysParams["app_key"] = $this->appkey;
        $sysParams["sign_method"] = $this->signMethod;
        $sysParams["timestamp"] = $this->msectime();
        if ($withToken) {
            $sysParams["access_token"] = $this->getRefreshedToken();
        }

        $apiParams = $request->udfParams;
        
        $requestUrl = $this->gatewayUrl;

        if ($this->endWith($requestUrl, "/")) {
            $requestUrl = substr((string) $requestUrl, 0, -1);
        }

        $requestUrl .= $request->apiName;
        $requestUrl .= '?';

        $sysParams["partner_id"] = $this->sdkVersion;
        $sysParams["sign"] = $this->generateSign($request->apiName, array_merge($apiParams, $sysParams));

        foreach ($sysParams as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode((string) $sysParamValue) . "&";
        }

        $requestUrl = substr($requestUrl, 0, -1);
        
        $resp = '';

        try {
            if ($request->httpMethod == 'POST') {
                $resp = $this->curl_post($requestUrl, $apiParams, $request->fileParams, $request->headerParams);
            } else {
                $resp = $this->curl_get($requestUrl, $apiParams, $request->headerParams);
            }
        } catch (Exception $e) {
            $this->logApiError($requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
            throw $e;
        }

        unset($apiParams);

        $respObject = json_decode((string) $resp);
        if (isset($respObject->code) && $respObject->code != "0") {
            $this->logApiError($requestUrl, $respObject->code, $respObject->message, 'error');
        } else {
            $this->logApiError($requestUrl, '', '');
        }
        return $respObject;
    }

    protected function logApiError($requestUrl, $errorCode, $responseTxt, $type='info')
    {
        $logData = [
            date("Y-m-d H:i:s"),
            'KEY '.$this->appkey,
            'URL '.$requestUrl,
            $errorCode,
            str_replace("\n", "", (string) $responseTxt)
        ];
        $this->logger->{$type}(implode(' -- ', $logData));
    }

    public function msectime()
    {
        [$msec, $sec] = explode(' ', microtime());
        return $sec . '000';
    }

    public function endWith($haystack, $needle)
    {
        $length = strlen((string) $needle);
        if ($length == 0) {
            return false;
        }
        return (substr((string) $haystack, -$length) === $needle);
    }
}
