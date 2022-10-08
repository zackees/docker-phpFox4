<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Attachment_Component_Ajax_Ajax extends Phpfox_Ajax
{	
	public function upload()
	{
		Phpfox::getBlock('attachment.upload', array(
				'sCategoryId' => $this->get('category_id')
			)
		);
	
		$this->call('$("#js_attachment_content").html("' . $this->getContent() . '");');
		$this->call("$('#swfUploaderContainer').css('top',70).css('z-index',880);");
		$this->call('$Core.loadInit();');
	}
	
	public function add()
	{
		if ($this->get('attachment_custom') == 'photo')
		{
			$this->setTitle(_p('attach_a_photo'));
		}
		elseif ($this->get('attachment_custom') == 'video')
		{
			$this->setTitle(_p('attach_a_video'));
		}
		else 
		{
			$this->setTitle(_p('attach_a_file'));
		}
				
				
		$aParams = array(
				'sAttachments' => $this->get('attachments'),
				'sCategoryId' => $this->get('category_id'),
				'iItemId' => $this->get('item_id'),
				'sAttachmentInput' => $this->get('input'),
                'attachment_custom' => $this->get('attachment_custom')
			);
		Phpfox::getBlock('attachment.add', $aParams);
	}
	
	public function browse()
	{
		Phpfox::getBlock('attachment.archive', array('sPage' => (int)$this->get('page')));
		$this->call('$("#js_attachment_content").html("' . $this->getContent() . '");');
		$this->call("$('#swfUploaderContainer').css('top',0).css('z-index',0);");
		
	}
	
	public function updateDescription()
	{
		if (($iUserId = Phpfox::getService('attachment')->hasAccess($this->get('iId'), 'delete_own_attachment', 'delete_user_attachment')) && Phpfox::getService('attachment.process')->updateDescription((int) $this->get('iId'), $iUserId, $this->get('info')))
		{
			$this->html('#js_description' . $this->get('iId'), Phpfox::getLib('parse.output')->clean(Phpfox::getLib('parse.input')->clean($this->get('info'))), '.highlightFade()');
		}
	}

    public function delete()
    {
        $iUserId = Phpfox::getService('attachment')->hasAccess($this->get('id'), 'delete_own_attachment',
            'delete_user_attachment');
        $aAttachment = Phpfox::getService('attachment')->getItem($this->get('id'));

        if (empty($aAttachment['attachment_id'])) {
            return false;
        }

        if ($iUserId && is_numeric($iUserId) &&
            Phpfox::getService('attachment.process')->delete($iUserId, $this->get('id'))
        ) {
            $sEditorHolder = $this->get('editorHolderId');
            if ($sEditorHolder) {
                $this->remove("#js_attachment_id_{$this->get('id')}', '#{$this->get('editorHolderId')}")
                    ->call("typeof \$Core.Attachment !== 'undefined' && \$Core.Attachment.descreaseCounter('{$this->get('editorHolderId')}');");
            } else {
                $this->call("typeof \$Core.Attachment !== 'undefined' && \$Core.Attachment.descreaseCounter(\$Core.Attachment.getEditorHolder('#js_attachment_id_{$this->get('id')}', true));")
                    ->remove("#js_attachment_id_{$this->get('id')}")
                    ->call('$Core.checkAttachmentHolder();');
            }

            if (!empty($sEditorId = $this->get('editor_id'))) {
                $this->call('$Core.Attachment.removeInline(null, ' . (!empty($aAttachment['inline']['path']) ? '"' . $aAttachment['inline']['path'] . '"' : (int)$aAttachment['attachment_id']) . ', "' . $sEditorId . '");');
            }
        }
    }
	
	public function updateActivity()
	{
        Phpfox::getService('attachment.process')->updateActivity($this->get('id'), $this->get('active'));
	}

	public function addViaLink()
	{
		Phpfox::isUser(true);
		
		$aVals = $this->get('val');
		
		if (Phpfox::getService('link.process')->add($aVals, true))
		{
			$iId = Phpfox::getService('link.process')->getInsertId();
			
			$iAttachmentId = Phpfox::getService('attachment.process')->add(array(
					'category' => $aVals['category_id'],
					'link_id' => $iId
				)
			);			
			
			Phpfox::getBlock('link.display', array(
					'link_id' => $iId
				)
			);
			
			$this->call('var $oParent = $(\'#' . $aVals['attachment_obj_id'] . '\');');
			$this->call('$oParent.find(\'.js_attachment:first\').val($oParent.find(\'.js_attachment:first\').val() + \'' . $iAttachmentId . ',\'); $oParent.find(\'.js_attachment_list:first\').show(); $oParent.find(\'.js_attachment_list_holder:first\').prepend(\'<div class="attachment_row">' . $this->getContent() . '</div>\');');
			if (isset($aVals['attachment_inline']))
			{
				$this->call('$Core.clearInlineBox();');
			}
			else
			{
				$this->call('tb_remove();');
			}
		}
	}
	
	public function playVideo()
	{
		$aAttachment = Phpfox::getService('attachment')->getForDownload($this->get('attachment_id'));
		
		$sVideoPath = Phpfox::getParam('core.url_attachment') . $aAttachment['destination'];
		if (!empty($aAttachment['server_id']))
		{
			$sVideoPath = Phpfox::getLib('cdn')->getUrl($sVideoPath, $aAttachment['server_id']);	
		}		
		
		$sDivId = 'js_tmp_avideo_player_' . $aAttachment['attachment_id'];
		$this->html('#js_attachment_id_' . $this->get('attachment_id') . '', '<div id="' . $sDivId . '" style="width:480px; height:295px;"></div>');
		$this->call('$Core.player.load({id: \'' . $sDivId . '\', auto: true, type: \'video\', play: \'' . $sVideoPath . '\'}); $Core.player.play(\'' . $sDivId . '\', \'' . $sVideoPath . '\');');		
	}

    public function deleteAttachment()
    {
        $iItemId = $this->get('item_id');
        if (($iUserId = Phpfox::getService('attachment')->hasAccess($iItemId, 'delete_own_attachment', 'delete_user_attachment')) &&
            is_numeric($iUserId) && Phpfox::getService('attachment.process')->delete($iUserId, $iItemId)
        ) {
            $this->call("$('#js_attachment_id_" . $iItemId . "').remove();")
                ->call("$('.attachment_time_same_block').not(':has(.attachment-row)').remove()");
        }
    }

    public function reloadAttachmentList()
    {
        Phpfox::isUser(true);

        $attachmentIds = trim($this->get('ids'), ',');
        if (!isset($attachmentIds) || $attachmentIds == '') {
            return false;
        }

        $holderId = $this->get('holder_id');
        list(, $aRows) = Phpfox::getService('attachment')->get("attachment.attachment_id IN (" . $attachmentIds . ") AND attachment.view_id = 0", 'attachment.attachment_id DESC', false);
        if (!empty($aRows)) {
            Phpfox::getLib('template')->assign([
                'aAttachments' => $aRows,
                'sUrlPath' => Phpfox::getParam('core.url_attachment'),
                'sUsage' => Phpfox::getUserBy('space_attachment'),
                'bIsAttachmentNoHeader' => true,
                'bIsAttachmentEdit' => false,
                'bIsGetAttachmentList' => true,
                'sEditorId' => $this->get('ele_id', ''),
            ])->getTemplate('attachment.block.list');
            if (!empty($content = $this->getContent(false))) {
                $this->html('#' . $holderId . ' .attachment_list', $content . '<span class="no-attachment hide">' . _p('no_attachments_available') . '</span>');
                $this->call('$Core.Attachment.reInitReload(' . json_encode(['holder_id' => $holderId, 'total' => count($aRows)]) . ');');
            }
        }
    }

    /**
     * Update attachment view count
     */
    public function updateCounter()
    {
        $id = (int)$this->get('item_id');
        Phpfox::getService('attachment.process')->updateCounter($this->get('item_id'));
        $this->call("if ($('#attachment_counter_{$id}').length) { if (typeof currentAttachmentCounter === 'undefined') { var currentAttachmentCounter = parseInt($('#attachment_counter_{$id} .js_number').text()) + 1; } else { currentAttachmentCounter = parseInt($('#attachment_counter_{$id} .js_number').text()) + 1; }}");
        $this->call("if ($('#attachment_counter_{$id}').length) { $('#attachment_counter_{$id} .js_number').text(currentAttachmentCounter); $('#attachment_counter_{$id} .js_text').text(currentAttachmentCounter === 1 ? '" . _p('view') . "' : '" . _p('views') . "');}");
    }
}