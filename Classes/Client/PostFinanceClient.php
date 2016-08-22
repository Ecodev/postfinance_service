<?php
namespace Ecodev\PostfinanceService\Client;

/*
 * This file is part of the Ecodev/PostfinanceService project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use SoapClient;
use SoapHeader;
use SoapVar;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class PostFinanceClient
 */
class PostFinanceClient
{

    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var string
     */
    protected $password = '';

    /**
     * @var string
     */
    protected $wsdl = 'https://ebill-ki.postfinance.ch/B2BService/B2BService.svc?singleWsdl';

    /**
     * @param string $username
     * @param string $password
     * @param string $action
     * @return SoapClient
     */
    public function getClientFor($action) {
        $client = new SoapClient($this->wsdl, $this->getSoapHeaders());
        $client->__setSoapHeaders($this->soapClientWSSecurityHeader($this->username, $this->password, $action));
        return $client;
    }

    /**
     * @return array
     */
    protected function getSoapHeaders() {
        return [
            'soap_version' => SOAP_1_2,
            'trace' => 1,
            'exceptions' => 1,
            'connection_timeout' => 180
        ];
    }

    /**
     * This function implements a WS-Security digest authentification for PHP.
     *
     * @access private
     * @param string $username
     * @param string $password
     * @return SoapHeader
     */
    protected function soapClientWSSecurityHeader($username, $password, $action)
    {

        // Initializing namespaces
        $namespace = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';


        $xml = '<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
      <wsse:UsernameToken>
         <wsse:Username>%s</wsse:Username>
         <wsse:Password>%s</wsse:Password>
      </wsse:UsernameToken>
   </wsse:Security>
   <wsa:Action xmlns:wsa="http://www.w3.org/2005/08/addressing">http://ch.swisspost.ebill.b2bservice/B2BService/%s</wsa:Action>';


        $authentication = sprintf($xml, $username, $password, $action);
        $soapVariable = new SoapVar($authentication, XSD_ANYXML);
        return new SoapHeader($namespace, 'Security', $soapVariable , true);
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

}