<?php

event('app_settings', function ($settings) {
    if (!isset($settings['pf_core_cache_driver'])) {
        return;
    }
    $redis_file = PHPFOX_DIR_SETTINGS . 'redis.sett.php';
    if (isset($settings['pf_core_redis']) && $settings['pf_core_redis'] == '1' && !empty($settings['pf_core_redis_host'])) {
        file_put_contents($redis_file,
            "<?php\nreturn ['host' => '{$settings['pf_core_redis_host']}', 'enabled' => 1];\n");
    } else {
        if (isset($settings['pf_core_redis']) && !$settings['pf_core_redis'] && isset($settings['pf_core_redis_host'])) {
            file_put_contents($redis_file,
                "<?php\nreturn ['host' => '{$settings['pf_core_redis_host']}', 'enabled' => 0];\n");
        }
    }

    $cache_file = PHPFOX_DIR_SETTINGS . 'cache.sett.php';
    $cache_file_data = [];
    if (isset($settings['pf_core_cache_driver'])) {
        $cache_file_data['driver'] = $settings['pf_core_cache_driver'];
        switch ($cache_file_data['driver']) {
            case 'redis':
                $cache_file_data['redis'] = [
                    'host' => $settings['pf_core_cache_redis_host'],
                    'port' => $settings['pf_core_cache_redis_port']
                ];
                if (!empty($settings['pf_core_cache_redis_password'])) {
                    $cache_file_data['redis']['password'] = $settings['pf_core_cache_redis_password'];
                }
                if (!empty($settings['pf_core_cache_redis_database'])) {
                    $cache_file_data['redis']['database'] = $settings['pf_core_cache_redis_database'];
                }
                file_put_contents($cache_file, "<?php\n return " . var_export($cache_file_data, true) . ";\n");
                break;
            case 'memcached':
                $cache_file_data['memcached'] = [
                    [$settings['pf_core_cache_memcached_host'], $settings['pf_core_cache_memcached_port'], 1]
                ];
                file_put_contents($cache_file, "<?php\n return " . var_export($cache_file_data, true) . ";\n");
                break;
            default:
                if (file_exists($cache_file)) {
                    @unlink($cache_file);
                }

        }
    }
});

if (!function_exists('materialParseIcon') && !defined('PHPFOX_INSTALLER')) {
    function materialParseIcon($sKey, $sDefault = null)
    {
        static $aIconParseList = [
            'attachment' => 'paperclip-alt',
            'blog' => 'compose-alt',
            'groups' => 'user-man-three-o',
            'marketplace' => 'store-o',
            'music' => 'music-note-o',
            'pages' => 'flag-waving-o',
            'photo' => 'photos-alt-o',
            'poll' => 'bar-chart2',
            'quiz' => 'question-circle-o',
            'todo' => 'paragraph-plus',
            'activity-statistics' => 'info-circle-alt-o',
            'event' => 'calendar-check-o',
            'comment' => 'comment-square-o',
            'invite' => 'paperplane-alt-o',
            'forum' => 'comments-o',
            'rss' => 'rss-o',
            'default' => 'box-o',
            'user' => 'user1-three-o',
            'home' => 'alignleft',
            'members' => 'user1-three-o',
            'info' => 'info-circle-alt-o',
            'v' => 'video',
            'video' => 'video',
            'all_results' => 'search-o'
        ];

        if (Phpfox::hasCallback($sKey, 'getMaterialParseIcon')) {
            return Phpfox::callback($sKey . '.getMaterialParseIcon');
        }

        if (empty($aIconParseList[$sKey])) {
            return $sDefault ? $sDefault : 'ico ico-' . $aIconParseList['default'];
        }

        return 'ico ico-' . $aIconParseList[$sKey];
    }
}

if (!function_exists('materialParseMobileIcon') && !defined('PHPFOX_INSTALLER')) {
    function materialParseMobileIcon($sIcon)
    {
        static $aIconParseList = [
            'pencil-square' => 'compose-alt',
            'users' => 'user-man-three-o',
            'photo' => 'photos-alt-o',
            'comments' => 'comments-o',
            'bar-chart' => 'bar-chart2',
            'puzzle-piece' => 'question-circle-o',
            'calendar' => 'calendar-check-o',
            'music' => 'music-note-o',
            'usd' => 'store-o',
            'video-camera' => 'video',
            'default' => 'box-o'
        ];

        if (is_null($sIcon)) {
            return 'ico ico-' . $aIconParseList['default'];
        } elseif (strpos($sIcon, 'ico-') !== false) {
            if (strpos($sIcon, 'ico ico-') === false) {
                return "ico $sIcon";
            } else {
                return $sIcon;
            }
        } elseif (empty($aIconParseList[$sIcon])) {
            return 'fa fa-' . $sIcon;
        }

        return 'ico ico-' . $aIconParseList[$sIcon];
    }
}

route('dropzone/parse-thumbnail', function () {
    $sType = 'photo';
    $aParams = [
        'list_type' => [],
        'max_size' => null,
        'upload_dir' => Phpfox::getParam('core.dir_pic'),
        'thumbnail_sizes' => [],
        'user_id' => Phpfox::getUserId(),
        'type' => $sType,
        'param_name' => 'file',
        'field_name' => 'temp_file'
    ];
    if (!empty(request()->get('force'))) {
        $aParams['force_upload'] = true;
    }
    $aParams = array_merge($aParams, Phpfox::callback($sType . '.getUploadParams'));
    $aFile = Phpfox::getService('user.file')->upload($aParams['param_name'], $aParams);
    if (!$aFile) {
        echo json_encode([
            'type' => $sType,
            'error' => _p('upload_fail_please_try_again_later'),
            'field_name' => $aParams['field_name']
        ]);
        exit;
    }
    $iServerId = Phpfox_Request::instance()->getServer('PHPFOX_SERVER_ID');
    $iId = phpFox::getService('core.temp-file')->add([
        'type' => $sType,
        'size' => $aFile['size'],
        'path' => $aFile['name'],
        'server_id' => $iServerId
    ]);
    echo json_encode([
        'file' => $iId,
        'type' => $sType,
        'field_name' => $aParams['field_name'],
        'path' => Phpfox::getLib('image.helper')->display([
            'file' => $aFile['name'],
            'path' => 'photo.url_photo',
            'server_id' => $iServerId,
            'return_url' => true
        ])
    ]);
    exit;
});

if (!defined('PHPFOX_IS_MOBILE_API_CALL') && !defined('PHPFOX_INSTALLER')
    && auth()->isLoggedIn() && ($cached = storage()->get('google_force_email_' . user()->id)
    && request()->segment(1) != 'user' && request()->segment(2) != 'setting' && request()->segment(1) != 'logout')) {
    if (substr(user()->email, -7) == '@google') {
        Phpfox::addMessage(_p('please_provide_us_with_an_active_email_to_associate_with_your_account'));
        url()->send('/user/setting');
    } else {
        storage()->del('google_force_email_' . user()->id);
    }
}