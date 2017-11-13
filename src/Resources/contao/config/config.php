<?php

$GLOBALS['BE_MOD']['content']['mailchimp'] = [
    'tables' => ['tl_mailchimp'],
];

$GLOBALS['FE_MOD']['mailchimp'] = [
    'mailchimp_subscribe' => 'Oneup\Contao\MailChimpBundle\Module\ModuleSubscribe',
    'mailchimp_unsubscribe' => 'Oneup\Contao\MailChimpBundle\Module\ModuleUnsubscribe',
];

$GLOBALS['TL_MODELS']['tl_mailchimp'] = 'Oneup\Contao\MailChimpBundle\Model\MailChimpModel';
