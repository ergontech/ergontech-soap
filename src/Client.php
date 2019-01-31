<?php

namespace ErgonTech\Soap;

use ErgonTech\Soap\Stream\Ntlm;

class Client extends \SoapClient
{
    const NTLM_USERNAME_OPTION_KEY = 'ntlm_username';
    const NTLM_PASSWORD_OPTION_KEY = 'ntlm_password';
    const REMOVE_NS_FROM_XSI_TYPES = 'remove_ns_from_xsi_types';

    private $options;

    /**
     * @var bool
     */
    protected $ntlm = false;

    /**
     * @var bool
     */
    protected $removeNsFromXsiTypes = false;

    public function __construct($wsdl, array $options = null)
    {
        $options = $options ?: [];
        if (array_key_exists(self::REMOVE_NS_FROM_XSI_TYPES, $options)) {
            $this->setRemoveNsFromXsiTypes($options[self::REMOVE_NS_FROM_XSI_TYPES]);
            unset($options[self::REMOVE_NS_FROM_XSI_TYPES]);
        }

        if ($wsdl === null
            || empty($options[self::NTLM_USERNAME_OPTION_KEY] )
            || empty($options[self::NTLM_PASSWORD_OPTION_KEY])  ) {
            parent::__construct($wsdl, $options);
        } else {
            $this->options = $options;
            $this->ntlm = true;

            $protocol = \strtolower(parse_url($wsdl, PHP_URL_SCHEME));
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
     * @param bool $trueOrFalse
     */
    public function setRemoveNsFromXsiTypes($trueOrFalse)
    {
        $this->removeNsFromXsiTypes = (bool) $trueOrFalse;
    }

    /**
     * @return bool
     */
    public function getRemoveNsFromXsiTypes()
    {
        return $this->removeNsFromXsiTypes;
    }

    /**
     * (non-PHPdoc)
     * @see SoapClient::__doRequest()
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        if ($this->getRemoveNsFromXsiTypes()) {
            $request = $this->removeNameSpacedXsiObjectAttributeValues($request);
        }

        $this->__last_request = $request;

        $ch = \curl_init($location);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);

        \curl_setopt($ch, \CURLOPT_HTTPHEADER, [
            'Method: POST',
            'User-Agent: PHP-SOAP-CURL',
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "' . $action . '"',
        ]);

        \curl_setopt($ch, \CURLOPT_POST, true);
        \curl_setopt($ch, \CURLOPT_POSTFIELDS, $request);
        \curl_setopt($ch, \CURLOPT_HTTP_VERSION, \CURL_HTTP_VERSION_1_1);

        if($this->ntlm){
            \curl_setopt($ch, \CURLOPT_HTTPAUTH, \CURLAUTH_NTLM);
            \curl_setopt($ch, \CURLOPT_USERPWD, $this->options[self::NTLM_USERNAME_OPTION_KEY].':'. $this->options[self::NTLM_PASSWORD_OPTION_KEY]);
        }

        $response = \curl_exec($ch);

        return $response;
    }

    /**
     * Finds any Elements with attribute xsi:object set, checks for namespaced attribute values, and moves that namespace to the parent node
     * E.g. this:     <some_element xsi:type="some_ns:some_type" />
     * would become:  <some_element xsi:type="some_type" xmlns="http://some_ns_uri"/>
     * @param string $request
     * @return string
     */
    public function removeNameSpacedXsiObjectAttributeValues(string $request)
    {
        $doc = new \DOMDocument();
        $doc->loadXML($request);
        $xpath = new \DOMXPath($doc);
        $namespaces = $xpath->query('namespace::*', $doc->documentElement);
        foreach ($namespaces as $node) {
            /** @var \DOMNameSpaceNode  $node */
            $prefix = $node->prefix;
            $uri = $node->namespaceURI;
            $elementsToFix = $xpath->query("//*[starts-with(@xsi:type, '$prefix')]");
            foreach ($elementsToFix as $element) {
                /** @var \DOMElement $element */
                //set the namespace on the node
                $element->setAttribute('xmlns', $uri);
                //remove the namespace prefix from the xsi:type value
                $newXsiType =  str_replace($prefix . ':', '',  $element->getAttribute('xsi:type'));
                $element->setAttribute('xsi:type', $newXsiType);
            }
        }
        return $doc->saveXML();

    }

}
