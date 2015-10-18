<?php

namespace Oneup\Contao\Mailchimp\Module;

use Oneup\Contao\Mailchimp\MailChimp;
use Oneup\Contao\Mailchimp\Model\MailChimpModel;

use Haste\Form\Form;

class ModuleSubscripe extends \Module
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
        $objForm = new Form('mailchimp-subscribe', 'POST', function(Form $objHaste) {
            return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });

        $objForm->setFormActionFromPageId($this->mailchimpJumpTo);

        $objForm->addFormField('email', array(
            'label'         => 'E-Mail',
            'inputType'     => 'text',
            'eval'          => array('mandatory'=>true, 'rgxp'=>'email')
        ));

        $objForm->addFormField('submit', array(
            'label'     => 'Submit',
            'inputType' => 'submit'
        ));

        $objForm->addContaoHiddenFields();

        if ($objForm->validate()) {
            $arrData = $objForm->fetchAll();
        }

        $objForm->addToTemplate($this->Template);
    }
}
