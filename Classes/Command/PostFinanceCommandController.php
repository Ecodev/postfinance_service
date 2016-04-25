<?php
namespace Ecodev\PostfinanceService\Command;

/*
 * This file is part of the Ecodev.PostfinanceService package.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Command Controller which imports the Postal Box as voting location.
 */
class PostFinanceCommandController extends CommandController
{

    /**
     * Import a bunch of data form SmartVote using its API.
     *
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function downloadCommand()
    {
        var_dump(123);
        exit();
        $logLines = array();
        $this->outputLine($logLines);
    }

}
