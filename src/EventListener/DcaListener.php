<?php

declare(strict_types=1);

namespace Oneup\Contao\MailChimpBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\DataContainer;
use Oneup\Contao\MailChimpBundle\Model\MailChimpModel;
use Oneup\MailChimp\Client as ApiClient;
use Oneup\MailChimp\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class DcaListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onSaveListFields(DataContainer $dataContainer): void
    {
        $record = MailChimpModel::findByPk($dataContainer->activeRecord->id);

        $listId = $record->listId;
        $apiKey = $record->listApiKey;

        // Create new Api Client
        $apiClient = new ApiClient($apiKey);

        try {
            // Get list fields
            $fields = [];
            $fieldsOffset = 0;
            $fieldsLimit = 10;

            while (!empty(($fieldData = $apiClient->getListFields($listId, $fieldsOffset, $fieldsLimit))->merge_fields)) {
                $fieldsOffset += $fieldsLimit;

                foreach ($fieldData->merge_fields as $rawField) {
                    $field = [
                        'id' => $rawField->merge_id,
                        'tag' => $rawField->tag,
                        'name' => $rawField->name,
                        'type' => $rawField->type,
                        'displayOrder' => $rawField->display_order,
                        'required' => (bool) $rawField->required,
                        'options' => $rawField->options,
                        'public' => $rawField->public,
                        'default' => $rawField->default_value,
                    ];

                    $fields[] = $field;
                }
            }

            $record->fields = json_encode($fields);

            // Get interest groups
            $categories = [];
            $categoryOffset = 0;
            $categoryLimit = 10;

            while (!empty(($categoryData = $apiClient->getListGroupCategories($listId, $categoryOffset, $categoryLimit))->categories)) {
                $categoryOffset += $categoryLimit;

                foreach ($categoryData->categories as $group) {
                    $interests = [];
                    $groupOffset = 0;
                    $groupLimit = 10;

                    while (!empty(($groupData = $apiClient->getListGroup($listId, $group->id, $groupOffset, $groupLimit))->interests)) {
                        $groupOffset += $groupLimit;

                        foreach ($groupData->interests as $interest) {
                            $interests[] = [
                                'id' => $interest->id,
                                'name' => $interest->name,
                                'displayOrder' => $interest->display_order,
                            ];
                        }
                    }

                    $categories[] = [
                        'id' => $group->id,
                        'title' => $group->title,
                        'type' => $group->type,
                        'interests' => $interests,
                    ];
                }
            }

            $record->groups = json_encode($categories);

            // Save the record
            $record->save();
        } catch (ApiException $e) {
            $this->logger->log(
                LogLevel::ERROR,
                sprintf('There was an error with the MailChimp API: %s', $e->getMessage()),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );

            Controller::redirect('contao/main.php?act=error');
        }
    }

    public function onLoadInterests($dc): array
    {
        if ($dc && $dc->activeRecord && $dc->activeRecord->mailchimpList) {
            if (null !== ($record = MailChimpModel::findByPk($dc->activeRecord->mailchimpList))) {
                if (!empty($record->groups) && ($groups = json_decode($record->groups))) {
                    $options = [];

                    foreach ($groups as $group) {
                        if ('hidden' !== $group->type) {
                            $options[$group->id] = $group->title;
                        }
                    }

                    return $options;
                }
            }
        }

        return [];
    }
}
