<?php

namespace App\Helper\BusinessCentral\Connector;

use App\Helper\BusinessCentral\Connector\NTLMStream;
use SoapClient;

class NTLMSoapClient extends SoapClient
{
    public function __construct($wsdl, array $options = [])
    {
        // Set missing indexes to their default value.
        $options += array(
            'user' => null,
            'password' => null,
        );
        $this->options = $options;

        // Verify that a user name and password were entered.
        if (empty($options['user']) || empty($options['password'])) {
            throw new \BadMethodCallException(
                'A username and password is required.'
            );
        }
        $this->connexion = $options['user'] . ':' . $options['password'];
        NTLMStream::$user = $options['user'];
        NTLMStream::$password = $options['password'];
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', 'App\Helper\BusinessCentral\Connector\NTLMStream');
        parent::__construct($wsdl, $options);
        stream_wrapper_restore('http');
    }


    public function __doRequest($request, $location, $action, $version, $one_way = null)
    {
        $headers = array(
            'Method: POST',
            'Connection: Keep-Alive',
            'User-Agent: PHP-SOAP-CURL',
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "' . $action . '"',
        );
        $this->__last_request_headers = $headers;
        $ch = curl_init($location);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($ch, CURLOPT_USERPWD, $this->connexion);
        dump($request);
        $response = curl_exec($ch);
        return $response;
    }

    public function __getLastRequestHeaders()
    {
        return implode("\n", $this->__last_request_headers) . "\n";
    }
}
