<?php

declare(strict_types=1);

namespace Oneup\Contao\MailChimpBundle\Module;

use Codefog\HasteBundle\Form\Form;
use Contao\BackendTemplate;
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use Contao\System;
use Oneup\Contao\MailChimpBundle\Event\ModifyFormEvent;
use Oneup\Contao\MailChimpBundle\Model\MailChimpModel;
use Oneup\MailChimp\Client;
use Patchwork\Utf8;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ModuleUnsubscribe extends Module
{
    protected $strTemplate = 'mod_mailchimp_unsubscribe';

    /** @var Client */
    protected $mailChimp;
    protected $objMailChimp;
    protected $mailChimpListId;

    public function generate(): string
    {
        if (TL_MODE === 'BE') {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['mailchimp_unsubscribe'][0]) . ' ###';

            return $objTemplate->parse();
        }

        $this->objMailChimp = MailChimpModel::findByPk($this->mailchimpList);
        $this->mailChimp = new Client($this->objMailChimp->listApiKey);
        $this->mailChimpListId = $this->objMailChimp->listId;

        return parent::generate();
    }

    protected function compile(): void
    {
        System::loadLanguageFile('tl_module');

        $objForm = new Form('mailchimp-unsubscribe-' . $this->id, 'POST', fn (Form $objHaste) => Input::post('FORM_SUBMIT') === $objHaste->getFormId());

        $objForm->setAction(Environment::get('request'));

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
            'inputType' => 'submit',
        ]);

        $objForm->addContaoHiddenFields();

        // event: modify form
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = System::getContainer()->get('event_dispatcher');
        $eventDispatcher->dispatch(new ModifyFormEvent($objForm, $this), ModifyFormEvent::UNSUBSCRIBE);

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
