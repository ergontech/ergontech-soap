<?php

namespace ErgonTech\Soap;

use ErgonTech\Soap\Stream\Ntlm;

class Client extends \SoapClient
{
    const NTLM_USERNAME_OPTION_KEY = 'ntlm_username';
    const NTLM_PASSWORD_OPTION_KEY = 'ntlm_password';

    private $options;

    protected $ntlm = false;

    public function __construct($wsdl, array $options = null)
    {
        $options = $options ?: [];
        if ($wsdl === null
            || empty($options[self::NTLM_USERNAME_OPTION_KEY] )
            || empty($options[self::NTLM_PASSWORD_OPTION_KEY])  ) {
            parent::__construct($wsdl, $options);
        } else {
            $this->options = $options;
            $this->ntlm = true;

            $protocol = strtolower(parse_url($wsdl, PHP_URL_SCHEME));
            if ($protocol !== 'http' && $protocol !== 'https') {
                throw new \InvalidArgumentException("Unknown protocol in wsdl URL: $protocol");
            }

            NTLM::$user	= $options[self::NTLM_USERNAME_OPTION_KEY];
            NTLM::$password = $options[self::NTLM_PASSWORD_OPTION_KEY];

            \stream_wrapper_unregister($protocol);

            if(!\stream_wrapper_register($protocol, Ntlm::class)){
                throw new \Exception("Unable to register $protocol Handler");
            }
            parent::__construct($wsdl, $options);

            \stream_wrapper_restore($protocol);
        }
    }

    /**
     * (non-PHPdoc)
     * @see SoapClient::__doRequest()
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $this->__last_request = $request;

        $ch = \curl_init($location);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        \curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Method: POST',
            'User-Agent: PHP-SOAP-CURL',
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "' . $action . '"',
        ]);

        \curl_setopt($ch, CURLOPT_POST, true);
        \curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        \curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        if($this->ntlm){
            \curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
            \curl_setopt($ch, CURLOPT_USERPWD, $this->options[self::NTLM_USERNAME_OPTION_KEY].':'. $this->options[self::NTLM_PASSWROD_OPTION_KEY]);
        }

        $response = \curl_exec($ch);

        return $response;
    }

}
