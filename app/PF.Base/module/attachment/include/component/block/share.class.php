<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Attachment_Component_Block_Share extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $mAttachmentShare = $this->getParam('attachment_share', null);
        $id = $this->getParam('id');

        if ($mAttachmentShare === null) {
            return false;
        }

        if (!is_array($mAttachmentShare)) {
            $mAttachmentShare = array('type' => $mAttachmentShare);
        }

        if (!isset($mAttachmentShare['inline'])) {
            $mAttachmentShare['inline'] = false;
        }

        if (!empty($aForms = $this->template()->getVar('aForms'))) {
            if (isset($aForms['total_attachment_' . strtolower($id)])) {
                $totalAttachment = (int)$aForms['total_attachment_' . strtolower($id)];
            } elseif (!empty($aForms['total_attachment'])) {
                $totalAttachment = $aForms['total_attachment'];
            }

            if (!empty($totalAttachment)) {
                $this->template()->assign('totalAttachment', $totalAttachment);
            }
        }

        $this->template()->assign(array(
                'aAttachmentShare' => $mAttachmentShare,
                'id' => $id,
                'holderId' => uniqid('attachment_holder_'),
                'defaultAttachmentLocation' => isset($mAttachmentShare['default_attachment_location']) ? $mAttachmentShare['default_attachment_location'] : null,
            )
        );

        return null;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('attachment.component_block_share_clean')) ? eval($sPlugin) : false);
    }
}