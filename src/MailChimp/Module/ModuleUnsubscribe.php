<?php

namespace Oneup\Contao\MailChimp\Module;

use Oneup\Contao\MailChimp\Model\MailChimpModel;

use Haste\Form\Form;
use Oneup\MailChimp\MailChimp;

class ModuleUnsubscribe extends \Module
{
    protected $strTemplate = 'mod_mailchimp_unsubscribe';

    /** @var MailChimp */
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
        global $objPage;

        \System::loadLanguageFile('tl_module');

        $objForm = new Form('mailchimp-subscribe-'.$this->id, 'POST', function(Form $objHaste) {
            return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });

        $objForm->setFormActionFromPageId($objPage->id);

        $objForm->addFormField('email', [
            'label' => $GLOBALS['TL_LANG']['tl_module']['mailchimp']['labelEmail'],
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'rgxp' => 'email',
                'placeholder' => $GLOBALS['TL_LANG']['tl_module']['mailchimp']['placeholderEmail'],
            ],
        ]);

        $objForm->addFormField('submit', [
            'label' => $GLOBALS['TL_LANG']['tl_module']['mailchimp']['labelSubmit'],
            'inputType' => 'submit'
        ]);

        $objForm->addContaoHiddenFields();

        $this->Template->error = false;

        if ($objForm->validate()) {
            $arrData = $objForm->fetchAll();

            $mergeVars = [
                'FNAME' => $arrData['firstname'],
                'LNAME' => $arrData['lastname'],
            ];

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
