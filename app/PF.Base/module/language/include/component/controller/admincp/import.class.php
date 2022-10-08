<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		phpFox LLC
 * @package 		Phpfox_Component
 * @version 		$Id: import.class.php 4961 2012-10-29 07:11:34Z phpFox LLC $
 */
class Language_Component_Controller_Admincp_Import extends Phpfox_Component
{
    private function _processUpload()
    {
        if ($this->request()->method() == 'POST') {
            if ($success = Phpfox::getService('language.process')->uploadPack()) {
                $this->url()->send('admincp.language.import', $success === true ? [] : ['dir' => base64_encode($success)], _p('language_pack_successfully_uploaded'));
            }
        }

        $this->template()
            ->setTitle(_p('upload_language_pack'))
            ->setBreadCrumb(_p('upload_language_pack'))
            ->assign([
                'bIsUploadLanguagePack' => true,
            ]);
    }

    /**
     * Controller
     */
    public function process()
    {
        if ($this->request()->get('upload')) {
            $this->_processUpload();
        } else {
            $iPage = $this->request()->getInt('page', 0);
            $bImportPhrases = false;
            $base = true;

            if ($install = $this->request()->get('install')) {
                $base = false;
                $dir = PHPFOX_DIR_INCLUDE . 'xml/language/' . $install . '/';
                Phpfox::getService('language.process')->installPackFromFolder($install, $dir);

                $this->request()->set('dir', $dir);
                if (!is_dir($dir)) {
                    Phpfox_Error::set(_p('language_package_cannot_be_found_at_dir', ['dir' => $dir]));
                }
            }

            if (($dir = $this->request()->get('dir'))) {
                $dir = ($base ? base64_decode($dir) : $dir);
                $parts = explode('language/', rtrim($dir, '/'));

                $bImportPhrases = true;

                $sCacheId = Phpfox::getLib('cache')->set('import_language_' . $parts[1]);
                if (false === ($aLanguage = Phpfox::getLib('cache')->get($sCacheId))) {
                    $aLanguage = Phpfox::getService('language.phrase')->get(['l.language_id' => $parts[1]], '', '', '', false);
                    foreach ($aLanguage as $language) {
                        $aLanguage[$language['var_name']] = $language['phrase_id'];
                    }

                    Phpfox::getLib('cache')->save($sCacheId, $aLanguage);
                }

                $bIsConfirm = $this->request()->get('is_confirm');
                $bIsOverride = $this->request()->get('is_override');

                if (!empty($aLanguage) && $iPage == 0 && empty($bIsConfirm)) {
                    $this->url()->send('admincp.language.confirm', ['dir' => base64_encode($dir), 'page' => $iPage]);
                }

                if ($iPage == 0 && !empty($bIsConfirm)) {
                    $sCacheUpdateLanguageId = Phpfox::getLib('cache')->set('import_language_update');
                    if (false === ($aLanguageUpdate = Phpfox::getLib('cache')->get($sCacheUpdateLanguageId))) {
                        Phpfox_Error::set(_p('not_a_valid_language_package_to_import'));
                    }

                    if (Phpfox::getService('language.process')->update($parts[1], $aLanguageUpdate)) {
                        Phpfox::getLib('cache')->remove('import_language_update');
                    }
                }

                $mReturn = Phpfox::getService('language.phrase.process')->installFromFolder($parts[1], $dir, $iPage, 5, $aLanguage, $bIsOverride);
                if ($mReturn === 'done') {
                    $sPhrase = _p('successfully_installed_the_language_package');

                    Phpfox::getLib('cache')->removeGroup('locale');
                    Phpfox::getLib('cache')->remove('import_language_' . $parts[1]);

                    $this->url()->send('admincp.language', null, $sPhrase);
                } else {
                    if ($mReturn) {
                        $this->template()->setHeader('<meta http-equiv="refresh" content="2;url=' . $this->url()->makeUrl('admincp.language.import',
                                ['dir' => base64_encode($dir), 'is_confirm' => $bIsConfirm, 'page' => ($iPage + 1)]) . '">');
                    }
                }
            }

            $this->template()->setTitle(_p('manage_language_packages'))
                ->setBreadCrumb(_p('manage_language_packages'))
                ->assign(array(
                        'aNewLanguages' => Phpfox::getService('language')->getForInstall(),
                        'bImportPhrases' => $bImportPhrases
                    )
                );
        }

        $this->template()->setActiveMenu('admincp.globalize.language');
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('language.component_controller_admincp_import_clean')) ? eval($sPlugin) : false);
    }
}