$Behavior.quickEdit = function() {
  $('.sJsQuickEdit').dblclick(function() {
    $(this).createQuickEditForm($(this).find('.quickEdit').get(0).href);
    return false;
  });

  $('.quickEdit').click(function() {
    $(this).createQuickEditForm($(this).get(0).href);
    $('[data-toggle="dropdown"]', $(this).closest('.comment-options-holder')).trigger('click');

    return false;
  });
};

$(document).on('click', '[data-component="comment-cancel-edit"]', function() {
  $('[data-component="comment-cancel-btn"]', $(this).closest('.js_mini_feed_comment')).trigger('click');
});

$(document).on('keydown', '[data-component="comment-textarea"]', function(event) {
  if (!PF.isMobile) {
    if (event.which === 13) {
      if (event.ctrlKey || event.metaKey) {
        var val = this.value,
          start = this.selectionStart;

        this.value = val.slice(0, start) + '\n' + val.slice(this.selectionEnd);
        this.selectionStart = this.selectionEnd = start + 1;
      }
      else {
        event.preventDefault();
        $('[data-component="comment-submit-btn"]',
          $(this).closest('.js_mini_feed_comment')).trigger('click');
      }
    } else if (event.which === 27) {
      $('[data-component="comment-cancel-btn"]', $(this).closest('.js_mini_feed_comment')).trigger('click');
    }
  }
})
  .on('keyup', '[data-component="comment-textarea"]', function() {
    $Core.resizeTextarea($(this));
  })
  .on('paste', '[data-component="comment-textarea"]', function() {
    $Core.resizeTextarea($(this));
  });

$.fn.createQuickEditForm = function(sUrl) {
  $aParams = $.getParams(sUrl);

  eval('var sTempVar = \'js_cache_quick_edit' + $aParams['id'] + '\';');

  $(this).blur();

  if (document.getElementById(sTempVar)) {
    return;
  }

  var sParams = '';
  for (sVar in $aParams) {
    sParams += '&' + sVar + '=' + $aParams[sVar] + '';
  }
  sParams = sParams.substr(1, sParams.length);

  var sProcessing = '<span style="margin-left:4px; margin-right:4px; display:none; font-size:9pt; font-weight:normal;" id="js_quick_edit_processing' +
    $aParams['id'] + '">' + getPhrase('processing') + '...</span>';

  switch ($aParams['type']) {
    case 'input':
      $('body').
        append('<div id="js_cache_quick_edit' + $aParams['id'] +
          '" style="display:none;">' + $('#' + $aParams['id']).html() +
          '</div>');
      var sValue = $('#' + $aParams['content']).html();
      sValue = sValue.replace(/"/g, '&quot;').replace(/'/g, '&#039;');
      var sHtml;
      sHtml = ' <input style="vertical-align:middle;" size="20" type="text" name="quick_edit_input" value="' +
        sValue + '" id="js_quick_edit' + $aParams['id'] + '" /> ';
      sHtml += ' <input style="vertical-align:middle;" type="button" value="' +
        getPhrase('save') +
        '" class="button" onclick="$(\'#js_quick_edit_processing' +
        $aParams['id'] + '\').show(); $(\'#js_cache_quick_edit' +
        $aParams['id'] + '\').remove(); $(\'#js_quick_edit' + $aParams['id'] +
        '\').ajaxCall(\'' + $aParams['call'] + '\', \'' + sParams + '\');" /> ';
      sHtml += ' <input style="vertical-align:middle;" type="button" value="' +
        getPhrase('cancel') + '" class="button button_off" onclick="$(\'#' +
        $aParams['id'] + '\').html($(\'#js_cache_quick_edit' + $aParams['id'] +
        '\').html()); $(\'#js_cache_quick_edit' + $aParams['id'] +
        '\').remove(); $Core.loadInit();" /> ';
      sHtml += sProcessing;
      $('#' + $aParams['id']).html(sHtml);
      $('#js_quick_edit' + $aParams['id']).focus();
      break;
    case 'text':
      $('#' + $aParams['id']).hide();
      $('body').
        append('<div id="js_cache_quick_edit' + $aParams['id'] +
          '" style="display:none;">' + $('#' + $aParams['id']).html() +
          '</div>');
      var sHtml;
      $.ajaxCall($aParams['data'], '' + sParams + '');
      sHtml = '<div id="js_quick_edit_id' + $aParams['id'] + '">' +
        $.ajaxProcess(getPhrase('loading_text_editor')) + '</div>';

      if (!PF.isMobile) {
        sHtml += '<div class="comment-edit-help-block hide">' + getPhrase('press_esc_to_cancel_edit') + '.</div>';
      }

      sHtml += '<div class="t_right" style="padding:4px 0 4px 0;">';
      sHtml += sProcessing;
      sHtml += ' <input type="button" data-component="comment-cancel-btn" value="' + getPhrase('cancel') +
        '" class="btn btn-default btn-sm button_off" ' + (PF.isMobile ? '' : 'style="display:none !important"') + ' onclick="$(\'#' + $aParams['id'] +
        '\').html($(\'#js_cache_quick_edit' + $aParams['id'] +
        '\').html()); $(\'#js_cache_quick_edit' + $aParams['id'] +
        '\').remove()" /> ';

      sHtml += ' <input type="button" data-component="comment-submit-btn" value="' + getPhrase('submit') +
        '" class="btn btn-primary btn-sm"' + (PF.isMobile ? '' : 'style="display:none !important"') + ' onclick="if (function_exists(\'js_quick_edit_callback\')){js_quick_edit_callback();} $(\'#js_quick_edit_processing' +
        $aParams['id'] + '\').show(); $(\'#js_quick_edit' + $aParams['id'] +
        '\').ajaxCall(\'' + $aParams['call'] + '\', \'' + sParams + '\');" /> ';

      if (isset($aParams['main_url'])) {
        if (function_exists('quickSubmit')) {
          sHtml += ' <input type="button" onclick="quickSubmit(\'' +
            $aParams['id'] + '\', \'' + $aParams['main_url'] + '\')" value="' +
            getPhrase('go_advanced') + '" class="button button_off" /> ';
        }
        else {
          sHtml += ' <input type="button" value="' + getPhrase('go_advanced') +
            '" class="button button_off" onclick="window.location.href=\'' +
            $aParams['main_url'] + '\';" /> ';
        }
      }

      sHtml += '</div>';
      $('#' + $aParams['id']).html(sHtml);
      $('#' + $aParams['id']).show();
      $('#js_quick_edit' + $aParams['id']).focus();

      break;
  }
};