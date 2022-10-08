var $iPositionPlus = 25;
var $oCurrentGlobalObj = null;

$Core.updateInlineBox = function() {
  var $oPosition = $($oCurrentGlobalObj).
    parents('.global_attachment_list:first').
    find('.js_global_position_photo:first').
    offset();
  var $oPositionLink = $('.global_attachment_list li a.active').offset();

  $('#global_attachment_list_inline').css(
    {
      top: ($oPosition.top + $iPositionPlus) + 'px',
      left: ($oPositionLink.left) + 'px',
    });
};

$Core.clearInlineBox = function() {
  $('#global_attachment_list_inline').hide();
  $('.global_attachment_list li a').removeClass('active');
};

$Core.shareInlineBox = function(
  $oObj, $sAttachmentId, $bIsInlineAttachment, $sRequest, $iWidth, $sExtra) {
  $oCurrentGlobalObj = $oObj;

  $('#js_global_tooltip').hide();

  $sExtra = $sExtra + '&attachment_obj_id=' + $sAttachmentId;

  if ($bIsInlineAttachment) {
    $sExtra = $sExtra + '&attachment_inline=true';

    if ($('#global_attachment_list_inline').length <= 0) {
      var $sContent = '';

      $sContent += '<div id="global_attachment_list_inline"><div id="global_attachment_list_inline_holder"></div>';
      $sContent += '<div id="global_attachment_list_inline_close"><a href="#" onclick="$Core.clearInlineBox(); $bIsPreview=false; return false;">' +
        getPhrase('close') + '</a></div>';
      $sContent += '</div>';

      $('body').prepend($sContent);
    }

    $('#global_attachment_list_inline').hide();

    var $oPosition = $($oObj).offset();
    $('#global_attachment_manage').show();
    $($oObj).
      parents('.global_attachment_list:first').
      find('li a').
      removeClass('active');
    $($oObj).addClass('active');

    $Core.ajax($sRequest,
      {
        params: $sExtra,
        success: function($mData) {
          // $($oObj).parents('.global_attachment_header:first
          // .global_attachment_list_holder:first').html('<div
          // class="attachment_inline_holder">' + $mData + '</div>');

          $('#global_attachment_manage').hide();
          $('#global_attachment_list_inline_holder').html($mData);
          if ($('html[dir="rtl"]').length > 0) {
            $('#global_attachment_list_inline').css(
              {
                right: $(window).width() - $oPosition.left -
                $($oObj).outerWidth() + 'px',
                top: ($oPosition.top + $iPositionPlus) + 'px',
              });
          }
          else {
            $('#global_attachment_list_inline').css(
              {
                left: $oPosition.left + 'px',
                top: ($oPosition.top + $iPositionPlus) + 'px',
              });
          }

          $('#global_attachment_list_inline').show();

        },
      });
  }
  else {
    $Core.box($sRequest, $iWidth, $sExtra);
  }

  return false;
};

$Core.uploadNewAttachment = function($oObj, $bIsMultiShare, $sUploadPhrase) {
  var $oParent = $($oObj).
    parents('.js_upload_attachment_parent_holder:first').
    find('.js_default_upload_form:first');
  var $oPostParent = $($oObj).parents('.js_default_upload_form:first');

  $($oObj).parents('.js_upload_frame_form:first').submit();
  $oPostParent.find('.js_upload_form_holder').hide();
  $oPostParent.find('.js_upload_form_image_holder').
    find('span:first').
    html(getPhrase('uploading') + ' ' + $($oObj).val().split('\\').pop() +
      '...');
  $oPostParent.find('.js_upload_form_image_holder').show();

  var $sCategoryName = $oParent.find('.category_name:first').val();

  if ($bIsMultiShare) {
    var $oNewDate = new Date;
    var $iTotalFormsCreated = $oNewDate.getTime();

    $($oObj).
      parents('.js_upload_attachment_parent_holder:first').
      find('.js_add_new_form:first').
      append('<div id="js_new_temp_form_' + $iTotalFormsCreated + '_' +
        $sCategoryName + '" class="js_default_upload_form p_bottom_4">' +
        $oParent.html() + '</div>');

    var $oNew = $('#js_new_temp_form_' + $iTotalFormsCreated + '_' +
      $sCategoryName + '');

    $oNew.find('form:first')[0].reset();
    $oNew.find('.js_file_attachment:first').val('');
    $oNew.find('.js_upload_form_holder').show();
    $oNew.find('.js_upload_form_image_holder').hide();
    $oNew.find('.js_temp_upload_id:first').
      val('js_new_temp_form_' + $iTotalFormsCreated + '_' + $sCategoryName +
        '');
    $oNew.find('.js_upload_form_holder_global:first').attr('id', '');
  }

  $('#attachment_js_upload_frame_form .extra_info').hide();
};

$Core.Attachment = {
  reInitReload: function(params) {
    let holderObject = $('#' + params['holder_id']);
    if (holderObject.length) {
      holderObject.find("#attachment-toggle-button .attachment-counter").html('(' + (params.hasOwnProperty('total') ? params['total'] : 0) + ')');
      holderObject.find('.attachment-delete-all').removeClass('hide');
      $Core.loadInit();
    }
  },
  initReload: function(params) {
    if (typeof params === "undefined"
      || !params.hasOwnProperty('holder_id')
      || !params.hasOwnProperty('id')) {
      return false;
    }

    let attachmentHolderId = params['holder_id'];
    if (attachmentHolderId !== '') {
      let attachmentObjectId = $('#' + attachmentHolderId).find('input[type="hidden"][name="attachment_obj_id"]').val(),
          eleId = params['id'];
      if (attachmentObjectId !== '') {
        let parentObject = $('#' + attachmentObjectId);
        if (!parentObject.length) {
          parentObject = $('.' + attachmentObjectId);
        }
        if (parentObject.length) {
          let attachmentInput = parentObject.find('.js_attachment'),
              attachmentValues = attachmentInput.val(),
              attachmentIds = typeof attachmentValues !== "undefined" && trim(attachmentValues) !== '' ? trim(attachmentInput.val(), ',') : '',
              canReloadList = false,
              attachmentItems = parentObject.find('.attachment-row');
          if (typeof attachmentIds !== "undefined" && attachmentIds !== '') {
            if (attachmentItems.length) {
              let tempAttachmentIds = attachmentIds.split(',');
              $.each(tempAttachmentIds, function(key, value) {
                if (!$('#js_attachment_id_' + value).length) {
                  canReloadList = true;
                  return false;
                }
              });
              if (canReloadList) {
                if (attachmentItems.length) {
                  attachmentItems.each(function() {
                    let attachmentId = $(this).attr('id').replace('js_attachment_id_', '');
                    if (parseInt(attachmentId) > 0 && tempAttachmentIds.indexOf(attachmentId) === -1) {
                      tempAttachmentIds.push(attachmentId);
                    }
                  });
                }
                attachmentIds = tempAttachmentIds.join(',');
              }
            } else {
              canReloadList = true;
            }
          }

          if (canReloadList) {
            $.ajaxCall('attachment.reloadAttachmentList', $.param({
              ids: attachmentIds,
              holder_id: attachmentHolderId,
              ele_id: eleId,
            }));
          }
        }
      }
    }
  },
  dropzoneOnSending: function(data, xhr, formData, ele) {
    $('#attachment_params', ele.closest('.attachment-holder')).find('input').each(function() {
      formData.append($(this).prop('name'), $(this).val());
    });
    formData.append('current_total_attachments', $('.attachment-row').length);
  },

  dropzoneOnSuccess: function(ele, file, response) {
    eval(response);
  },

  deleteAll: function(ele) {
    var th = $(ele),
      editorHolder = $Core.Attachment.getEditorHolder(th),
      attachments = $('.attachment-row', editorHolder),
      textarea = $('textarea', editorHolder);
    $Core.jsConfirm({
      message: oTranslations['are_you_sure_you_want_to_delete_all_attachment_files'] ? oTranslations['are_you_sure_you_want_to_delete_all_attachment_files'] : oTranslations['are_you_sure']
    }, function() {
      attachments.each(function(key, attachment) {
        $.ajaxCall('attachment.delete', $.param({
          id: $(attachment).prop('id').replace('js_attachment_id_', ''),
          editorHolderId: editorHolder.attr('id')
        }));
      });
      // empty counter
      $('.attachment-counter', editorHolder).empty();
      $('.attachment-delete-all', editorHolder).addClass('hide');
      $('.no-attachment', editorHolder).removeClass('hide');

      var attachmentUploader = $Core.dropzone.instance['attachment_' + textarea.attr('id')];
      if (typeof attachmentUploader !== 'undefined') {
        attachmentUploader.removeAllFiles();
      }
    }, function() {});
  },

  attachPhoto: function(ele) {
    var holder = $Core.Attachment.getEditorHolder(ele);
    $('.dropzone-button-attachment', holder).trigger('click');
    $('[name="custom_attachment"]', holder).val('photo');
  },

  increaseCounter: function(editorHolderId) {
    var attachmentCounter = $('.attachment-counter', '#' + editorHolderId),
      counter = attachmentCounter.html(),
      number = parseInt(counter.substr(1, counter.length - 1));

    if (!number) {
      attachmentCounter.html('(1)');
      $('.attachment-delete-all', '#' + editorHolderId).removeClass('hide');
    }
    else {
      attachmentCounter.html('(' + ++number + ')');
    }

    // set flag has_attachment
    $('[name="has_attachment"]', '#' + editorHolderId).val(1);
  },

  descreaseCounter: function(editorHolderId) {
    var attachmentCounter = $('.attachment-counter', '#' + editorHolderId);

    if (typeof editorHolderId == 'undefined' || typeof attachmentCounter === 'undefined') {
      return;
    }

    var counter = attachmentCounter.html();
    if (counter.length === 0) {
      return;
    }

    var number = parseInt(counter.substr(1, counter.length - 1));

    if (number == 1) {
      attachmentCounter.empty();
      $('.attachment-delete-all', '#' + editorHolderId).addClass('hide');
    }
    else {
      attachmentCounter.html('(' + --number + ')');
    }

    if ($('.attachment-row', '#' + editorHolderId).length === 0) {
      $('.no-attachment', '#' + editorHolderId).removeClass('hide');
    }
  },

  resetForm: function(holderId, textareaId) {
    if (typeof $Core.dropzone.instance['attachment_' + textareaId] !== 'undefined' &&
      typeof $Core.dropzone.instance['attachment_' + textareaId].removeAllFiles === 'function') {
      $Core.dropzone.instance['attachment_' + textareaId].removeAllFiles();
    }

    if ($('.attachment-row', '#' + holderId).length) {
      $('.no-attachment', '#' + holderId).addClass('hide');
    }
  },

  resetAttachmentHolder: function(holder) {
    // empty counter
    $('.attachment-row', holder).remove();
    $('.attachment-delete-all', holder).addClass('hide');
    $('.no-attachment', holder).removeClass('hide');
    $('.attachment-counter', holder).html('(0)');
    $('[name="has_attachment"]', holder).val(0);

    var textarea = $('textarea', holder),
      attachmentUploader = $Core.dropzone.instance['attachment_' + textarea.attr('id')];
    if (typeof attachmentUploader !== 'undefined') {
      attachmentUploader.removeAllFiles();
    }
  },

  toggleAttachmentForm: function(ele) {
    var editor = $Core.Attachment.getEditorHolder($(ele));
    if($Core.getIEVersion()){
      $('.attachment-form-holder', editor).toggle();
    }else{
      $('.attachment-form-holder', editor).slideToggle();
    }
    
    $('.global_attachment', editor).toggleClass('attachment-form-open');

    return false;
  },

  getEditorHolder: function(child, returnId) {
    if (typeof child === 'string') {
      child = $(child);
    }
    var editor = child.closest('.attachment-holder');

    return returnId ? editor.attr('id') : editor;
  },
  
  insertInline: function(ele, name, attachmentId, path, url, isImage, editorId) {
    if (!editorId) {
      return;
    }

    Editor.setId(editorId)
      .insert({
        is_image: true,
        name: name,
        id: attachmentId,
        type: (typeof isImage !== 'undefined' && isImage) ? 'image' : 'attachment',
        path: path,
        url: url
      });
    $(ele).closest('.attachment-row-actions').find('span.js_attachment_remove_inline').fadeIn();
  },

  removeInline: function(ele, attachment, editorId) {
    if (!editorId) {
      return;
    }

    Editor.setId(editorId);
    var content = Editor.getContent();
    var isEditor = true;
    if(content === undefined) {
      content = $('#' + editorId).val();
      isEditor = false;
      if (content === undefined) {
        return;
      }
    }
    if (typeof attachment === 'string') {
      content = content.replace(new RegExp("<img[^>]*src=\"" + attachment + "\"[^>]*>", 'ig'), '').replace(new RegExp("\\[img\\]" + attachment + "\\[\/img\\]", 'ig'), '');
      if(isEditor) {
        Editor.setContent(content);
      }
      else {
        $('#' + editorId).val(content);
      }
    } else if (typeof attachment === 'number') {
      var rg = new RegExp('\\[attachment="' + attachment + '"][^\\]]*\\[\\/attachment\\]', 'ig'),
          rgCKEditor = new RegExp('\\[attachment=&quot;' + attachment + '&quot;][^\\]]*\\[\\/attachment\\]', 'ig');
      content = content.replace(rg, '').replace(rgCKEditor, '');
      if(isEditor) {
        Editor.setContent(content);
      }
      else {
        $('#' + editorId).val(content);
      }list.html.php
    }

    if (ele && $(ele).length) {
      $(ele).closest('span.js_attachment_remove_inline').fadeOut();
    }
  },

  appendAttachmentList: function(formId, content, editorId) {
    let formObject = $('#' + formId);
    if (formObject.length) {
      let attachmentListContent = $Core.b64DecodeUnicode(content);
      if (typeof editorId === 'string' && editorId !== '' && formObject.find('.js_attachment_holder[data-element-id="' + editorId + '"]').length) {
        var parentObject = $('.attachment_list', formObject.find('.js_attachment_holder[data-element-id="' + editorId + '"]'));
      } else {
        var parentObject = $('.attachment_list', formObject);
      }
      parentObject.prepend(attachmentListContent);
    }
  },
  
  checkRemoveInlineButtons: function() {
    var content = Editor.getContent();
    if (content === undefined) {
      content = $('#' + Editor.getId()).val();
      if (content === undefined) {
          return;
      }
    }

    // check attachment
    var attachments = this.checkInline(content, /\[attachment="([0-9]*)"\][^\[]*\[\/attachment\]/g);
    attachments.forEach(function(attachmentId) {
      $('.js_attachment_remove_inline', '#js_attachment_id_' + attachmentId).show();
    });

    var images = (this.checkInline(content, /<img[^>]*src=["']([^>"']*)["'][^>]*>/g)).concat(this.checkInline(content, /\[img\]([^\[]*)\[\/img\]/g));
    images.forEach(function(imageUrl) {
      $('[data-inline-path="' + imageUrl + '"]').parent().show();
    });
  },

  checkInline: function(content, regex) {
    var m, result = [];

    while ((m = regex.exec(content)) !== null) {
      // This is necessary to avoid infinite loops with zero-width matches
      if (m.index === regex.lastIndex) {
        regex.lastIndex++;
      }

      // The result can be accessed through the `m`-variable.
      m.forEach(function(value, index) {
        if (index === 1 && typeof value !== 'undefined') {
          result.push(value);
        }
      });
    }

    return result;
  },
};

$Behavior.checkRemoveInlineButtons = function() {
  $Core.Attachment.checkRemoveInlineButtons();
};

$Behavior.attachmentEvents = function() {
  $(document).on('mouseenter', '[id^="attachment-dropzone"]', function() {
    $('[name="custom_attachment"]', $Core.Attachment.getEditorHolder($(this))).val('');
  });
  $(document).on('click', '.dz-attachment-upload-again', function() {
    $('.dropzone-button-attachment', $Core.Attachment.getEditorHolder($(this))).trigger('click');
  });
  $(document).on('click', '.dropzone-button-attachment', function() {
    $('[name="custom_attachment"]', $Core.Attachment.getEditorHolder($(this), false)).val('');
  });
  $Behavior.attachmentEvents = function() {};
};
