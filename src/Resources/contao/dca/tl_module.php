<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_module']['palettes']['mailchimp_subscribe'] = '
    {title_legend},name,headline,type;
    {list_legend},mailchimpList;
    {jumpTo_legend},mailchimpJumpTo;
    {option_legend},mailchimpOptin,mailchimpShowPlaceholder,mailchimpMandatoryInterests;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['mailchimp_unsubscribe'] = '
    {title_legend},name,headline,type;
    {list_legend},mailchimpList;
    {jumpTo_legend},mailchimpJumpTo;
    {option_legend},mailchimpShowPlaceholder;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['fields']['mailchimpList'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['mailchimpList'],
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
    'label' => &$GLOBALS['TL_LANG']['tl_module']['mailchimpJumpTo'],
    'exclude' => true,
    'inputType' => 'pageTree',
    'eval' => [
        'fieldType' => 'radio',
        'mandatory' => true,
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['mailchimpOptin'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['mailchimpOptin'],
    'inputType' => 'checkbox',
    'eval' => [
        'mandatory' => false,
        'isBoolean' => true,
    ],
    'sql' => "varchar(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['mailchimpShowPlaceholder'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['mailchimpShowPlaceholder'],
    'inputType' => 'checkbox',
    'eval' => [
        'mandatory' => false,
        'isBoolean' => true,
    ],
    'sql' => "varchar(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['mailchimpMandatoryInterests'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['mailchimpMandatoryInterests'],
    'inputType' => 'checkbox',
    'options_callback' => ['oneup_contao_mailchimp.listener.dca', 'onLoadInterests'],
    'eval' => [
        'mandatory' => false,
        'isBoolean' => true,
        'multiple' => true,
    ],
    'sql' => 'blob NULL',
];
