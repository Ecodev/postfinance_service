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
    public function pingCommand($secretFile)
    {
        $action = 'ExecutePing';

        $secret = parse_ini_file($secretFile);
        $client = $this->getPostFinanceClient()
            ->setUsername($secret['username'])
            ->setPassword($secret['password'])
            ->setWsdl($secret['wsdl'])
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
    public function listCommand($secretFile)
    {
        $action = 'GetInvoiceListPayer';

        $secret = parse_ini_file($secretFile);
        $client = $this->getPostFinanceClient()
            ->setUsername($secret['username'])
            ->setPassword($secret['password'])
            ->setWsdl($secret['wsdl'])
            ->getClientFor($action);

        try {
            $response = $client->$action(['eBillAccountID' => $secret['accountId'], 'ArchiveData' => false]);
            $resultProperty = $action . 'Result';
            foreach ($response->$resultProperty as $item) {
                print_r($item);
            }
        } catch (\RuntimeException $fault) {
            $this->dying($fault, $client);
        }
    }

    /**
     * Download the list of invoices.
     *
     * @param string $secretFile
     * @param string $notificationEmail
     * @param int $limit
     * @throws \InvalidArgumentException
     * @throws \Fab\Messenger\Exception\InvalidEmailFormatException
     * @throws \Fab\Messenger\Exception\WrongPluginConfigurationException
     */
    public function downloadCommand($secretFile, $notificationEmail = '', $limit = 0)
    {
        $action = 'GetInvoiceListPayer';

        $secret = parse_ini_file($secretFile);
        $client = $this->getPostFinanceClient()
            ->setUsername($secret['username'])
            ->setPassword($secret['password'])
            ->setWsdl($secret['wsdl'])
            ->getClientFor($action);

        $downloadAction = 'GetInvoicePayer';
        $downloadService = $this->getPostFinanceClient()
            ->setUsername($secret['username'])
            ->setPassword($secret['password'])
            ->setWsdl($secret['wsdl'])
            ->getClientFor($downloadAction);

        try {

            // Prepare variables
            $recipients = GeneralUtility::trimExplode(',', $notificationEmail, true);
            $basePath = rtrim($secret['target'], '/');

            $this->prepareEnvironmentAndAlertIfProblem($basePath, $recipients);

            $response = $client->$action(['eBillAccountID' => $secret['accountId'], 'ArchiveData' => false]);

            // compute the result property
            $resultProperty = $action . 'Result';
            $downloadResultProperty = $downloadAction . 'Result';

            if ($response->$resultProperty->InvoiceReport) {

                $numberOfDownloadedFiles = 0;
                foreach ($response->$resultProperty->InvoiceReport as $item) {

                    $numberOfDownloadedFiles++;

                    // Download file via the web service.
                    $downloadResponse = $downloadService->$downloadAction([
                        'eBillAccountID' => $secret['accountId'],
                        'BillerID' => $item->BillerID,
                        'TransactionID' => $item->TransactionID,
                        'FileType' => $item->FileType,
                    ]);

                    // Get the result
                    $downloadResult = $downloadResponse->$downloadResultProperty;

                    $fileNameAndPath = sprintf(
                        '%s/%s',
                        $basePath,
                        $downloadResult->Filename
                    );

                    file_put_contents($fileNameAndPath, $downloadResult->Data);

                    if ($limit && $numberOfDownloadedFiles >= $limit) {
                        break;
                    }
                }

                if ($recipients && $numberOfDownloadedFiles > 0) {

                    $path = $basePath . '/*';
                    $files = glob($path);
                    $numberOfFiles = count($files);

                    $subject = sprintf(
                        'Nouveau lot de e-factures - %s fichier%s',
                        $numberOfDownloadedFiles,
                        $numberOfDownloadedFiles > 1 ? 's' : ''
                    );
                    $body = sprintf(
                        "Nouvellement téléchargé %s. Nombre de fichiers %s, en attente de traitement dans le dossier %s/ \n\n%s%s",
                        $numberOfDownloadedFiles,
                        $numberOfFiles,
                        $basePath,
                        $files ? "\n    * " : '',
                        implode("\n    * ", $files)
                    );
                    foreach ($recipients as $recipient) {
                        $this->sendNotification($subject, $body, $recipient);
                    }
                }
            }


        } catch (\RuntimeException $fault) {
            $this->dying($fault, $client);
        }
    }

    /**
     * @param string $basePath
     * @param array $recipients
     * @throws \Fab\Messenger\Exception\InvalidEmailFormatException
     * @throws \Fab\Messenger\Exception\WrongPluginConfigurationException
     */
    protected function prepareEnvironmentAndAlertIfProblem($basePath, array $recipients)
    {
        // Make sure the file exist
        if (!is_dir($basePath)) {
            GeneralUtility::mkdir($basePath);
        }

        $testFile = $basePath . '/test.txt';
        try {
            touch($testFile);
        } catch (\Exception $e) {
            // do not handle exception here.
        }

        // Test if we can write a file on the target, if not die right away.
        if (!is_file($testFile)) {

            $subject = "Erreur e-factures: le dossier cible n'est pas atteignable";
            $body = sprintf(
                "Le dossier cible %s n'est pas disponible en écriture, vérifiez qu'il est correctement monté.\n\nAucune e-facture n'a été téléchargée. ",
                $testFile
            );

            foreach ($recipients as $recipient) {
                $this->sendNotification($subject, $body, $recipient);
            }
            $this->outputLine($body);
            $this->sendAndExit(1);
        }

        // remove file.
        unlink($testFile);
    }

    /**
     * @param string $subject
     * @param string $body
     * @param string $recipient
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Fab\Messenger\Exception\InvalidEmailFormatException
     * @throws \Fab\Messenger\Exception\WrongPluginConfigurationException
     */
    protected function sendNotification($subject, $body, $recipient)
    {

        /** @var Message $message */
        $message = $this->objectManager->get(Message::class);

        $message->setBody($body)
            ->setSubject($subject)
            ->setSender($this->getSender())
            ->parseToMarkdown(true)
            ->setTo([$recipient => $recipient]);

        // Send message
        $message->send();
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