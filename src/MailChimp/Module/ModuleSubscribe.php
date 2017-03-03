<?php

namespace Oneup\Contao\MailChimp\Module;

use Oneup\Contao\MailChimp\Model\MailChimpModel;
use Contao\Module;
use Contao\System;
use Contao\Environment;
use Contao\Input;
use Contao\BackendTemplate;
use Haste\Form\Form;
use Oneup\MailChimp\Client;

class ModuleSubscribe extends Module
{
    protected $strTemplate = 'mod_mailchimp_subscribe';

    /** @var Client */
    protected $mailChimp;
    protected $objMailChimp;
    protected $mailChimpListId;

    public function generate()
    {
        if (TL_MODE == 'BE') {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['mailchimp_subscribe'][0]).' ###';

            return $objTemplate->parse();
        }

        $this->objMailChimp = MailChimpModel::findByPk($this->mailchimpList);
        $this->mailChimp = new Client($this->objMailChimp->listApiKey);
        $this->mailChimpListId = $this->objMailChimp->listId;

        return parent::generate();
    }

    protected function compile()
    {
        System::loadLanguageFile('tl_module');

        $objForm = new Form('mailchimp-subscribe', 'POST', function (Form $objHaste) {
            return Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });

        $objForm->setFormActionFromUri(Environment::get('request'));

        $eval = [
            'mandatory' => true,
            'rgxp' => 'email',
        ];

        if ((int) $this->mailchimpShowPlaceholder) {
            $eval['placeholder'] = $GLOBALS['TL_LANG']['tl_module']['mailchimp']['placeholderEmail'];
        }

        $objForm->addFormField('email', [
            'label' => $GLOBALS['TL_LANG']['tl_module']['mailchimp']['labelEmail'],
            'inputType' => 'text',
            'eval' => $eval,
        ]);

        $fields = json_decode($this->objMailChimp->fields);
        $mergeVarTags = [];

        if (is_array($fields)) {
            foreach ($fields as $field) {
                $addedName = $this->addFieldToForm($field, $objForm);

                if (null !== $addedName) {
                    $mergeVarTags[] = $addedName;
                }
            }
        }

        $objForm->addFormField('submit', [
            'label' => $GLOBALS['TL_LANG']['tl_module']['mailchimp']['labelSubmit'],
            'inputType' => 'submit'
        ]);

        $objForm->addContaoHiddenFields();

        $this->Template->error = false;

        if ($objForm->validate()) {
            $arrData = $objForm->fetchAll();

            $mergeVars = [];

            foreach ($mergeVarTags as $tag) {
                $mergeVars[$tag] = $arrData[$tag];
            }

            $subscribed = $this->mailChimp->subscribeToList(
                $this->mailChimpListId,
                $arrData['email'],
                $mergeVars,
                (boolean) $this->mailchimpOptin
            );

            if ($subscribed) {
                $this->jumpToOrReload($this->mailchimpJumpTo);
            } else {
                $this->Template->error = true;
                $this->Template->errorMsg = $GLOBALS['TL_LANG']['tl_module']['mailchimp']['subscribeError'];
            }
        }

        $form = new \stdClass();
        $objForm->addToObject($form);

        $this->Template->form = $form;
    }

    /**
     * Return the name of the field.
     *
     * @param $field
     * @param  Form  $form
     * @return mixed
     */
    protected function addFieldToForm($field, Form $form)
    {
        if (!in_array($field->type, ['text', 'number', 'website', 'address', 'dropdown', 'radio', 'url', 'date', 'birthday', 'phone'])) {
            return null;
        }

        switch ($field->type) {
            case 'text':
            case 'address':
            case 'date':
            case 'birthday':
            case 'phone':
                $inputType = 'text';

                $eval = [
                    'mandatory' => $field->required,
                ];

                if (($maxLength = (int) $field->options->size) > 0) {
                    $eval['maxlength'] = $maxLength;
                }

                if ((int) $this->mailchimpShowPlaceholder) {
                    $eval['placeholder'] = $field->name;
                }

                if (false === (bool) $field->public) {
                    $inputType = 'hidden';
                }

                $form->addFormField($field->tag, [
                    'label' => $field->name,
                    'inputType' => $inputType,
                    'eval' => $eval,
                    'default' => $field->default,
                ]);

                break;

            case 'dropdown':
                $inputType = 'select';

                if (false === (bool) $field->public) {
                    $inputType = 'hidden';
                }

                $form->addFormField($field->tag, [
                    'label' => $field->name,
                    'inputType' => $inputType,
                    'options' => $field->options->choices,
                    'eval' => [
                        'required' => $field->required,
                    ],
                    'default' => $field->default,
                ]);

                break;

            case 'radio':
                $inputType = 'radio';

                if (false === (bool) $field->public) {
                    $inputType = 'hidden';
                }

                $form->addFormField($field->tag, [
                    'label' => $field->name,
                    'inputType' => $inputType,
                    'options' => $field->options->choices,
                    'eval' => [
                        'required' => $field->required,
                    ],
                    'default' => $field->default,
                ]);

                break;

            case 'number':
                $inputType = 'text';

                if (false === (bool) $field->public) {
                    $inputType = 'hidden';
                }

                $eval = [
                    'rgxp' => 'digit',
                    'mandatory' => $field->required,
                ];

                if ((int) $this->mailchimpShowPlaceholder) {
                    $eval['placeholder'] = $field->name;
                }

                $form->addFormField($field->tag, [
                    'label' => $field->name,
                    'inputType' => $inputType,
                    'eval' => $eval,
                    'default' => $field->default,
                ]);

                break;

            case 'url':
                $inputType = 'text';

                if (false === (bool) $field->public) {
                    $inputType = 'hidden';
                }

                $eval = [
                    'rgxp' => 'url',
                    'mandatory' => $field->required,
                ];

                if ((int) $this->mailchimpShowPlaceholder) {
                    $eval['placeholder'] = $field->name;
                }

                $form->addFormField($field->tag, [
                    'label' => $field->name,
                    'inputType' => $inputType,
                    'eval' => $eval,
                    'default' => $field->default,
                ]);

                break;
        }

        return $field->tag;
    }
}
