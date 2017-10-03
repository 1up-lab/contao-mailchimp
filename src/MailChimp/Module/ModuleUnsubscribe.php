<?php

namespace Oneup\Contao\MailChimp\Module;

use Contao\Module;
use Contao\BackendTemplate;
use Contao\System;
use Contao\Input;
use Contao\Environment;
use Oneup\Contao\MailChimp\Model\MailChimpModel;
use Haste\Form\Form;
use Oneup\MailChimp\Client;
use Patchwork\Utf8;

class ModuleUnsubscribe extends Module
{
    protected $strTemplate = 'mod_mailchimp_unsubscribe';

    /** @var Client */
    protected $mailChimp;
    protected $objMailChimp;
    protected $mailChimpListId;

    public function generate()
    {
        if (TL_MODE == 'BE') {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['mailchimp_unsubscribe'][0]).' ###';

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

        $objForm = new Form('mailchimp-unsubscribe-'.$this->id, 'POST', function (Form $objHaste) {
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

        $objForm->addFormField('submit', [
            'label' => $GLOBALS['TL_LANG']['tl_module']['mailchimp']['labelSubmit'],
            'inputType' => 'submit'
        ]);

        $objForm->addContaoHiddenFields();

        $this->Template->error = false;

        if ($objForm->validate()) {
            $arrData = $objForm->fetchAll();

            $unsubscribed = $this->mailChimp->unsubscribeFromList($this->mailChimpListId, $arrData['email']);

            if ($unsubscribed) {
                $this->jumpToOrReload($this->mailchimpJumpTo);
            } else {
                $this->Template->error = true;
                $this->Template->errorMsg = $GLOBALS['TL_LANG']['tl_module']['mailchimp']['unsubscribeError'];
            }
        }

        $form = new \stdClass();
        $objForm->addToObject($form);

        $this->Template->form = $form;
    }
}
