<?php

if (isset($_REQUEST['license_id'])) {
    $sLicensePath = realpath(__DIR__ . '/../file/settings/license.sett.php');
    try {
        if (!file_exists($sLicensePath)) {
            echo 'PHPfox does not seem to be installed. Odd...';
            exit();
        }
        require($sLicensePath);

        if (!defined('PHPFOX_LICENSE_ID') || $_REQUEST['license_id'] != PHPFOX_LICENSE_ID) {
            echo 'License ID does not match. ';
            exit();
        }

        if (!defined('PHPFOX_LICENSE_KEY') || $_REQUEST['license_key'] != PHPFOX_LICENSE_KEY) {
            echo 'License Key does not match. ';
            exit();
        }

        /**
         * No SESSIONS
         */
        define('PHPFOX_NO_SESSION', true);

        /**
         * Do not set user sessions
         */
        define('PHPFOX_NO_USER_SESSION', true);

        /**
         * Script upgrade
         */
        define('PHPFOX_SCRIPT_UPGRADE', true);

        /**
         * Do not run
         */
        define('PHPFOX_NO_RUN', true);

        $action = isset($_REQUEST['method']) ? $_REQUEST['method'] : '';

        // Require all phpfox methods
        require (__DIR__ . '/../start.php');
        require(__DIR__ . '/../install/include/installer.class.php');
        $url = $_SERVER["REQUEST_URI"];
        switch ($action) {
            case 'upgrade-phpfox':
                if (version_compare(Phpfox::getVersion(), Phpfox::getCurrentVersion(), '>')) {
                    echo 'Upgrade phpFox version. ';
                    //Upgrade phpfox
                    $installer = (new Phpfox_Installer());
                    //Prepare DB
                    $installer->_prepare_database();
                    echo 'Prepared database. ';
                    //Update language
                    $installer->_language();
                    echo 'Updated language. ';
                    //Update versions
                    $currentVersion = $installer->_getCurrentVersion();
                    foreach ($installer->getVersionList() as $version) {
                        if (version_compare($version, $currentVersion) > 0) {
                            $installer->getOReq()->set('version', $version);
                            $installer->_upgrade_phpfox_version();
                        }
                    }
                    echo 'Updated versions.';
                    //Update core module
                    $installer->getOReq()->set('module', 'core');
                    $installer->_prepare_database();
                    //Update setting
                    $installer->_update_settings();
                    //Update db
                    $installer->_update_db();
                    echo 'Updated settings. ';
                    //Rebuild theme
                    $installer->_rebuild_bootstrap();
                    if (Phpfox::getLib('assets')->getDefaultStorageId()) {
                        //Transfer asset storage
                        echo 'Transferred assets storage. ';
                        $installer->_transfer_asset_files(true);
                    }
                    echo 'Rebuilt theme.. ';
                    $installer->_all_done();
                    echo 'Done!';
                } else {
                    echo 'Your site already up-to-date.';
                }
                break;
            case 'upgrade-app':
                echo 'Upgrade installed apps. ';
                //Upgrade apps
                $uploadedApp = Phpfox::getService('admincp.apps')->getUploadedApps();
                $upgraded = [];
                foreach ($uploadedApp as $app) {
                    if (empty($app['can_upgrade'])) {
                        continue;
                    }
                    Phpfox::getService('admincp.process')->processUploadedApp('upgrade', 'app', $app['apps_id'], $app['apps_dir']);
                    echo 'Upgraded: ' . $app['apps_id'] . '. ';
                    $upgraded = $app['apps_id'];
                }
                if (!count($upgraded)) {
                    echo 'No update found!';
                } else {
                    echo 'Done!';
                }
                break;
            case 'chatplus-setting':
                //Sync chatplus setting
                echo 'Sync Chatplus Settings. ';
                if (!Phpfox::isAppActive('P_ChatPlus')) {
                    echo 'Chatplus doesn\'t available on your site.';
                } else {
                    $sAppId = 'P_ChatPlus';
                    $App = \Core\Lib::appInit($sAppId);
                    Phpfox::getService('chatplus.permissions')->importFromApp($App);
                    if (Phpfox::getService('chatplus')->syncChatPlusServerSettings(true)) {
                        echo 'Done!';
                    } else {
                        echo 'Failed! Please try it later.';
                    }
                }
                break;
            case 'chatplus-user':
                //Import user to chatplus
                echo 'Import User to Chatplus. ';
                if (!Phpfox::isAppActive('P_ChatPlus')) {
                    echo 'Chatplus doesn\'t available on your site.';
                } else {
                    if (Phpfox::getService('chatplus')->exportAllUsers()) {
                        echo 'Done! Import users job is scheduled, it will run soon.';
                    }
                }
                break;
        }
        exit();
    } catch (Exception $e) {
        echo $e->getMessage();
        exit();
    }
} else {
    echo 'Missing License ID & Key';
    exit();
}
