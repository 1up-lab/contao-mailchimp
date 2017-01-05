<?php

namespace Oneup\Contao\MailChimp\Model;

use Contao\Controller;
use Contao\DC_Table;
use Contao\Model;
use Contao\System;
use Oneup\MailChimp\Client as ApiClient;
use Oneup\MailChimp\Exception\ApiException;

class MailChimpModel extends Model
{
    protected static $strTable = 'tl_mailchimp';

    public static function saveListFields(DC_Table $dcTable)
    {
        $record = MailChimpModel::findByPk($dcTable->activeRecord->id);

        $listId = $record->listId;
        $apiKey = $record->listApiKey;
        $fieldData = null;

        // Create new Api Client
        $apiClient = new ApiClient($apiKey);

        try {
            $fieldData = $apiClient->getListFields($listId);
        } catch (ApiException $e) {
            System::log(
                sprintf('There was an error with the MailChimp API: %s', $e->getMessage()),
                __METHOD__,
                TL_ERROR
            );

            Controller::redirect('contao/main.php?act=error');
        }

        if ($fieldData) {
            $fields = [];
            $rawFields = $fieldData->merge_fields;

            foreach ($rawFields as $rawField) {
                $field = [
                    'id' => $rawField->merge_id,
                    'tag' => $rawField->tag,
                    'name' => $rawField->name,
                    'type' => $rawField->type,
                    'required' => !!$rawField->required,
                    'options' => $rawField->options
                ];

                $fields[] = $field;
            }

            $record->fields = json_encode($fields);
            $record->save();
        }
    }
}
