<?php

namespace Oneup\Contao\MailChimp\Module;

use Oneup\Contao\MailChimp\MailChimp;
use Oneup\Contao\MailChimp\Model\MailChimpModel;

use Haste\Form\Form;

class ModuleSubscribe extends \Module
{
    protected $strTemplate = 'mod_mailchimp_subscribe';
    protected $mailChimp;
    protected $objMailChimp;
    protected $mailChimpListId;

    public function generate()
    {
        $this->objMailChimp = MailChimpModel::findByPk($this->mailchimpList);
        $this->mailChimp = new MailChimp($this->objMailChimp->listApiKey);
        $this->mailChimpListId = $this->objMailChimp->listId;

        return parent::generate();
    }

    protected function compile()
    {
        \System::loadLanguageFile('tl_module');

        $objForm = new Form('mailchimp-subscribe', 'POST', function(Form $objHaste) {
            return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });

        $objForm->setFormActionFromPageId($this->mailchimpJumpTo);

        $objForm->addFormField('email', [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['mailchimp']['labelEmail'],
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'rgxp' => 'email',
                'placeholder' => &$GLOBALS['TL_LANG']['tl_module']['mailchimp']['placeholderEmail'],
            ],
        ]);

        $objForm->addFormField('firstname', [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['mailchimp']['labelFirstname'],
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'placeholder' => &$GLOBALS['TL_LANG']['tl_module']['mailchimp']['placeholderFirstname'],
            ],
        ]);

        $objForm->addFormField('lastname', [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['mailchimp']['labelLastname'],
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'placeholder' => &$GLOBALS['TL_LANG']['tl_module']['mailchimp']['placeholderLastname'],
            ],
        ]);

        $objForm->addFormField('submit', [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['mailchimp']['labelSubmit'],
            'inputType' => 'submit'
        ]);

        $objForm->addContaoHiddenFields();

        if ($objForm->validate()) {
            $arrData = $objForm->fetchAll();

            // form action
        }

        $form = new \stdClass();
        $objForm->addToObject($form);

        $this->Template->form = $form;
    }
}
