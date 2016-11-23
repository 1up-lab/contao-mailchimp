<?php

namespace Oneup\Contao\MailChimp\Model;

use Contao\DC_Table;
use Oneup\MailChimp\Client as ApiClient;
use Oneup\MailChimp\Exception\ApiException;

class MailChimpModel extends \Model
{
    protected static $strTable = 'tl_mailchimp';

    public static function saveListFields(DC_Table $dcTable)
    {
        $record = MailChimpModel::findByPk($dcTable->activeRecord->id);

        $listId = $record->listId;
        $apiKey = $record->listApiKey;

        // Create new Api Client
        $apiClient = new ApiClient($apiKey);

        try {
            $fieldData = $apiClient->getListFields($listId);
        } catch (ApiException $e) {
            // TODO Implement
            die();
        }

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
