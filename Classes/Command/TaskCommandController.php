<?php
namespace Ecodev\PostfinanceService\Command;

/*
 * This file is part of the Ecodev/PostfinanceService project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Fab\Messenger\Domain\Model\Message;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class TaskCommandController
 */
class TaskCommandController extends CommandController
{

    /**
     * Tell whether all tasks have been executed correctly or are pending. If not send a notification email.
     *
     * @param string $notificationEmail
     * @param string $excludedTasks
     * @throws \InvalidArgumentException
     */
    public function superviseCommand($notificationEmail = '', $excludedTasks = '')
    {
        $clause = 'serialized_executions IS NOT NULL AND serialized_executions != ""';
        if ($excludedTasks) {
            $clause .= sprintf(' AND uid NOT IN (%s)', $excludedTasks);
        }
        $tasks = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'tx_scheduler_task', $clause);

        $faultyTaskIdentifiers = [];

        foreach ((array)$tasks as $task) {
            $faultyTaskIdentifiers[] = $task['uid'];
        }

        $recipients = GeneralUtility::trimExplode(',', $notificationEmail, true);
        if ($tasks && $recipients) {

            $subject = 'Problème e-factures: certaines tâches sont restées suspendues';
            $body = sprintf(
                "Veuillez contrôller les tâches avec les identifiants %s",
                implode(', ', $faultyTaskIdentifiers)
            );

            foreach ($recipients as $recipient) {
                $this->sendNotification($subject, $body, $recipient);
            }
        }
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
     * Returns a pointer to the database.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

}