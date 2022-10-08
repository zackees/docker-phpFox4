<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Profile_Component_Block_Cover
 */
class Profile_Component_Block_Cover extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if (($iPageId = $this->request()->get('page_id'))) {
            $this->template()->assign(array(
                'iPageId' => $iPageId
            ));
        }
        if (($iGroupId = $this->request()->get('groups_id'))) {
            $this->template()->assign(array(
                'iGroupId' => $iGroupId
            ));
        }

        $iMaxUploadFileSize = Phpfox::getUserParam('photo.photo_max_upload_size');
        $this->template()->assign([
            'iMaxUploadFileSize' => $iMaxUploadFileSize,
            'sUploadError' => html_entity_decode(_p('upload_error')),
            'sPhotoLargerThanLimit' => html_entity_decode(_p('your_photo_is_larger_than_limit_file_size_mb', ['size' => Phpfox_File::filesize($iMaxUploadFileSize * 1024)]) . '. ' . _p('upload_another_photo'). '?'),
            'sChangePhoto' => html_entity_decode(_p('change_photo')),
            'sPhraseCancel' => html_entity_decode(_p('cancel'))
        ]);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('profile.component_block_cover_clean')) ? eval($sPlugin) : false);
    }
}
