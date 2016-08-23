# Postfinance web service consumer

TYPO3 extension to consume web service (SOAP) of Postfinance. After installation

./typo3cms postfinance:ping
./typo3cms postfinance:getArchiveList
./typo3cms postfinance:download


Installation
============

* Install extension and its dependencies EXT:vidi, EXT:messenger + minimum configuration in the Extension Manager
* Configure mail sender

    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'Foo';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'foo@example.com';

* Configure a secret in `.secret/development` ou `.secret/production` with followings values

    username=foo
    password=bar
    accountId=123456
    target=e-bills
    
* Make sure the scheduler is well configured
* Set up the "ping" task in the BE as test
* Set up the "download" task in the BE