<?php
defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Edit_Schedule extends Phpfox_Component
{
    public function process()
    {
        $bLoadCheckIn = false;
        $bLoadTagFriends = false;
        $bLoadPrivacy = false;

        $iScheduleId = $this->request()->get('id');
        $aScheduleItem = Phpfox::getService('core.schedule')->getScheduleItem($iScheduleId, true);
        if (empty($aScheduleItem['schedule_id']) || $aScheduleItem['user_id'] != Phpfox::getUserId()) {
            return Phpfox_Error::set('schedule_not_found');
        }

        $aForms = $aScheduleItem['data'];
        $aForms['schedule_id'] = $aForms['feed_id'] = $iScheduleId;
        $aForms['type_id'] = 'edit_schedule';
        $aForms['item_type'] = $aScheduleItem['item_type'];

        if (Phpfox::hasCallback($aScheduleItem['item_type'], 'getAdditionalEditBlock')) {
            $sTemplate = Phpfox::callback($aScheduleItem['item_type'] . '.getAdditionalEditBlock');
            $this->template()->assign([
                'additionalEditTemplate' => $sTemplate
            ]);
        }

        if (Phpfox::getParam('feed.enable_check_in') && Phpfox::getParam('core.google_api_key')) {
            $bLoadCheckIn = true;
        }
        if (Phpfox::isModule('friend') && Phpfox::getParam('feed.enable_tag_friends') && $this->getParam('allowTagFriends', true)) {
            $bLoadTagFriends = true;
        }
        if (isset($aForms['privacy'])) {
            $bLoadPrivacy = true;
        }

        $generateStatus = Phpfox::getLib('parse.output')->htmlspecialchars(html_entity_decode($aForms['user_status'], ENT_QUOTES, 'UTF-8'));
        preg_match_all('/(?<match>\[(?<type>[\w]+)=(?<id>[\d]+)\](?<name>[\p{L}\p{P}\p{S}\p{N}\s]+)\[\/([\w]+)\])/Umu', $generateStatus, $matches);
        if (isset($matches['match'])) {
            foreach ($matches['match'] as $key => $match) {
                if (isset($matches['type'][$key]) && isset($matches['id'][$key]) && isset($matches['name'][$key])) {
                    $generateStatus = str_replace($match, sprintf('<span id="generated" class="generatedMentionTag" contenteditable="false" data-type="%s" data-id="%d">%s</span>', $matches['type'][$key], $matches['id'][$key], $matches['name'][$key]), $generateStatus);
                }
            }
        }
        $generateStatus = preg_replace('/\R/', '<br>', $generateStatus);
        if (preg_match('/<\/(span|div|a)>$/', $generateStatus)) {
            //Support Firefox
            $generateStatus .= '<br>';
        }

        if (!empty($aScheduleItem['extra'])) {
            $this->template()->clean('aScheduleImages');
            $this->template()->assign($aScheduleItem['extra']);
        }

        $this->template()->assign([
            'iScheduleId'     => $iScheduleId,
            'bIsEdit'         => true,
            'iFeedId'         => $iScheduleId,
            'iModuleId'       => $aScheduleItem['module_id'],
            'generateStatus'  => $generateStatus,
            'aForms'          => $aForms,
            'bLoadTagFriends' => $bLoadTagFriends,
            'bLoadCheckIn'    => $bLoadCheckIn,
            'bLoadPrivacy'    => $bLoadPrivacy,
        ]);
        return 'block';
    }
}
