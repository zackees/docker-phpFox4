<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Admincp_Component_Controller_Apps_Ajax
 */
class Admincp_Component_Controller_Apps_Ajax extends Phpfox_Component
{
    public function process()
    {
        $allApps = Phpfox::getCoreApp()->getForManage();

        if (!defined('PHPFOX_TRIAL_MODE')) {
            $appIdList =  array_map(function($item) {
                return ($item->is_phpfox_default || !$item->is_active) ? null : $item->id;
            }, $allApps);
            foreach ($appIdList as $keyApp => $value) {
                if (!isset($value) || empty($value)) {
                    unset($appIdList[$keyApp]);
                }
            }

            $cacheService = Phpfox::getLib('cache');
            $sHasLicenseCacheId = $cacheService->set('admincp_apps_hasLicense');
            $appHasLicense = $cacheService->get($sHasLicenseCacheId, 1440);
            if(count($appIdList) && $appHasLicense === false) {
                $sendData =  ['apps'=> $appIdList];
                if (@get_headers(Core\Home::store())) {
                    $Home = new Core\Home(PHPFOX_LICENSE_ID, PHPFOX_LICENSE_KEY);
                    $response = $Home->products(['products' => $sendData]);
                    if (isset($response->products->apps)) {
                        $responseApps = $response->products->apps;
                        foreach ($responseApps as $key => $app) {
                            $appHasLicense[$key] = (array)$app;
                        }
                        $cacheService->save($sHasLicenseCacheId, $appHasLicense);
                        $cacheService->group('admincp', $sHasLicenseCacheId);
                    }
                }
            }
            $sNoLicenseCacheId = $cacheService->set('admincp_apps_noLicense');
            $appNoLicense = $cacheService->get($sNoLicenseCacheId, 1440);
            $hasUpdateCache = false;

            foreach ($allApps as $index=>$app) {
                $id =  $app->id;
                if (!$app->is_active) {
                    continue;
                }
                if(!empty($appHasLicense) && isset($appHasLicense[$id]) && isset($appHasLicense[$id]['version'])){
                    $app->latest_version = $appHasLicense[$id]['version'];
                    if (version_compare($app->version, $app->latest_version, '<') && isset($appHasLicense[$id]['link'])) {
                        $allApps[$index]->have_new_version =  $this->url()->makeUrl('admincp.apps',['upgrade_app' => true, 'app_id' => $id]);
                    }
                    else {
                        $allApps[$index]->have_new_version = false;
                    }
                } elseif (!empty($app->store_id)) {
                    if (!isset($appNoLicense[$app->store_id])) {
                        if (@get_headers(Core\Home::store())) {
                            $store = json_decode(@fox_get_contents(Core\Home::store() . 'product/' . $app->store_id . '/view.json'), true);
                            $appNoLicense[$app->store_id] = $store;
                            $hasUpdateCache = true;
                        }
                    } else {
                        $store = $appNoLicense[$app->store_id];
                    }
                    if (!empty($store['id']) && !empty($store['version'])) {
                        $allApps[$index]->latest_version =  $store['version'];
                        $allApps[$index]->have_new_version =  false;
                        if (version_compare($app->version, $store['version'], '<')) {
                            $allApps[$index]->have_new_version = $this->url()->makeUrl('admincp.apps',['upgrade_app' => true, 'app_id'=>$id, 'store_id' => $store['id']]);
                        }

                    } else {
                        $app->latest_version =  _p('n_a');
                        $allApps[$index]->have_new_version = false;
                    }
                } else {
                    $app->latest_version =  _p('n_a');
                    $allApps[$index]->have_new_version = false;
                }
            }
            if($hasUpdateCache) {
                $cacheService->save($sNoLicenseCacheId, $appNoLicense);
                $cacheService->group('admincp', $sNoLicenseCacheId);
            }
        }

        (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_controller_apps_end')) ? eval($sPlugin) : false);

		$allApps =  array_filter($allApps, function($app){
			return $app && !!$app->id;
		});

        echo $this->template()
            ->assign([
                'bShowClearCache' => true,
                'apps' => $allApps,
                'bIsTechie' => Phpfox::isTechie()
            ])
            ->getTemplate('admincp.controller.apps.ajax', true);
        exit;
    }
}