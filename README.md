# Postfinance web service consumer

TYPO3 extension to consume web service (SOAP) of Postfinance.

Usage
=====

    # Check the service is alive
    ./typo3cms postfinance:ping  --secret-file=.secret/development

    # List invoices
    ./typo3cms postfinance:list --secret-file=.secret/development

    # Download invoices
    ./typo3cms postfinance:download --notification-email=fabien@udriot.net
    ./typo3cms postfinance:download  --secret-file=.secret/development --notification-email=email@test.com --limit=10


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