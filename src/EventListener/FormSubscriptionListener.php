<?php

declare(strict_types=1);

namespace Oneup\Contao\MailChimpBundle\EventListener;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\DataContainer;
use Contao\Form;
use Contao\StringUtil;
use Oneup\Contao\MailChimpBundle\Model\MailChimpModel;
use Oneup\MailChimp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This service handles all necessary callbacks of the Mailchimp subscription functionality for Contao forms.
 */
class FormSubscriptionListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * Returns the list of group categories from the Mailchimp API 
     * for the currently selected Mailchimp list.
     * 
     * @Callback(table="tl_form", target="fields.mailchimpGroups.options")
     */
    public function onMailchimpGroupsOptionsCallback(DataContainer $dc): array
    {
        $options = [];

        if (empty($dc->activeRecord->mailchimpList)) {
            return $options;
        }

        $model = MailChimpModel::findByPk($dc->activeRecord->mailchimpList);

        if (null === $model) {
            return $options;
        }

        $api = new Client($model->listApiKey);

        $listGroupCategories = $api->getListGroupCategories($model->listId);

        if (empty($listGroupCategories->categories)) {
            return $options;
        }

        foreach ($listGroupCategories->categories as $category) {
            $listGroup = $api->getListGroup($category->list_id, $category->id);

            if (empty($listGroup->interests)) {
                continue;
            }

            foreach ($listGroup->interests as $interest) {
                $options[$category->title][$interest->id] = $interest->name;
            }
        }

        return $options;
    }

    /**
     * Checks whether the merge tags have at least "EMAIL".
     * 
     * @Callback(table="tl_form", target="fields.mailchimpMergeTags.save")
     * 
     * @param mixed $value
     * @return mixed
     */
    public function onMailchimpMergeTagsSaveCallback($value, DataContainer $dc)
    {
        $mergeTags = StringUtil::deserialize($value, true);

        foreach ($mergeTags as $mergeTag) {
            if ('EMAIL' === $mergeTag['key'] ?? null && !empty($mergeTag['value'])) {
                return $value;
            }
        }

        throw new \Exception($this->translator->trans('ERR.mailchimpMergeTagsEmailMissing', [], 'contao_default'));
    }

    /**
     * Subscribes to a Mailchimp list if enabled.
     * 
     * @Hook("processFormData")
     */
    public function onProcessFormData(array $submittedData, array $formData, ?array $files, array $labels, Form $form): void
    {
        // Check if Mailchimp subscriptions are enabled for this form
        if (!$form->enableMailchimp || empty($form->mailchimpList)) {
            return;
        }

        // Check if subscription process is confirmed
        if (!empty($form->mailchimpConfirmField) && empty($submittedData[$form->mailchimpConfirmField])) {
            return;
        }

        $model = MailChimpModel::findByPk($form->mailchimpList);

        if (null === $model) {
            return;
        }

        // Set interest groups
        $interests = [];
        foreach (StringUtil::deserialize($form->mailchimpGroups, true) as $group) {
            $interests[$group] = true;
        }

        // Extract merge vars
        $mergeVars = [];
        foreach (StringUtil::deserialize($form->mailchimpMergeTags, true) as $mergeTag) {
            $mergeVars[$mergeTag['key']] = $submittedData[$mergeTag['value']] ?? null;
        }

        // Check if we have an email address
        if (empty($mergeVars['EMAIL'])) {
            return;
        }

        $api = new Client($model->listApiKey);
        $email = $mergeVars['EMAIL'];
        $result = $api->subscribeToList($model->listId, $email, $mergeVars, (bool) $form->mailchimpOptIn, $interests);

        if ($result) {
            $this->logger->log(LogLevel::INFO, sprintf('Successfully subscribed "%s" to Mailchimp list "%s".', $email, $model->listName), ['contao' => new ContaoContext(__METHOD__, TL_GENERAL)]);
        } else {
            $this->logger->log(LogLevel::INFO, sprintf('Could not subscribe "%s" to Mailchimp list "%s".', $email, $model->listName), ['contao' => new ContaoContext(__METHOD__, TL_ERROR)]);
        }
    }
}
