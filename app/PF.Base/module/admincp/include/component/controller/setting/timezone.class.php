<?php
defined('PHPFOX') or exit('NO DICE!');

class Admincp_Component_Controller_Setting_Timezone extends Phpfox_Component
{
    public function process()
    {
        $aTimeZones = Phpfox::getService('core')->getTimeZones(true);
        $disableTimezones = Phpfox::getService('core')->getDisabledTimezones();
        $sDefaultTimezone = Phpfox::getParam('core.default_time_zone_offset');
        $rtTimeZones = [];
        foreach ($aTimeZones as $key => $sTimeZone) {
            $aTimeZone = explode('/', $sTimeZone);
            if (count($aTimeZone) > 1) {
                $region = $aTimeZone[0];
                unset($aTimeZone[0]);
                $sTimeZone = implode('/', $aTimeZone);
            }
            else {
                $region = str_replace(' (GMT)' , '', $sTimeZone);
            }
            $aTimeZones[$key] = [
                'text' => $sTimeZone
            ];
            if (isset($disableTimezones[$key])) {
                $aTimeZones[$key]['disable'] = 1;
            } else {
                $aTimeZones[$key]['disable'] = 0;
                $rtTimeZones[$region]['active'] = 1;
            }

            if(PHPFOX_USE_DATE_TIME) {
                $rtTimeZones[$region]['data'][$key] = $aTimeZones[$key];
            }
        }

        if ($aVals = $this->request()->get('val')) {
            unset($aTimeZones[$sDefaultTimezone]);
            $aUncheckedTimeZoneKeys = array_diff(array_keys($aTimeZones), array_keys($aVals));
            if (Phpfox::getService('core.process')->processTimezoneSettings($aUncheckedTimeZoneKeys)) {
                $this->url()->send('current', [], _p('setting_successfully_updated'));
            }
        }

        $this->template()->setTitle(_p('time_zones'))
            ->setBreadCrumb(_p('time_zones'))
            ->setSectionTitle(_p('time_zones'))
            ->setActiveMenu('admincp.setting.time_zones')
            ->assign([
                'aTimeZones' => $rtTimeZones,
                'sDefaultTimezone' => $sDefaultTimezone
            ]);
        return null;
    }
}