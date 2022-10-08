<?php
defined('PHPFOX') or exit('NO DICE!');

class Admincp_Service_Apps_Apps extends Phpfox_Service
{
    /**
     * @var array of all default apps
     */
    private $_aDefaultApps = [
            'ad'                     => 'ad',
            'admincp'                => 'admincp',
            'announcement'           => 'announcement',
            'api'                    => 'api',
            'attachment'             => 'attachment',
            'ban'                    => 'ban',
            'blog'                   => 'blog',
            'captcha'                => 'captcha',
            'comment'                => 'comment',
            'contact'                => 'contact',
            'core'                   => 'core',
            'custom'                 => 'custom',
            'egift'                  => 'egift',
            'error'                  => 'error',
            'event'                  => 'event',
            'feed'                   => 'feed',
            'forum'                  => 'forum',
            'friend'                 => 'friend',
            'invite'                 => 'invite',
            'language'               => 'language',
            'like'                   => 'like',
            'link'                   => 'link',
            'log'                    => 'log',
            'mail'                   => 'mail',
            'marketplace'            => 'marketplace',
            'music'                  => 'music',
            'newsletter'             => 'newsletter',
            'notification'           => 'notification',
            'page'                   => 'page',
            'pages'                  => 'pages',
            'photo'                  => 'photo',
            'poke'                   => 'poke',
            'poll'                   => 'poll',
            'privacy'                => 'privacy',
            'profile'                => 'profile',
            'quiz'                   => 'quiz',
            'report'                 => 'report',
            'request'                => 'request',
            'rss'                    => 'rss',
            'search'                 => 'search',
            'share'                  => 'share',
            'subscribe'              => 'subscribe',
            'tag'                    => 'tag',
            'theme'                  => 'theme',
            'track'                  => 'track',
            'user'                   => 'user',
            'PHPfox_CDN_Service'     => 'phpFox CDN Service',
            'PHPfox_Facebook'        => 'Facebook Base',
            'PHPfox_Groups'          => 'Groups',
            'PHPfox_Twemoji_Awesome' => 'Twemoji Awesome',
        ];

    /**
     * Check an App ID/module name is default
     *
     * @param string $sName
     *
     * @return bool
     */
    public function isDefault($sName)
    {
        if (substr($sName, 0, 9) == '__module_') {
            $sName = substr_replace($sName, '', 0, 9);
        }
        if (isset($this->_aDefaultApps[$sName])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param bool $showAll
     * @return array
     */
    public function getUploadedApps($showAll = false)
    {
        $uploadedApps = [];

        $installedApps = array_reduce(db()
            ->select('a.*')
            ->from(':apps', 'a')
            ->execute('getSlaveRows'), function ($carry, $item) {
            $carry[$item['apps_id']] = $item;
            return $carry;
        }, []);


        foreach (scandir(PHPFOX_DIR_SITE_APPS) as $dir) {
            if (substr($dir, 0, 1) == '.') {
                continue;
            }
            $installFile = PHPFOX_DIR_SITE_APPS . $dir . PHPFOX_DS . 'Install.php';
            if (!file_exists($installFile)) {
                continue;
            }

            $content = file_get_contents($installFile);

            $info = [
                'apps_dir' => $dir,
            ];

            if (preg_match('/\$this->id\s*=\s*[\'|"](\w+)[\'|"]\s*;/m', $content, $match)) {
                $info['apps_id'] = $match[1];
            } else {
                continue;
            }

            $id = $info['apps_id'];

            if (preg_match('/\$this->version\s*=\s*[\'|"]([\w\.\-]+)[\'|"]\s*;/m', $content, $match)) {
                $info['version'] = $match[1];
            } else {
                continue;
            }

            if (array_key_exists($id, $installedApps)) {
                $info['can_install'] = false;
                $info['can_upgrade'] = version_compare($info['version'], $installedApps[$id]['version'], '>');
                $info['current_version'] = $installedApps[$id]['version'];
            } else {
                $info['can_install'] = true;
            }

            if (!$showAll && !$info['can_upgrade'] && !$info['can_install']) {
                continue;
            }

            $uploadedApps[] = $info;
        }

        return $uploadedApps;
    }
    public function getAppInformation($appId, $storeId, $forceRequestApi = false)
    {
        $cacheService = Phpfox::getLib('cache');
        $store = null;
        if (!empty($appId)) {
            $sHasLicenseCacheId = $cacheService->set('admincp_apps_hasLicense');
            $appHasLicense = $cacheService->get($sHasLicenseCacheId, 1440);
            if ($appHasLicense !== false && isset($appHasLicense[$appId])) {
                $store = $appHasLicense[$appId];
            }
        }
        if ($storeId && (!$store || $forceRequestApi)) {
            $storeAppId = $cacheService->set('store_app_' . $storeId);
            //Cache in 6 hours
            $store = $cacheService->get($storeAppId, 360);
            if ($store === false) {
                if (@get_headers(Core\Home::store())) {
                    $store = json_decode(@fox_get_contents(Core\Home::store() . 'product/' . $storeId . '/view.json'), true);
                }
                $cacheService->save($storeAppId, $store);
                $cacheService->group('admincp', $storeAppId);
            }
        }
        if ($store && !isset($store['url']) && isset($store['link'])) {
            $store['url'] = $store['link'];
        }
        return $store;
    }
}