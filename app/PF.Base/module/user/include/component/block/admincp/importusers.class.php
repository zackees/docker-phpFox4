<?php
defined('PHPFOX') or exit('NO DICE!');

class User_Component_Block_Admincp_Importusers extends Phpfox_Component
{
    public function process()
    {
        $bIsUpload = true;

        if ($aFields = $this->getParam('field')) {
            $bIsUpload = false;
            $aRequiredFields = ['full_name', 'user_name', 'email', 'full_phone_number'];
            $aFieldText = [
                'full_name' => _p('display_name'),
                'user_name' => _p('username'),
                'email' => _p('email_address'),
                'full_phone_number' => _p('phone_number'),
                'gender' => _p('gender'),
                'country_iso' => _p('location'),
                'city_location' => _p('city'),//table user_field
                'postal_code' => _p('zip_postal_code'),//table user_field
                'country_child_id' => _p('state_province'),//table user_field
                'user_group_id' => _p('group')
            ];
            $aCustomGroups = Phpfox::getService('custom')->getForListing();
            $aCustomFields = [];
            if (!empty($aCustomGroups)) {
                foreach ($aCustomGroups as $aCustomGroup) {
                    foreach ($aCustomGroup['child'] as $aCustomField) {
                        $aCustomFields['cf_' . $aCustomField['field_name']] = _p($aCustomField['phrase_var_name']);
                    }
                }
            }

            $aMergeFields = array_merge($aFieldText, $aCustomFields);

            $aTextFields = [];
            foreach ($aFields as $sField) {
                $aTextFields[$sField] = $aMergeFields[$sField];
            }

            $aFields = $aTextFields;
            $bIsIncludeUserGroupField = isset($aFields['user_group_id']) ? true : false;
            unset($aFields['user_group_id']);
            $aUserGroups = [];
            foreach (Phpfox::getService('user.group')->getAll() as $aGroup) {
                $aUserGroups[$aGroup['user_group_id']] = \Core\Lib::phrase()->isPhrase($aGroup['title']) ? _p($aGroup['title']) : $aGroup['title'];
            }
            $this->template()->assign([
                'aFields' => $aFields,
                'aRequiredFields' => $aRequiredFields,
                'aUserGroups' => $aUserGroups,
                'bIsIncludeUserGroupField' => $bIsIncludeUserGroupField
            ]);
        }
        $this->template()->assign([
            'sTemplateDownloadLink' => '<a href="' . $this->url()->makeUrl('admincp.user.downloadtemplatefile') . '" target="_blank">' . _p('sample_csv_template') . '</a>',
            'bIsUpload' => $bIsUpload,
        ]);
        return 'block';
    }
}