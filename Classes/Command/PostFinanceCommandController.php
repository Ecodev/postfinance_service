<?php
namespace Ecodev\PostfinanceService\Command;

/*
 * This file is part of the Ecodev/PostfinanceService project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Ecodev\PostfinanceService\Client\PostFinanceClient;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Command Controller which imports the Postal Box as voting location.
 */
class PostFinanceCommandController extends CommandController
{

    /**
     * Ping the service and see it works correctly.
     *
     * @param string $username
     * @param string $password
     * @param string $accountId
     * @throws \InvalidArgumentException
     */
    public function pingCommand($username, $password, $accountId)
    {
        $action = 'ExecutePing';

        $client = $this->getPostFinanceClient()
            ->setUsername($username)
            ->setPassword($password)
            ->getClientFor($action);

        try {
            $response = $client->$action(['eBillAccountID' => $accountId]);
            $result = $action . 'Result';
            $this->outputLine($response->$result);
        } catch (\RuntimeException $fault) {
            $this->dying($fault, $client);
        }
    }


    /**
     * Get the archive list.
     *
     * @param string $username
     * @param string $password
     * @param string $accountId
     * @throws \InvalidArgumentException
     */
    public function getArchiveListCommand($username, $password, $accountId)
    {
        $action = 'GetInvoiceListPayer';

        $client = $this->getPostFinanceClient()
            ->setUsername($username)
            ->setPassword($password)
            ->getClientFor($action);

        try {
            $response = $client->$action(['eBillAccountID' => $accountId, 'ArchiveData' => false]);
            $result = $action . 'Result';
            #$this->outputLine($response->$result);
            print_r($response->$result);
        } catch (\RuntimeException $fault) {
            $this->dying($fault, $client);
        }
    }

    /**
     * @param \RuntimeException $fault
     * @param $client
     */
    protected function dying($fault, $client)
    {
        echo 'dying here...';
        $message = sprintf(
            'Error code %s -> %s',
            $fault->getCode(),
            $fault->getMessage()
        );
        echo $message;
        #print_r($client->__getLastRequest());
        die();
    }

    /**
     * @return PostFinanceClient
     * @throws \InvalidArgumentException
     */
    protected function getPostFinanceClient()
    {
        return GeneralUtility::makeInstance(PostFinanceClient::class);
    }

}