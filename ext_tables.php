<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {

    // Default User TSConfig to be added in any case.
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('

		# Hide the module in the BE.
		options.hideModules.web := addToList(layout,ViewpageView,list,info,func,ts)
		options.hideModules.file := addToList(FilelistList)
		#options.hideModules.tools := addToList(ExtensionmanagerExtensionmanager,LangLanguage)
		options.hideModules.system := addToList(BeuserTxPermission,InstallInstall,dbint,config,ReportsTxreportsm1)
		options.hideModules.content := addToList(VidiFeUsersM1,VidiFeGroupsM1)
	');

}
