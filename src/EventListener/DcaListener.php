<?php

declare(strict_types=1);

namespace Oneup\Contao\MailChimpBundle\EventListener;

use Contao\Controller;
use Contao\DC_Table;
use Contao\System;
use Oneup\Contao\MailChimpBundle\Model\MailChimpModel;
use Oneup\MailChimp\Client as ApiClient;
use Oneup\MailChimp\Exception\ApiException;

class DcaListener
{
    public static function onSaveListFields(DC_Table $dcTable): void
    {
        $record = MailChimpModel::findByPk($dcTable->activeRecord->id);

        $listId = $record->listId;
        $apiKey = $record->listApiKey;
        $fieldData = null;

        // Create new Api Client
        $apiClient = new ApiClient($apiKey);

        try {
            // Get list fields
            $fields = [];
            $fieldsOffset = 0;
            $fieldsLimit = 10;

            while(!empty(($fieldData = $apiClient->getListFields($listId, $fieldsOffset, $fieldsLimit))->merge_fields)) {
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

            while(!empty(($categoryData = $apiClient->getListGroupCategories($listId, $categoryOffset, $categoryLimit))->categories)) {
                $categoryOffset += $categoryLimit;

                foreach ($categoryData->categories as $group) {
                    $interests = [];
                    $groupOffset = 0;
                    $groupLimit = 10;

                    while(!empty(($groupData = $apiClient->getListGroup($listId, $group->id, $groupOffset, $groupLimit))->interests)) {
                        $groupOffset += $groupLimit;

                        foreach ($groupData->interests as $interest) {
                            $interests[] = [
                                'id' => $interest->id,
                                'name' => $interest->name,
                                'displayOrder' => $interest->display_order
                            ];
                        }
                    }

                    $categories[] = [
                        'id' => $group->id,
                        'title' => $group->title,
                        'type' => $group->type,
                        'interests' => $interests
                    ];
                }
            }

            $record->groups = json_encode($categories);

            // Save the record
            $record->save();
        } catch (ApiException $e) {
            System::log(
                sprintf('There was an error with the MailChimp API: %s', $e->getMessage()),
                __METHOD__,
                TL_ERROR
            );

            Controller::redirect('contao/main.php?act=error');
        }
    }

    public function onLoadInterests($dc)
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
