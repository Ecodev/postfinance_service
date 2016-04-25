<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_MODE === 'BE') {

    // Register Tasks
//    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Visol\EasyvoteSmartvote\Task\PurgeTask'] = [
//        'extension' => 'easyvote_smartvote',
//        'title' => 'LLL:EXT:easyvote_smartvote/Resources/Private/Language/locallang_task.xlf:purge.name',
//        'description' => 'LLL:EXT:easyvote_smartvote/Resources/Private/Language/locallang_task.xlf:purge.description',
//        'additionalFields' => \Visol\EasyvoteSmartvote\Task\AdditionalFieldProvider::class,
//    ];

    // Configure commands that can be run from the cli_dispatch.phpsh script.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Ecodev\PostfinanceService\Command\PostFinanceCommandController::class;
}
