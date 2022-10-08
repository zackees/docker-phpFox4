<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Ajax_Ajax extends Phpfox_Ajax
{

    public function activateCountry()
    {
        $sIso = $this->get('country_iso');
        $sActive = (int)$this->get('active');
        if (empty($sIso)) {
            return false;
        }
        if (Phpfox::getService('core.country.process')->updateCountryActive(explode(',', $sIso), $sActive) && ($this->get('type') == 'multiple')) {
            $this->alert($sActive == 1 ? _p('activate_selected_successfully') : _p('deactivate_selected_successfully'));
            $this->call('setTimeout(function(){$Core.reloadPage();},1500);');
        }
    }

    public function ajaxPaging()
    {
        $aParams = $this->getAll();
        $sBlock = $aParams['block'];
        $sContainer = $aParams['container'];
        unset($aParams['block']);
        $aParams['ajax_paging'] = true;

        if ($sContainer) {
            Phpfox::getBlock($sBlock, $aParams);
            if (!empty($aParams['type']) && $aParams['type'] == 'loadmore') {
                $this->call('$("' . $sContainer . '").append("' . $this->getContent('false') . '");')
                    ->remove($sContainer . ' .ajax-paging-loading');
            } else {
                $this->html($sContainer, $this->getContent(false));
            }
            $this->call('$Core.loadInit();');
        }
    }

    public function buildStats()
    {
        Phpfox::getBlock('core.admin-stat');

        $this->remove('#js_core_site_stat_build');
        $this->html('#js_core_site_stat tbody', $this->getContent(false));
    }

    public function message()
    {
        Phpfox::getBlock('core.message', [
                'sMessage' => ''
            ]
        );
        $this->call('<script type="text/javascript">$(\'#js_custom_core_message\').html(sCustomMessageString);</script>');
    }

    public function info()
    {
        Phpfox::getBlock('core.info');

        $this->html('#' . $this->get('temp_id') . '', $this->getContent(false));
        $this->call('$(\'#' . $this->get('temp_id') . '\').parent().show();');
    }

    public function activity()
    {
        Phpfox::getBlock('core.activity');

        $this->html('#' . $this->get('temp_id') . '', $this->getContent(false));
        $this->call('$(\'#' . $this->get('temp_id') . '\').parent().show();');
    }

    /**
     * Core progress bar using apc_fetch.
     */
    public function progress()
    {
        return false;
    }

    public function updateComponentSetting()
    {
        $aVals = $this->get('val');

        if (Phpfox::getService('core.process')->updateComponentSetting($aVals)) {
            Phpfox::getBlock($aVals['load_block']);

            if (isset($aVals['load_entire_block'])) {
                $this->call('$(\'#' . $aVals['block_id'] . '\').before(\'' . $this->getContent() . '\').remove();');
            } else {
                $this->call('$(\'#' . $aVals['block_id'] . '\').find(\'.content\').html(\'' . $this->getContent() . '\');');
            }

            if (isset($aVals['load_init'])) {
                $this->call('$Core.loadInit();');
            }
        }
    }

    public function hideBlock()
    {
        if ($this->get('sController') == 'pages.view') {
            Phpfox::getService('theme.process')->updateBlock([
                    'cache_id' => $this->get('type_id'),
                    'item_id' => $this->get('custom_item_id'),
                    'type_id' => 'pages',
                    'is_installed' => '1'
                ]
            );
        } else {
            Phpfox::getService('core.process')->hideBlock($this->get('block_id'), $this->get('type_id'), $this->get('sController'));
        }

        $this->softNotice('Block was hidden');
    }

    public function getEditBarNew()
    {
        Phpfox::getBlock('core.new-setting');
        $this->html('#js_edit_block_' . $this->get('block_id'), $this->getContent(false))->slideDown('#js_edit_block_' . $this->get('block_id'));
    }

    public function getChildren()
    {
        Phpfox::getBlock('core.country-child', ['admin_search' => $this->get('admin_search'), 'country_child_value' => $this->get('country_iso'), 'country_child_id' => $this->get('country_child_id')]);

        $this->remove('#js_cache_country_iso')->html('#js_country_child_id', $this->getContent(false));
        $this->call('$Core.initSelectize();');
    }

    public function statOrdering()
    {
        if (Phpfox::getService('core.stat.process')->updateOrder($this->get('val'))) {
        }
    }

    /**
     * Clone of statOrdering to change the order of the items shown when cancelling an account
     */
    public function cancellationsOrdering()
    {
        Phpfox::getService('user.cancellations.process')->updateOrder($this->get('val'));
        $this->call('$Core.addNoticeMessage("' . _p('order_updated') . '");');
        $this->call('$Core.closeAjaxMessage();');
    }

    /**
     * Clone of updateStatActivity, activates/deactivates a cancellation
     */
    public function updateCancellationsActivity()
    {
        if (Phpfox::getService('user.cancellations.process')->updateActivity($this->get('id'), $this->get('active'))) {

        }
    }

    public function updateStatActivity()
    {
        if (Phpfox::getService('core.stat.process')->updateActivity($this->get('id'), $this->get('active'))) {

        }
    }

    public function ftpPathSearch()
    {
        if (($aVals = $this->get('val'))) {
            define('PHPFOX_FTP_LOGIN_PASS', true);

            $this->error(false);

            if (Phpfox::getLib('ftp')->connect($aVals['host'], $aVals['user_name'], $aVals['password'])) {
                $sPath = Phpfox::getLib('ftp')->getPath();

                if ($sPath === false) {
                    $this->html('#js_ftp_check_process', '')->html('#js_ftp_error', implode('', Phpfox_Error::get()))->show('#js_ftp_error');

                    return;
                }

                if (Phpfox::getLib('ftp')->test($sPath)) {
                    $this->hide('#js_ftp_form')->show('#js_ftp_path')->val('#js_ftp_actual_path', str_replace('\\', '/', $sPath))->html('#js_ftp_check_process', '');
                    if (empty($sPath)) {
                        $this->show('#js_empty_ftp_path');
                    }
                }
            }

            $this->html('#js_ftp_check_process', '')->html('#js_ftp_error', implode('', Phpfox_Error::get()))->show('#js_ftp_error');

            return;
        }

        Phpfox::getBlock('core.ftp');
    }

    public function countryOrdering()
    {
        Phpfox::isAdmin(true);
        $aVals = $this->get('val');
        Phpfox::getService('core.process')->updateOrdering([
                'table' => 'country',
                'key' => 'country_iso',
                'values' => $aVals['ordering']
            ]
        );
        Phpfox::getLib('cache')->removeGroup('country');
    }

    public function currencyOrdering()
    {
        Phpfox::isAdmin(true);
        $aVals = $this->get('val');
        Phpfox::getService('core.process')->updateOrdering([
                'table' => 'currency',
                'key' => 'currency_id',
                'values' => $aVals['ordering']
            ]
        );
        Phpfox::getLib('cache')->removeGroup('currency');
    }

    public function updateCurrencyDefault()
    {
        Phpfox::getService('core.currency.process')->updateDefault($this->get('id'), $this->get('active'));
        $this->reload();
    }

    public function updateCurrencyActivity()
    {
        if (Phpfox::getService('core.currency.process')->updateActivity($this->get('id'), $this->get('active'))) {
            $this->call("window.location.reload(true);");
        }
    }

    public function countryChildOrdering()
    {
        Phpfox::isAdmin(true);
        $aVals = $this->get('val');
        Phpfox::getService('core.process')->updateOrdering([
                'table' => 'country_child',
                'key' => 'child_id',
                'values' => $aVals['ordering']
            ]
        );
    }

    public function prompt()
    {
        $sPhrase = '';
        $sTitle = '';
        $sCommand = '';
        $sError = '';

        switch ($this->get('type')) {
            case 'url':
                $sPhrase = _p('enter_the_url_of_your_link');
                $sCommand = 'Editor.createBBtag(\'[link=\\\'\' + $(\'#js_global_prompt_value\').val() + \'\\\']\', \'[/link]\', \'' . $this->get('editor') . '\', $(\'#js_global_prompt_value\').val());';
                $sError = _p('fill_in_a_proper_url');
                $sTitle = _p('url');
                break;
            case 'img':
                $sPhrase = _p('enter_the_url_of_your_image');
                $sCommand = 'Editor.createBBtag(\'[img]\' + $(\'#js_global_prompt_value\').val() + \'\', \'[/img]\', \'' . $this->get('editor') . '\');';
                $sError = _p('provide_a_proper_image_path');
                $sTitle = _p('image');
                break;
        }

        echo '<div class="main_break"></div>';
        echo '<div id="js_prompt_error_message" class="error_message" style="display:none;">' . $sError . '</div>';
        echo $sPhrase;
        echo '<div class="p_4"><input type="text" name="url" value="http://" style="width:80%;" id="js_global_prompt_value" /><div class="p_top_4"><input type="submit" value="' . _p('submit') . '" class="button btn-primary" onclick="if (empty($(\'#js_global_prompt_value\').val()) || $(\'#js_global_prompt_value\').val() == \'http://\') { $(\'#js_prompt_error_message\').show(); } else { ' . $sCommand . ' tb_remove(); }" /></div></div>';
        echo '<script type="text/javascript">$(\'#TB_ajaxWindowTitle\').html(\'' . str_replace("'", "\'", $sTitle) . '\');</script>';
    }

    public function showGiftPoints()
    {
        Phpfox::getBlock('core.giftpoints', ['user_id' => $this->get('user_id')]);
    }

    public function doGiftPoints()
    {
        if (Phpfox::getService('user.activity')->doGiftPoints($this->get('user_id'), $this->get('amount'))) {
            $this->html('#div_show_gift_points', _p('gift_sent_successfully'));
        }
    }


    public function getMyCity()
    {
        $sInfo = Phpfox_Request::instance()->send('http://freegeoip.net/json/' . Phpfox_Request::instance()->getIp(), [], 'GET');
        $oInfo = (object)[];
        if ($sInfo) {
            $oInfo = json_decode($sInfo);
        }
        if ($this->get('section') == 'feed') {
            // during testing latlng wont work
            if (empty($oInfo->latitude)) {
                $oInfo->latitude = '-43.132123';
                $oInfo->longitude = '9.140625';
            }
            if (Phpfox::isModule('feed')) {
                $this->call('$Core.FeedPlace.gMyLatLng = new google.maps.LatLng("' . $oInfo->latitude . '","' . $oInfo->longitude . '");');
            }
            $this->call('setCookie("core_places_location", "' . $oInfo->latitude . ',' . $oInfo->longitude . '");');
            $this->call('$($Core.FeedPlace.matchIdIndex("#hdn_location_name") + "," + $Core.FeedPlace.matchIdIndex("#val_location_name")).val("' . $oInfo->city . ', ' . $oInfo->country_name . '"); ');
            if (Phpfox::isModule('feed')) {
                $this->call('$Core.FeedPlace.getNewLocations();');
                $this->call('$Core.FeedPlace.createMap();');
            }
        }

        if ($this->get('saveLocation')) {
            Phpfox::getService('user.process')->saveMyLatLng(['latitude' => $oInfo->latitude, 'longitude' => $oInfo->longitude]);
        }
    }

    /* Called from main.js loads the blocks from an ajax call after the controller has loaded */
    public function loadDelayedBlocks()
    {
        // These are blocks intentionally delayed
        $aLocations = explode(',', $this->get('locations'));
        $oModule = Phpfox_Module::instance();
        $aParams = $this->get('params');
        define('PHPFOX_LOADING_DELAYED', true);
        if ($this->get('locations') != null && count($aLocations) > 0) {
            $oModule->loadBlocks();
            if ($oModule->getFullControllerName() == 'core.index' && Phpfox::isUser()) {
                $oModule->setController('core.index-member');
            }
            foreach ($aLocations as $iLocation) {
                $aBlocks = $oModule->getModuleBlocks($iLocation, true);
                foreach ($aBlocks as $sBlock) {
                    Phpfox::getBlock($sBlock);
                    $this->html('#delayed_block_' . $iLocation, $this->getContent(false));
                }
            }
        } else if ($this->get('loadContent') != null) // Then we are loading the 'content'
        {
            $sController = $this->get('loadContent');
            if (!empty($aParams)) {
                $oRequest = Phpfox_Request::instance();
                foreach ($aParams as $sIndex => $sKey) {
                    $oRequest->set($sIndex, $sKey);
                }
            }
            $oModule->getComponent($sController, $aParams, 'controller');

            $this->hide('#delayed_block_image');
            $this->html('#delayed_block', $this->getContent(false));
            $this->show('#delayed_block');
        } else if ($this->get('delayedTemplates') != null) {

            $aTemplates = $this->get('delayedTemplates');

            foreach ($aTemplates as $sIndex => $sKey) {
                $aTemplate = explode('=', $sKey);
                $sTemplate = Phpfox_Template::instance()->getBuiltFile($aTemplate[1]);
                $this->html('#' . $aTemplate[1], $sTemplate);
            }

        }
        $this->call('$Behavior.loadDelayedBlocks = function(){}; $Core.loadInit();');
    }

    /** Called from rewrite.js in the AdminCP -> SEO -> Rewrite URL */
    public function updateRewrites()
    {
        Phpfox::isAdmin(true);
        $aRewrites = json_decode($this->get('aRewrites'), true);

        Phpfox::getService('core.redirect.process')->updateRewrites($aRewrites);

        if (Phpfox_Error::isPassed()) {
            $this->call('$Core.AdminCP.Rewrite.saveSuccessful();');
            $this->softNotice('Saved Successfully');
        } else {
            $this->call('$("#processing").hide();');
        }

    }

    public function removeRewrite()
    {
        Phpfox::isAdmin(true);

        Phpfox::getService('core.redirect.process')->removeRewrite($this->get('id'));
    }

    /**
     * @return bool
     */
    public function removeTempFile()
    {
        $iId = $this->get('id', 0);
        if (empty($iId)) {
            return false;
        }

        return Phpfox::getService('core.temp-file')->delete($iId, true);
    }

    public function searchItemsMapView()
    {
        $sType = $this->get('type');
        $aBounds = $this->get('bounds');
        $aQuery = $this->getAll();
        if (!Phpfox::hasCallback($sType, 'getMapViewItems') || !Phpfox::hasCallback($sType, 'getMapViewParams')) {
            return false;
        }
        $aSearchParams = Phpfox::callback($sType . '.getMapViewParams', $aQuery);
        if (!isset($aSearchParams['search_params'], $aSearchParams['card_view'], $aSearchParams['browse_params'])) {
            return Phpfox_Error::set(_p('invalid_data'));
        }
        $oSearch = Phpfox_Search::instance();
        $oSearch->set($aSearchParams['search_params']);
        Phpfox::callback($sType . '.getMapViewItems', $aQuery);
        $oSearch->setContinueSearch(true);
        $oSearch->setABounds($aBounds);
        $oSearch->browse()->params($aSearchParams['browse_params'])->setPagingMode(isset($aSearchParams['pagination_style']) ? $aSearchParams['pagination_style'] : 'pagination')->execute();
        $aItems = $oSearch->browse()->getRows();
        $sLatField = isset($aSearchParams['search_params']['location_field']['latitude_field']) ? $aSearchParams['search_params']['location_field']['latitude_field'] : 'location_lat';
        $sLngField = isset($aSearchParams['search_params']['location_field']['longitude_field']) ? $aSearchParams['search_params']['location_field']['longitude_field'] : 'location_lng';

        $this->call('$Core.Gmap.setMapMarker(\'' . (!empty($aSearchParams['map_marker']['icon']) ? $aSearchParams['map_marker']['icon'] : Phpfox::getLib('image.helper')->display(['theme' => 'misc/map_ico.png', 'return_url' => true])) . '\',\'' . (!empty($aSearchParams['map_marker']['hover_icon']) ? $aSearchParams['map_marker']['hover_icon'] : Phpfox::getLib('image.helper')->display(['theme' => 'misc/map_ico_hover.png', 'return_url' => true])) . '\');');
        if (count($aItems)) {
            $aMarkers = [];
            $aCurrentIds = [];
            foreach ($aItems as $sKey => $aItem) {
                if (Phpfox::hasCallback($sType, 'convertItemOnMap')) {
                    $aItems[$sKey] = Phpfox::callback($sType . '.convertItemOnMap', $aItem);
                }
                $aItems[$sKey]['id'] = isset($aSearchParams['browse_params']['field'], $aItem[$aSearchParams['browse_params']['field']]) ? $aItem[$aSearchParams['browse_params']['field']] : $sKey;
                $aItems[$sKey]['latitude'] = $aItem[$sLatField];
                $aItems[$sKey]['longitude'] = $aItem[$sLngField];
                $aCurrentIds[] = $aItem['id'];

                $title = isset($aItems[$sKey]['item_title']) ? $aItems[$sKey]['item_title'] : '';
                if (!empty($aItems[$sKey]['item_info_window_title'])) {
                    $title = $aItems[$sKey]['item_info_window_title'];
                }
                $aItem = $aItems[$sKey];
                $aItem['item_title'] = $title;

                Phpfox::getLib('template')->assign(['aItem' => $aItem]);
                if (isset($aSearchParams['info_window']) && $aSearchParams['info_window']) {
                    Phpfox::getLib('template')->getBuiltFile($aSearchParams['info_window']);
                } else {
                    Phpfox::getLib('template')->getBuiltFile('core.block.gmap-info-window');
                }
                $aMarkers[] = [
                    'id' => $aItems[$sKey]['id'],
                    'lat' => $aItems[$sKey]['latitude'],
                    'lng' => $aItems[$sKey]['longitude'],
                    'title' => html_entity_decode($title, ENT_QUOTES),
                    'info_window' => $this->getContent(false)
                ];
                $this->call('$Core.Gmap.oShowingItems = ' . json_encode($aCurrentIds) . ';');
                $this->call('$Core.Gmap.setMarkersOnMap(' . json_encode($aMarkers) . ');');
            }
        } else {
            $this->call('$Core.Gmap.clearAllMarkers();');
        }
        Phpfox_Pager::instance()->set([
            'page' => $oSearch->getPage(),
            'size' => $oSearch->getDisplay(),
            'count' => $oSearch->browse()->getCount(),
            'paging_mode' => $oSearch->browse()->getPagingMode(),
            'params' => [
                'pagination_walk' => 1
            ]
        ]);
        Phpfox::getComponent('core.gmap-card-views', [
            'sType' => $sType,
            'aItems' => $aItems,
            'aParams' => $aSearchParams['card_view'],
            'sPagingMode' => $oSearch->browse()->getPagingMode(),
            'sAjax' => 'core.searchItemsMapView',
            'aSearchParams' => $aSearchParams['search_params']
        ]);
        $this->call('$(\'#js-core-map-listing-container\').html(\'' . $this->getContent() . '\').removeClass(\'hide\');');
        $this->call('$Core.Gmap.loadCssStyle();');
        return true;
    }

    /**
     * Show popup to manage schedule items
     */
    public function manageScheduleItems()
    {
        $this->error(false);
        Phpfox::getBlock('core.schedule-items');
    }

    public function editScheduleItem()
    {
        Phpfox::isUser(true);
        $iScheduleId = $this->get('id');
        $aScheduleItem = Phpfox::getService('core.schedule')->getScheduleItem((int)$iScheduleId);
        if (empty($aScheduleItem)) {
            return Phpfox_Error::display(_p('this_scheduled_item_not_exist'));
        }
        Phpfox::getBlock('core.edit-schedule', ['id' => $iScheduleId]);
        $params = [
            'type' => $aScheduleItem['item_type']
        ];
        $this->call('<script type="text/javascript">$Core.activityFeedProcess(false);</script>');
        $this->call('<script type="text/javascript">$Core.editFeedStatus(' . json_encode($params) . '); if (typeof sCurrentFeedType !== "undefined") { sCurrentFeedType = "' . $aScheduleItem['item_type'] . '"; } else { var sCurrentFeedType = "' .$aScheduleItem['item_type'] . '"; }</script>');
        return true;
    }

    public function updateSchedule()
    {
        $aVals = (array)$this->get('val');
        if (Phpfox::getService('core.schedule')->updateScheduleItem($aVals)) {
            $aSchedules = Phpfox::getService('core.schedule')->getScheduleItems($iCount, 1, 1, null, ['item.schedule_id' => $aVals['schedule_id']]);
            if (!empty($aSchedules)) {
                Phpfox::getLib('template')
                    ->assign([
                        'aScheduleItem' => $aSchedules[0]
                    ])
                    ->getTemplate('core.block.schedule-item');
                $this->call('$("#js_schedule_item_holder_' . $aVals['schedule_id'] . '").html("' . $this->getContent() . '");tb_remove();js_box_remove("#js_edit_schedule_form", true);setTimeout(function(){$Core.resetActivityFeedForm();$Core.loadInit();}, 500);');
                if ($aSchedules[0]['item_type'] == 'photo') {
                    $this->call('if (typeof $Core.dropzone.instance["photo_schedule"] !== "undefined") { $Core.dropzone.instance["photo_schedule"] = null; $Core.reloadValidation.reset(); };');
                    if (($iRemainingUploadingPhotos = Phpfox::getService('photo')->getTotalPhotosPerUploading(null, true)) !== true) {
                        $this->call('$Core.Photo.updateUploadingPhotoLimitationOnFeed(' . json_encode(['total' => (int)$iRemainingUploadingPhotos, 'message' => _p('maximum_number_of_images_you_can_upload_each_time_is') . ' ' . $iRemainingUploadingPhotos]) . ');');
                    }
                }
                return true;
            }
        } else {
            $this->call('$Core.activityFeedProcess(false);');
        }
        return false;
    }

    public function deleteScheduleItem()
    {
        Phpfox::isUser(true);
        $iScheduleId = (int)$this->get('id');
        if (!$iScheduleId) {
            return false;
        }
        Phpfox::getService('core.schedule')->deleteScheduleItem($iScheduleId);
        $this->call('$Core.FeedSchedule.deleteScheduleItem("' . $iScheduleId . '", null, true);');
        return true;
    }

    public function sendNowScheduleItem()
    {
        Phpfox::isUser(true);
        $iScheduleId = (int)$this->get('id');
        if (!$iScheduleId) {
            return false;
        }
        if (Phpfox::getService('core.schedule')->sendNowScheduleItem($iScheduleId)) {
            $this->alert(_p('scheduled_post_sent_successfully'), null, 300, 150, true);
        } else {
            $this->alert(_p('this_scheduled_item_not_exist'), _p('error'), 300, 150, true);
        }
        $this->call('$Core.FeedSchedule.sendNowScheduleItem("' . $iScheduleId . '", null, true);');
        return true;
    }

    public function validateScheduleTime()
    {
        $aVals = [
            'schedule_hour' => $this->get('hour'),
            'schedule_minute' => $this->get('minute'),
            'schedule_month' => $this->get('month'),
            'schedule_day' => $this->get('day'),
            'schedule_year' => $this->get('year')
        ];
        if (!Phpfox::getService('core.schedule')->validateScheduleTime($aVals, false)) {
            echo json_encode([
                'error' => true
            ]);
        } else {
            echo json_encode([
                'error' => false
            ]);
        }
    }
}