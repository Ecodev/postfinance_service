<?php
namespace Ecodev\PostfinanceService\Command;

/*
 * This file is part of the Ecodev/PostfinanceService project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Ecodev\PostfinanceService\Client\PostFinanceClient;
use Fab\Messenger\Domain\Model\Message;
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
     * @param string $secretFile
     * @throws \InvalidArgumentException
     */
    public function pingCommand($secretFile = '.secret/development')
    {
        $action = 'ExecutePing';

        $secret = parse_ini_file($secretFile);
        $client = $this->getPostFinanceClient()
            ->setUsername($secret['username'])
            ->setPassword($secret['password'])
            ->getClientFor($action);

        try {
            $response = $client->$action(['eBillAccountID' => $secret['accountId']]);
            $result = $action . 'Result';
            $this->outputLine($response->$result);
        } catch (\RuntimeException $fault) {
            $this->dying($fault, $client);
        }
    }


    /**
     * Get the list of invoices
     *
     * @param string $secretFile
     * @throws \InvalidArgumentException
     */
    public function listInvoicesCommand($secretFile = '.secret/development')
    {
        $action = 'GetInvoiceListPayer';

        $secret = parse_ini_file($secretFile);
        $client = $this->getPostFinanceClient()
            ->setUsername($secret['username'])
            ->setPassword($secret['password'])
            ->getClientFor($action);

        try {
            $response = $client->$action(['eBillAccountID' => $secret['accountId'], 'ArchiveData' => false]);
            $result = $action . 'Result';
            foreach ($response->$result as $item) {
                print_r($item);
            }
        } catch (\RuntimeException $fault) {
            $this->dying($fault, $client);
        }
    }

    /**
     * Download the list of invoices
     *
     * @param string $secretFile
     * @param string $notificationEmail
     * @throws \Fab\Messenger\Exception\InvalidEmailFormatException
     * @throws \InvalidArgumentException
     */
    public function downloadCommand($secretFile = '.secret/development', $notificationEmail = '')
    {


        $action = 'GetInvoiceListPayer';

        $secret = parse_ini_file($secretFile);
        $client = $this->getPostFinanceClient()
            ->setUsername($secret['username'])
            ->setPassword($secret['password'])
            ->getClientFor($action);

        try {

            $basePath = rtrim($secret['target'], '/');
            // Make sure the file exist
            if (!is_dir($basePath)) {
                GeneralUtility::mkdir($secret['target']);
            }

            $response = $client->$action(['eBillAccountID' => $secret['accountId'], 'ArchiveData' => false]);
            $result = $action . 'Result';
            foreach ($response->$result->InvoiceReport as $item) {

                $fileExtension = strtolower($item->FileType);

                if ($fileExtension === 'pdf') {

                    $fileNameAndPath = sprintf(
                        '%s/%s.%s',
                        $basePath,
                        $item->TransactionID,
                        $fileExtension
                    );

                    file_put_contents($fileNameAndPath, 'asdf');
                }
            }


            if ($notificationEmail) {

                /** @var Message $message */
                $message = $this->objectManager->get(Message::class);

                $path = $basePath . '/*.pdf';
                $files = glob($path);
                $numberOfFiles = count($files);

                $subject = sprintf('Nouveau lot de factures téléchargés (%s)', $numberOfFiles);
                $body = sprintf(
                    "Nombre de factures téléchargés %s dans le dossier %s/ \n\nCeci est un message automatique.",
                    $numberOfFiles,
                    $basePath
                );

                $message->setBody($body)
                    ->setSubject($subject)
                    ->setSender($this->getSender())
                    ->parseToMarkdown(true)
                    ->setTo([$notificationEmail => $notificationEmail]);

                // Send message
                $message->send();
            }


        } catch (\RuntimeException $fault) {
            $this->dying($fault, $client);
        }
    }


    /**
     * @return array
     */
    protected function getSender()
    {
        $sender = [];
        if ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']) {
            $email = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
            $name = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
                ?: $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];


            $sender = [$email => $name];
        }
        return $sender;
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