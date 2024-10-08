<?php

declare(strict_types=1);

namespace Oneup\Contao\MailChimpBundle\Module;

use Codefog\HasteBundle\Form\Form;
use Contao\BackendTemplate;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Oneup\Contao\MailChimpBundle\Event\ModifyFormEvent;
use Oneup\Contao\MailChimpBundle\Model\MailChimpModel;
use Oneup\MailChimp\Client;
use Oneup\MailChimp\Exception\ApiException;
use Patchwork\Utf8;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ModuleSubscribe extends Module
{
    protected $strTemplate = 'mod_mailchimp_subscribe';

    /** @var Client */
    protected $mailChimp;
    protected $objMailChimp;
    protected $mailChimpListId;
    protected ScopeMatcher $scopeMatcher;
    protected RequestStack $requestStack;

    public function __construct($objModule, $strColumn = 'main')
    {
        $this->scopeMatcher = System::getContainer()->get('contao.routing.scope_matcher');
        $this->requestStack = System::getContainer()->get('request_stack');

        parent::__construct($objModule, $strColumn);
    }

    public function generate(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request && $this->scopeMatcher->isBackendRequest($request)) {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['mailchimp_subscribe'][0]) . ' ###';

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

        $objForm = new Form('mailchimp-subscribe-' . $this->id, 'POST', fn (Form $objHaste) => Input::post('FORM_SUBMIT') === $objHaste->getFormId());

        $objForm->setAction(Environment::get('request'));
        $objForm->addContaoHiddenFields();

        if (null === $this->objMailChimp->fields || '' === $this->objMailChimp->fields) {
            /** @var LoggerInterface $logger */
            $logger = System::getContainer()->get('monolog.logger.contao');
            $logger->info(
                'No MailChimp fields found. Did you configure your settings correctly?',
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS)]
            );

            $this->Template->error = true;
            $this->Template->errorMsg = 'No MailChimp fields found. Did you configure your settings correctly?';

            return;
        }

        $fields = [];

        if (null !== $this->objMailChimp->fields) {
            $fields = json_decode($this->objMailChimp->fields);
        }

        // sort fields by displayOrder ASC
        usort($fields, static fn ($a, $b) => ($a->displayOrder > $b->displayOrder) ? 1 : -1);

        $fields = $this->insertEmailField($fields);

        $mergeVarTags = [];

        if (\is_array($fields)) {
            foreach ($fields as $field) {
                $addedName = $this->addFieldToForm($field, $objForm);

                if (null !== $addedName) {
                    $mergeVarTags[] = $addedName;
                }
            }
        }

        $groups = [];

        if (null !== $this->objMailChimp->groups) {
            // add the interest groups
            $groups = json_decode($this->objMailChimp->groups);
        }

        // sort groups by displayOrder ASC
        usort($groups, fn ($a, $b) => (($a->displayOrder ?? 0) > ($b->displayOrder ?? 0)) ? 1 : -1);

        $interestCategoryIds = [];

        if (\is_array($groups)) {
            foreach ($groups as $category) {
                $addedName = $this->addInterestCategoryToForm($category, $objForm);

                if (null !== $addedName) {
                    $interestCategoryIds[] = $addedName;
                }
            }
        }

        if ($this->mailchimpCaptcha) {
            $objForm->addCaptchaFormField('subscribe-' . $this->id);
        }

        $objForm->addFormField('submit', [
            'label' => $GLOBALS['TL_LANG']['tl_module']['mailchimp']['labelSubmit'],
            'inputType' => 'submit',
        ]);

        // event: modify form
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = System::getContainer()->get('event_dispatcher');
        $eventDispatcher->dispatch(new ModifyFormEvent($objForm, $this), ModifyFormEvent::SUBSCRIBE);

        $this->Template->error = false;

        if ($objForm->validate()) {
            $arrData = $objForm->fetchAll();

            $mergeVars = [];

            foreach ($mergeVarTags as $tag) {
                $mergeVars[$tag] = $arrData[$tag];
            }

            $interests = [];

            foreach ($interestCategoryIds as $id) {
                if (!empty($arrData[$id])) {
                    if (\is_array($arrData[$id])) {
                        foreach ($arrData[$id] as $groupId) {
                            $interests[$groupId] = true;
                        }
                    } else {
                        $interests[$arrData[$id]] = true;
                    }
                }
            }

            try {
                $subscribed = $this->mailChimp->subscribeToList(
                    $this->mailChimpListId,
                    $arrData['EMAIL'],
                    $mergeVars,
                    (bool) $this->mailchimpOptin,
                    $interests
                );

                if ($subscribed) {
                    $this->jumpToOrReload($this->mailchimpJumpTo);
                } else {
                    $this->Template->error = true;
                    $this->Template->errorMsg = $GLOBALS['TL_LANG']['tl_module']['mailchimp']['subscribeError'];
                }
            } catch (ApiException $e) {
                $this->Template->error = true;
                $this->Template->errorMsg = $e->getMessage();
            }
        }

        $form = new \stdClass();
        $objForm->addToObject($form);

        $this->Template->form = $form;
    }

    /**
     * Locates the position of the email field within the array of fields and inserts it.
     */
    protected function insertEmailField(array $fields): array
    {
        $email = (object) [
            'id' => 0,
            'tag' => 'EMAIL',
            'name' => $GLOBALS['TL_LANG']['tl_module']['mailchimp']['labelEmail'],
            'type' => 'text',
            'displayOrder' => -1,
            'required' => true,
            'public' => true,
        ];

        // if first field is displayed second, insert email field before
        if (2 === $fields[0]->displayOrder) {
            $email->displayOrder = 1;
        } else {
            // check if display order is consecutive
            $index = $fields[0]->displayOrder;

            foreach ($fields as $field) {
                // if one display slot is missing, the email field goes here
                if (($index + 1) === $field->displayOrder) {
                    $email->displayOrder = $index;
                    break;
                }

                ++$index;
            }

            // otherwise, append email field
            if (-1 === $email->displayOrder) {
                $email->displayOrder = (\count($fields) + 1);
            }
        }

        array_splice($fields, $email->displayOrder - 1, 0, [$email]);

        return $fields;
    }

    /**
     * Return the name of the field.
     */
    protected function addFieldToForm(\stdClass $field, Form $form): ?string
    {
        if (!\in_array($field->type, ['text', 'number', 'website', 'address', 'dropdown', 'radio', 'url', 'date', 'birthday', 'phone'], true)) {
            return null;
        }

        switch ($field->type) {
            case 'email':
                $inputType = 'text';

                $eval = [
                    'mandatory' => true,
                    'rgxp' => 'email',
                ];

                if ((int) $this->mailchimpShowPlaceholder) {
                    $eval['placeholder'] = $GLOBALS['TL_LANG']['tl_module']['mailchimp']['placeholderEmail'];
                }

                $form->addFormField('email', [
                    'label' => $field->name,
                    'inputType' => $inputType,
                    'eval' => $eval,
                ]);

                break;

            case 'text':
            case 'address':
            case 'date':
            case 'birthday':
            case 'phone':
                $inputType = 'text';

                $eval = [
                    'mandatory' => $field->required,
                ];

                if (property_exists($field, 'options') &&
                    property_exists($field->options, 'size') &&
                    ($maxLength = (int) $field->options->size) > 0) {
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
                    'default' => $field->default ?? '',
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
                        'mandatory' => $field->required,
                    ],
                    'default' => $field->default ?? '',
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
                        'mandatory' => $field->required,
                    ],
                    'default' => $field->default ?? '',
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
                    'default' => $field->default ?? '',
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
                    'default' => $field->default ?? '',
                ]);

                break;
        }

        return $field->tag;
    }

    /**
     * Return the name of the field.
     */
    protected function addInterestCategoryToForm(\stdClass $category, Form $form): ?string
    {
        if (!\in_array($category->type, ['checkboxes', 'radio', 'dropdown'], true)) {
            return null;
        }

        $interests = $category->interests;

        // sort interests by displayOrder ASC
        usort($interests, fn ($a, $b) => ($a->displayOrder > $b->displayOrder) ? 1 : -1);

        $inputType = str_replace(['checkboxes', 'dropdown'], ['checkbox', 'select'], $category->type);
        $options = [];
        $mandatoryInterests = StringUtil::deserialize($this->mailchimpMandatoryInterests, true);
        $eval = ['mandatory' => \in_array($category->id, $mandatoryInterests, true)];

        foreach ($interests as $interest) {
            $options[$interest->id] = $interest->name;
        }

        if (empty($options)) {
            return null;
        }

        if ('dropdown' === $category->type) {
            $eval['includeBlankOption'] = true;
        }

        $form->addFormField($category->id, [
            'label' => $category->title,
            'inputType' => $inputType,
            'options' => $options,
            'eval' => $eval,
        ]);

        return $category->id;
    }
}
