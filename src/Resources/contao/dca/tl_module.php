<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_module']['palettes']['mailchimp_subscribe'] = '
    {title_legend},name,headline,type;
    {list_legend},mailchimpList;
    {jumpTo_legend},mailchimpJumpTo;
    {option_legend},mailchimpOptin,mailchimpShowPlaceholder,mailchimpCaptcha,mailchimpMandatoryInterests;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['mailchimp_unsubscribe'] = '
    {title_legend},name,headline,type;
    {list_legend},mailchimpList;
    {jumpTo_legend},mailchimpJumpTo;
    {option_legend},mailchimpCaptcha,mailchimpShowPlaceholder;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['fields']['mailchimpList'] = [
    'default' => '',
    'exclude' => true,
    'search' => true,
    'sorting' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_mailchimp.listName',
    'eval' => [
        'mandatory' => true,
        'submitOnChange' => true,
        'includeBlankOption' => true,
    ],
    'sql' => "varchar(128) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['mailchimpJumpTo'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'eval' => [
        'fieldType' => 'radio',
        'mandatory' => true,
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['mailchimpOptin'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'mandatory' => false,
        'isBoolean' => true,
    ],
    'sql' => "varchar(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['mailchimpShowPlaceholder'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'mandatory' => false,
        'isBoolean' => true,
    ],
    'sql' => "varchar(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['mailchimpMandatoryInterests'] = [
    'inputType' => 'checkbox',
    'options_callback' => ['oneup_contao_mailchimp.listener.dca', 'onLoadInterests'],
    'eval' => [
        'mandatory' => false,
        'isBoolean' => true,
        'multiple' => true,
    ],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['mailchimpCaptcha'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'mandatory' => false,
        'isBoolean' => true,
    ],
    'sql' => "varchar(1) NOT NULL default ''",
];
