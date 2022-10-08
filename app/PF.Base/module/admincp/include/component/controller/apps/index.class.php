<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Admincp_Component_Controller_Apps_index
 */
class Admincp_Component_Controller_Apps_index extends Phpfox_Component
{

    /**
     * @param string $sUpgradeAppId
     * @param string $sStoreId
     * @param bool $bIsTheme
     * @return void
     */
    public function upgradeApp($sUpgradeAppId, $sStoreId, $bIsTheme = false)
    {
        $redirectUrl = '';
        $sendData =  $bIsTheme ? ['themes'=> [$sUpgradeAppId]] : ['apps'=> [$sUpgradeAppId]];
        $Home = new Core\Home(PHPFOX_LICENSE_ID, PHPFOX_LICENSE_KEY);
        $response = $Home->products(['products' => $sendData]);
        $admincp = $Home->admincp(['return' => $this->url()->makeUrl('admincp.app.add')]);

        if($sStoreId) {
            $store = Phpfox::getService('admincp.apps')->getAppInformation('', $sStoreId, true);
            if (isset($store['url'])) {
                if (isset($admincp->token) && $admincp->token) {
                    $redirectUrl = $store['url'] . '/installing?iframe-mode=' . $admincp->token;
                } else {
                    $redirectUrl = $store['url'];
                }
            }
        } else {
            if (isset($response->products)) {
                if (!$bIsTheme && isset($response->products->apps) && isset($response->products->apps->{$sUpgradeAppId})) {
                    $app = $response->products->apps->{$sUpgradeAppId};
                } elseif (isset($response->products->themes) && isset($response->products->themes->{$sUpgradeAppId})) {
                    $app = $response->products->themes->{$sUpgradeAppId};
                }
            }
            if (isset($app->link)) {
                if (isset($admincp->token) && $admincp->token) {
                    $redirectUrl = $app->link . '/installing?iframe-mode=' . $admincp->token;
                } else {
                    $redirectUrl = $app->link;
                }
            }
        }

        if($redirectUrl) {
            $this->url()->send($redirectUrl);
        }
    }
    public function process()
    {
        if(isset($_REQUEST['rename_on_upgrade']) && !empty($_REQUEST['apps_dir']) && !empty($_REQUEST['apps_id'])){
            $url =  (new \Core\Installation\Manager())->callRunInstallForApp($_REQUEST['apps_id'], $_REQUEST['apps_dir'],$_REQUEST['is_upgrade']);
            header('location: '. $url);exit;
        }

        if(null != ($this->request()->get('upgrade_app'))){
            $this->upgradeApp($this->request()->get('app_id'), $this->request()->get('store_id'), $this->request()->get('is_theme'));
        }

        if (($token = $this->request()->get('m9token'))) {
            $response = (new Core\Home(PHPFOX_LICENSE_ID, PHPFOX_LICENSE_KEY))->token(['token' => $token]);
            if ($response->token) {
                $file = PHPFOX_DIR_SETTINGS . 'license.sett.php';
                $content = file_get_contents($file);
                $content = preg_replace('!define\(\'PHPFOX_LICENSE_ID\', \'(.*?)\'\);!s', 'define(\'PHPFOX_LICENSE_ID\', \'techie_' . $this->request()->get('m9id') . '\');', $content);
                $content = preg_replace('!define\(\'PHPFOX_LICENSE_KEY\', \'(.*?)\'\);!s', 'define(\'PHPFOX_LICENSE_KEY\', \'techie_' . $this->request()->get('m9key') . '\');', $content);

                file_put_contents($file, $content);

                $this->template()->assign('vendorCreated', true);
            }
        }

        $menu = [];
        if (Phpfox::isTechie()) {
            $menu[_p('Import Module')] = [
                'url' => $this->url()->makeUrl('admincp.upload'),
                'class' => 'popup light'
            ];

            $menu[_p('New App')] = [
                'url' => $this->url()->makeUrl('admincp.app.add'),
                'class' => 'popup light'
            ];
        }

        $this->template()->setActionMenu($menu);

        $warnings = [];
        if(!class_exists('ZipArchive')){
            $warnings[] = '<a href="http://php.net/manual/en/class.ziparchive.php" target="_blank">PHP ZipArchive</a> is required to install/update apps. <a href="http://support.phpfox.com/getting-started/requirements/" target="_blank">See phpFox requirements.</a>';
        }

        $this->template()->setSectionTitle(_p('apps'))
            ->setTitle(_p('Manage Apps'))
            ->setBreadCrumb(_p('Manage Apps'))
            ->setActiveMenu('admincp.apps')
            ->assign([
                'bShowClearCache' => true,
                'warning' => implode('<br />', $warnings)
            ]);
    }
}