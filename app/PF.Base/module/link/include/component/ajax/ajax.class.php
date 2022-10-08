<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright       [PHPFOX_COPYRIGHT]
 * @author          phpFox LLC
 * @package         Phpfox_Ajax
 * @version         $Id: ajax.class.php 7185 2014-03-11 19:08:04Z Fern $
 */
class Link_Component_Ajax_Ajax extends Phpfox_Ajax
{
    public function preview()
    {
        $this->error(false);
        $sWhiteList = Phpfox::getParam('core.url_spam_white_list');
        if (Phpfox::getParam('core.disable_all_external_urls')) {
            if (!empty($sWhiteList)) {
                $aWhiteList = explode(',', $sWhiteList);
                foreach ($aWhiteList as $index => $domain) {
                    if (strpos($this->get('value'), trim(str_replace('*', '', $domain))) !== false) {
                        break;
                    }
                    if ($index == count($aWhiteList) - 1) {
                        Phpfox_Error::set(_p('disabled_external_links'));
                    }
                }
            } else {
                Phpfox_Error::set(_p('disabled_external_links'));
            }
        }

        if (!Phpfox_Error::isPassed()) {
            echo json_encode(array('error' => implode('', Phpfox_Error::get())));
        } else {
            Phpfox::getBlock('link.preview');
            // button has been disabled while the site grabs the URL
            $this->call('<script text/javascript">$("#activity_feed_submit").removeAttr("disabled");</script>');
            $this->call('<script text/javascript">$bIsPreview = false;</script>');
        }
    }

    public function addViaStatusUpdate()
    {
        Phpfox::isUser(true);

        define('PHPFOX_FORCE_IFRAME', true);

        $aVals = (array)$this->get('val');
        $aCallback = null;
        $bConfirmSchedule = isset($aVals['confirm_scheduled']) && (int)$aVals['confirm_scheduled'];
        if (isset($aVals['callback_module']) && Phpfox::hasCallback($aVals['callback_module'], 'addLink')) {
            $aCallback = Phpfox::callback($aVals['callback_module'] . '.addLink', $aVals);
        }

        if (!empty($aCallback) && $aCallback['module'] == 'pages') {
            $valid = false;
            if(Phpfox::isAppActive('Core_Pages')) {
                $aPage = Phpfox::getService('pages')->getForView($aCallback['item_id']);
                if(!empty($aPage)) {
                    $valid = true;
                    if(isset($aPage['use_timeline']) && $aPage['use_timeline']) {
                        if (!defined('PAGE_TIME_LINE')) {
                            define('PAGE_TIME_LINE', true);
                        }
                    }
                }
            }
            if(!$valid && Phpfox::isAppActive('PHPfox_Groups')) {
                $aPage = Phpfox::getService('groups')->getForView($aCallback['item_id']);
                if(!empty($aPage)) {
                    $aVals['callback_module'] = $aCallback['module'] = 'groups';
                    if(isset($aPage['use_timeline']) && $aPage['use_timeline']) {
                        if (!defined('PAGE_TIME_LINE')) {
                            define('PAGE_TIME_LINE', true);
                        }
                    }
                }
            }
        }

        $canLoadNewContent = true;
        $feed = !empty($aVals['feed_id']) ? Phpfox::getService('feed')->getFeed($aVals['feed_id'], !empty($aCallback['table_prefix']) ? $aCallback['table_prefix'] : '') : null;
        $update = !empty($feed['feed_id']);

        //Check if the tagged user is removed from feed in their profile
        if (!empty($iProfileUserId = Phpfox::getService('profile')->getProfileUserId())
            && in_array($iProfileUserId, Phpfox::getService('feed.tag')->getTaggedUserIds($feed['item_id'], $feed['type_id']))) {
            $currentTaggedFriends = !empty($aVals['tagged_friends']) ? array_map(function($value) {
                return trim($value);
            }, explode(',', $aVals['tagged_friends'])) : [];
            $canLoadNewContent = in_array($iProfileUserId, $currentTaggedFriends);
        }

        if ($bConfirmSchedule) {
            if (Phpfox::getService('core.schedule')->scheduleItem(Phpfox::getUserId(), 'link', 'link', $aVals)) {
                $iScheduleTime = Phpfox::getLib('date')->mktime($aVals['schedule_hour'], $aVals['schedule_minute'], 0, $aVals['schedule_month'], $aVals['schedule_day'], $aVals['schedule_year']);
                $this->call('$Core.resetActivityFeedForm();');
                $this->call('$Core.loadInit();');
                $this->alert(_p('your_status_will_be_sent_on_time', ['time' => Phpfox::getTime(Phpfox::getParam('feed.feed_display_time_stamp'), Phpfox::getLib('date')->convertToGmt((int)$iScheduleTime))]), null, 300, 150, true);
            } else {
                $this->call("\$Core.activityFeedProcess(false); bCheckUrlForceAdd = true; if (aCheckUrlForceAdd) { aCheckUrlForceAdd['js_activity_feed_form'] = false };");
            }
        } else {
            if (($iId = Phpfox::getService('link.process')->add($aVals, false, $aCallback))) {
                (($sPlugin = Phpfox_Plugin::get('link.component_ajax_addviastatusupdate')) ? eval($sPlugin) : false);
                if ($canLoadNewContent) {
                    Phpfox::getService('feed')->callback($aCallback)->processAjax($iId, $update ? $feed['user_id'] : Phpfox::getUserId(), $update);
                } elseif ($update) {
                    $this->slideUp('#js_item_feed_' . $aVals['feed_id']);
                    $this->call("tb_remove();");
                    $this->call('setTimeout(function(){$Core.resetActivityFeedForm();$Core.loadInit();}, 500);');
                }
                $this->call("$('#js_preview_link_attachment_custom_form_sub').remove();");
            } else {
                $this->call("\$Core.activityFeedProcess(false); bCheckUrlForceAdd = true; if (aCheckUrlForceAdd) { aCheckUrlForceAdd['js_activity_feed_form'] = false };");
            }
        }
    }

    public function play()
    {
        $sEmbedCode = Phpfox::getService('link')->getEmbedCode($this->get('id'), ($this->get('popup') ? true : false));

        if ($this->get('popup')) {
            $this->setTitle(_p('viewing_video'));
            echo '<div class="t_center">';
            echo $sEmbedCode;
            echo '</div>';
        } elseif ($this->get('feed_id')) {
            $this->call('$(\'#js_item_feed_' . $this->get('feed_id') . '\').find(\'.activity_feed_content_link:first\').html(\'' . str_replace("'",
                    "\\'", $sEmbedCode) . '\');');
        } else {
            $this->html('#js_global_link_id_' . $this->get('id'), str_replace("'", "\\'", $sEmbedCode));
        }
    }

    public function attach()
    {
        Phpfox::isUser(true);

        $this->setTitle(_p('attach_a_link'));

        Phpfox::getBlock('link.attach');
    }

    public function delete()
    {
        Phpfox::isUser(true);

        Phpfox::getService('link.process')->delete($this->get('id'));

        $this->call("$('.extra_info').show();");
    }
}