<?php
defined('PHPFOX') or exit('NO DICE!');


class Admincp_Component_Controller_Setting_License extends Phpfox_Component
{
    public function process()
    {
    	$sLicenseFile = PHPFOX_DIR_SETTINGS . 'license.sett.php';
        //Check license file is write able
        if (!is_writable($sLicenseFile)) {
            $this->template()->assign([
                'bCanWrite' => false,
            ]);
        } else {
            $oValid = Phpfox_Validator::instance()->set([
                'sFormName' => 'js_form',
                'aParams'   => [
                    'license_id'  => _p('Provide a license ID.'),
                    'license_key' => _p('Provide a license key.'),
                ],
            ]);

            if (($aVals = $this->request()->getArray('val'))) {
                if ($oValid->isValid($aVals)) {
                    if ($aVals['license_id'] == 'techie'
                        && $aVals['license_key'] == 'techie'
                    ) {
                        $response = new stdClass();
                        $response->valid = true;
                    } else {
                        try {
                            $Home = new Core\Home($aVals['license_id'], $aVals['license_key']);
                            $response = $Home->verify([
                                'url' => $this->getHostPath(),
                            ]);
                        } catch (\Exception $e) {
                            $response = new stdClass();
                            $response->error = $e->getMessage();
                        }
                    }
                    // Connect to phpFox and verify the license
                    if (isset($response->valid)) {
                        $aLicenseInfo = $response->license;
                        $data = "<?php if (!defined('PHPFOX_LICENSE_ID')) {define('PHPFOX_LICENSE_ID', '" . $aVals['license_id'] . "');} if (!defined('PHPFOX_LICENSE_KEY')) {define('PHPFOX_LICENSE_KEY', '" . $aVals['license_key'] . "');}";

                        if (!empty($aLicenseInfo)
                            && isset($aLicenseInfo->package_id)
                        ) {
                            $package_id = $aLicenseInfo->package_id;
                            $data .= "\n\nif (!defined('PHPFOX_PACKAGE_ID')) {define('PHPFOX_PACKAGE_ID', {$package_id});}";
                        }

                        file_put_contents($sLicenseFile, $data);
                        Phpfox::addMessage(_p("Successfully added your license key"));
                        $this->url()->send('admincp');
                    } else {
                        if (!is_object($response)) {
                            $info = $response;
                            $response = new stdClass();
                            $response->error = $info;
                        }
                        Phpfox_Error::reset();
                        Phpfox_Error::set($response->error);
                        $this->template()->assign([
                            'sError' => $response->error,
                        ]);
                    }
                }
            }else{
                $aVals = [
                    'license_id'=> defined('PHPFOX_LICENSE_ID')?PHPFOX_LICENSE_ID: 'techie',
                    'license_key'=>defined('PHPFOX_LICENSE_KEY')?PHPFOX_LICENSE_KEY: 'techie',
                ];
            }

            $this->template()->clearBreadCrumb()
                ->setBreadCrumb(_p('license_key'))
                ->setActiveMenu('admincp.settings.license')
                ->assign([
                	'useEnvFile'=> Phpfox::hasEnvParam('core.license_id'),
                    'aVals'=>$aVals,
                    'bCanWrite'  => true,
                    'sCreateJs'  => $oValid->createJS(),
                    'sGetJsForm' => $oValid->getJsForm(),
                    'bHasCurl'   => (function_exists('curl_init') ? true
                        : false),
                ]);
        }
    }

    private function getHostPath()
    {
        $protocol = 'http';
        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
            $protocol = 'https';
        }
        $parts = explode('index.php', $_SERVER['PHP_SELF']);
        $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $parts[0];
        return $url;
    }

    public function clean()
    {
        (($sPlugin
            = Phpfox_Plugin::get('admincp.component_controller_setting_license_clean'))
            ? eval($sPlugin) : false);
    }
}