<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_form']['fields']['enableMailchimp'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr', 'submitOnChange' => true],
    'sql' => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mailchimpList'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['mailchimpList'],
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_mailchimp.listName',
    'eval' => [
        'mandatory' => true,
        'includeBlankOption' => true,
        'tl_class' => 'w50',
        'submitOnChange' => true,
    ],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mailchimpGroups'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr',
        'includeBlankOption' => true,
        'multiple' => true,
    ],
    'sql' => ['type' => 'blob', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mailchimpMergeTags'] = [
    'inputType' => 'keyValueWizard',
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mailchimpConfirmField'] = [
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 128],
    'sql' => ['type' => 'string', 'length' => 128, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mailchimpOptIn'] = [
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mailchimpMemberTags'] = [
    'inputType' => 'keyValueWizard',
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];

PaletteManipulator::create()
    ->addLegend('mailchimp_legend', null)
    ->addField('enableMailchimp', 'mailchimp_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_form')
;

$GLOBALS['TL_DCA']['tl_form']['palettes']['__selector__'][] = 'enableMailchimp';
$GLOBALS['TL_DCA']['tl_form']['subpalettes']['enableMailchimp'] = 'mailchimpList,mailchimpGroups,mailchimpConfirmField,mailchimpOptIn,mailchimpMergeTags,mailchimpMemberTags';
