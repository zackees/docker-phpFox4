<?php
$installer = new Core\App\Installer();
$installer->onInstall(function () use ($installer) {
    (new \Apps\PHPfox_Core\Installation\Version\v480())->process();
    (new \Apps\PHPfox_Core\Installation\Version\v481())->process();
    (new \Apps\PHPfox_Core\Installation\Version\v483())->process();
});