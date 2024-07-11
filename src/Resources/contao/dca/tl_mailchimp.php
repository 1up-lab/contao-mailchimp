<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_mailchimp'] = [
    'config' => [
        'dataContainer' => Contao\DC_Table::class,
        'enableVersioning' => true,
        'onsubmit_callback' => [
            ['oneup_contao_mailchimp.listener.dca', 'onSaveListFields'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => 2,
            'fields' => ['listName'],
            'flag' => 1,
            'panelLayout' => 'sort,search,limit',
        ],

        'label' => [
            'fields' => ['listName', 'listApiKey', 'listId'],
            'format' => '%s',
        ],

        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],

        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_mailchimp']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],

            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_mailchimp']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"',
            ],

            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_mailchimp']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
                'attributes' => 'style="margin-right: 3px"',
            ],
        ],
    ],

    'palettes' => [
        'default' => '{list_legend},listName,{mailchimp_legend},listApiKey,listId',
    ],

    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],

        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'listName' => [
            'label' => &$GLOBALS['TL_LANG']['tl_mailchimp']['listName'],
            'exclude' => true,
            'sorting' => true,
            'search' => true,
            'flag' => 1,
            'filter' => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'unique' => true,
                'maxlength' => 255,
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        'listApiKey' => [
            'label' => &$GLOBALS['TL_LANG']['tl_mailchimp']['listApiKey'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'maxlength' => 255,
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        'listId' => [
            'label' => &$GLOBALS['TL_LANG']['tl_mailchimp']['listId'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'maxlength' => 255,
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        'fields' => [
            'sql' => 'blob NULL',
        ],

        'groups' => [
            'sql' => 'blob NULL',
        ],
    ],
];
