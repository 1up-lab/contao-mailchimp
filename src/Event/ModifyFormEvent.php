<?php

declare(strict_types=1);

namespace Oneup\Contao\MailChimpBundle\Event;

use Codefog\HasteBundle\Form\Form;
use Contao\Module;
use Symfony\Contracts\EventDispatcher\Event;

class ModifyFormEvent extends Event
{
    /**
     * The contao_mailchimp.modify_subscribe_form event is triggered when the subscribe form is finished creating the form object.
     */
    public const SUBSCRIBE = 'contao_mailchimp.modify_subscribe_form';

    /**
     * The contao_mailchimp.modify_unsubscribe_form event is triggered when the unsubscribe form is finished creating the form object.
     */
    public const UNSUBSCRIBE = 'contao_mailchimp.modify_unsubscribe_form';

    private Form $form;
    private Module $module;

    public function __construct(Form $form, Module $module)
    {
        $this->form = $form;
        $this->module = $module;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getModule(): Module
    {
        return $this->module;
    }
}
