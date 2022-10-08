
var bAutoSaveSettings = false,
	aAdminCPSearchValues = new Array();

// disable ajax mode in admincp
oParams.bOffFullAjaxMode = true;

PF.cmd('admincp.open_sub_menu', function (ele, evt) {
    var li = ele.closest('li').toggleClass('open'),
		id = li.attr('id'),
		key =  'admin_open_sub_menu';
    li.hasClass('open')? setCookie(key, id):deleteCookie(key);
    evt.preventDefault();
	return false;
}).cmd('admincp.site_setting_remove_input', function (obj) {
    $Core.jsConfirm({}, function () {
        obj.closest('.p_4').remove();
    }, function () {
    	obj.closest('form').trigger('submit');
    });
}).cmd('admincp.site_setting_add_input', function (btn) {
    var holder = btn.closest('.js_array_holder'),
        sVarName = btn.data('rel'),
        sValue = $('.js_add_to_array', holder).val(),
        iCnt = (parseInt($('#js_array_count', holder).html()) + 1),
		form = btn.closest('form') ;

    $('.js_array_data', holder).append('<div class="p_4" id="js_array' + iCnt + '"><div class="input-group"><input class="form-control" value="' + sValue + '" type="text" name="' + sVarName + '" placeholder="Add a New Value..." size="30" /><span class="input-group-btn"><a role="button" class="btn btn-danger" data-cmd="admincp.site_setting_remove_input"><i class="fa fa-remove"></a></span></div></div>');
    $('.js_array_count', holder).html(iCnt);
    $('.js_add_to_array', holder).val('').focus();

    if (form.attr('action') == '#') {
        form.trigger('submit');
    }
    else {
        $Core.processing();
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(e) {
                $('.ajax_processing').fadeOut();
            }
        });
    }
}).cmd('admincp.remove_user_image', function (btn) {
    $Core.jsConfirm('', function () {
        $.ajaxCall('user.deleteProfilePicture', 'id=' + btn.data('user-id'));
    }, function () {});
});
PF.cmd('admincp.ajax_menu',function(btn,evt){
    var href = btn.attr('href');
    btn.parent().children().removeClass('active');
    btn.addClass('active');
    evt.preventDefault();
    $.ajax({
        url: href,
        contentType: 'application/json',
        success: function(e) {
            $('#site_content').html(e.content).show();
            $('.breadcrumbs a:last').text(btn.text());
            $Core.loadInit();
        }
    });
});

function _admincp_load_content(
  customContent, contentIsLoaded, extraParams, appUrl) {
  if (contentIsLoaded) {
    return true;
  }

  $.ajax({
    url: customContent,
    data: {
      bIsAdminCp: true
    },
    contentType: 'application/json',
    success: function(e) {
      $('#app-content-holder').hide();
      $('#app-custom-holder').html(e.content).show();
      if (typeof e.title === 'string') {
        $('title').text(e.title);
      }
      if (typeof e.actual_title === 'string' && $('.page-title').length) {
        $('.page-title').html(e.actual_title);
      }
      if (typeof e.breadcrumb_menu === 'string') {
        $(e.breadcrumb_menu).appendTo('.breadcrumbs');
      }

      $Core.loadInit();
    },
  });

  return true;
}
function _admincp_toggle_icon_font_picker (inputId) {
    var eleHelper = $('#menu_font_helper'), eleIconPreview = $('#js_icon_font_picker_preview'), eleIconContainer = $('#js_icon_font_picker_container');
    $('#' + inputId).val('');
    $('.js_icon_selected_remove').trigger('click');
    if (!eleIconPreview.is(':visible')) {
        $('#' + inputId).attr('type', 'hidden');
        eleIconPreview.show();
        eleIconContainer.show();
        $('#js_font_aws_helper').show();
        $('#js_font_lineficon_helper').hide();
    } else {
        $('#' + inputId).attr('type', 'text');
        eleIconPreview.hide();
        eleIconContainer.hide();
        $('#js_font_aws_helper').hide();
        $('#js_font_lineficon_helper').show();
    }
    eleHelper.toggle();
}
function _admincp_on_change_menu_placement (ele) {
    var ele = $(ele), parentEle = $('#js_add_parent_menu');
    if (ele.val() == 'main') {
        parentEle.removeClass('hide');
        parentEle.find('select').removeAttr('disabled');
    } else {
        parentEle.addClass('hide');
        parentEle.find('select').attr('disabled', true);
    }
}

$Behavior.tableHover = function()
{
	if ($('#_sort tbody').hasClass('ui-sortable')) {
		$('#_sort tbody').sortable('destroy');
	}
	$('#_sort tbody').sortable({
		handle: '.fa-sort',
		helper: 'clone',
		axis: 'y',
		stop: function(event, ui) {
			var ids = '';
			$('#_sort tr').removeClass('tr');
			$('#_sort tr').each(function(i, el) {
				var t = $(this);
				if (!t.data('sort-id')) {
					return;
				}

				if (i % 2 === 0) {

				}
				else {
					t.addClass('tr');
				}

				ids += t.data('sort-id') + ',';
			});

			var t = ui.item.find('> td:first-of-type');
			t.find('i').hide();
			t.prepend('<i class="fa fa-spin fa-circle-o-notch"></i>');

			$('#public_message').remove();
			$.ajax({
				url: $('#_sort').data('sort-url'),
				type: 'POST',
				data: 'is_ajax_post=1&ids=' + ids,
				success: function() {
					t.find('.fa-spin').remove();
					t.find('i').show();

					$('body').prepend('<div id="public_message" class="public_message" style="display:block;">' + getPhrase('order_updated') + '</div>');
					$Core.loadInit();
				}
			});
		}
	});

	$('table tr td:last-of-type .goJump').each(function() {
		var t = $(this), html = '';

		html = '<ul class="table_actions">';
		t.find('option').each(function() {
			var o = $(this);
			if (o.val().length > 2) {
				html += '<li><a href="' + o.val() + '">' + o.html() + '</a></li>';
			}
		});
		html += '</ul>';

		t.hide();
		t.parent().html(html);
	});

	if ($Core.exists('.table_hover_action') && !$Core.exists('#table_hover_action_holder')){
		$('#table_hover_action_holder').remove();
		$('body').append('<div id="table_hover_action_holder"></div>');
        $('#table_hover_action_holder').html('<div class="selected-count" style="display:none;"><span class="count-number">0</span> ' + oTranslations['items_selected'] + ' <span class="deselect-all">' + oTranslations['deselect_all'] + '</span></div><div>' + $('.table_hover_action').html() + '</div>');
        $('.table_hover_action').hide();
        $('body').addClass('has-action-bottom');

        $(document).on('click', '#table_hover_action_holder .deselect-all', function() {
            $("input:checkbox").prop('checked', false);
            $('.selected-count', '#table_hover_action_holder').hide();
            $('#table_hover_action_holder').removeClass('active');
            if ($('.sJsCheckBoxButton').length) {
                $('.sJsCheckBoxButton').addClass('disabled').prop('disabled', true);
            }
        });

		if (bAutoSaveSettings) {
			$('#table_hover_action_holder').addClass('hidden');
		}

	}

	$('#admincp_search_input').focus(function(){
		if (empty(aAdminCPSearchValues)){
			$.ajaxCall('admincp.buildSearchValues', '', 'GET');
		}

		if ($(this).val() == $('#admincp_search_input_default_value').html()){
			$(this).val('').addClass('admincp_search_input_focus');
		}
	});

	$('#admincp_search_input').blur(function(){
		if (empty($(this).val())){
			$(this).val($('#admincp_search_input_default_value').html()).removeClass('admincp_search_input_focus');
		}
	});

	$('#admincp_search_input').keyup(function(){
		if (!empty(aAdminCPSearchValues)){

			var iFound = 0;
			var oParent = $(this);
			var sHtml = '';

			if (empty(oParent.val())){
				$('#admincp_search_input_results').hide();
				return;
			}

			$(aAdminCPSearchValues).each(function(sKey, aResult){
				var mRegSearch = new RegExp(oParent.val(), 'i');

				if (aResult['title'].match(mRegSearch))
				{
					sHtml += '<li><a href="' + aResult['link'] + '">' + aResult['title'] + '<div class="extra_info">' + aResult['type'] + '</div></a></li>';
					iFound++;
				}

				if (iFound > 10){
					return false;
				}
			});

			if (iFound > 0){
				$('#admincp_search_input_results').html('<ul>' + sHtml + '</ul>');
				$('#admincp_search_input_results').show();
			}
			else{
				$('#admincp_search_input_results').hide();
			}
		}
	});

	$("#js_check_box_all").click(function()
  	{
   		var bStatus = this.checked;

   		if (bStatus)
   		{
   			$('.sJsCheckBoxButton').removeClass('disabled');
   			$('.sJsCheckBoxButton').prop('disabled', false);
   		}
   		else
   		{
   			$('.sJsCheckBoxButton').addClass('disabled');
   			$('.sJsCheckBoxButton').prop('disabled', true);
   		}

   		$("input.checkbox").each(function()
   		{
    		this.checked = bStatus;
   		});
  	});

	$('th').click(function()
	{
		if (typeof($(this).find('a').get(0)) != 'undefined')
		{
			window.location.href = $(this).find('a').get(0).href;
		}
	});


	$('.text').click(function()
	{
		return false;
	});

    $('.checkbox, .custom-checkbox-wrapper').click(function()
    {
    	var sIdName = '#js_row' + $(this).get(0).id.replace('js_id_row', '');
    	var iCnt = 0;
   		$("input:checkbox").each(function()
   		{
    		if (this.checked)
    		{
   				iCnt++;
    		}
   		});

      if (iCnt > 0) {
        $('.sJsCheckBoxButton').removeClass('disabled');
        $('.sJsCheckBoxButton').attr('disabled', false);
        $('.selected-count', '#table_hover_action_holder').show();
        $('#table_hover_action_holder').addClass('active');
      }
      else {
        $('.sJsCheckBoxButton').addClass('disabled');
        $('.sJsCheckBoxButton').attr('disabled', true);
        $('.selected-count', '#table_hover_action_holder').hide();
        $('#table_hover_action_holder').removeClass('active');
      }

   		if ($('#js_check_box_all').prop('checked')) {
   		  iCnt--;
      }

   		$('.count-number', '#table_hover_action_holder').html(iCnt);
    });

    $('table tr td:last-of-type .goJump').each(function () {
        var t = $(this), html = '';

        html = '<ul class="table_actions">';
        t.find('option').each(function () {
            var o = $(this);
            if (o.val().length > 2) {
                html += '<li><a href="' + o.val() + '">' + o.html() + '</a></li>';
            }
        });
        html += '</ul>';

        t.hide();
        t.parent().html(html);
    });

    $('.checkbox, .custom-checkbox-wrapper').click(function()
    {
    	var sIdName = '#js_user_' + $(this).get(0).id.replace('js_id_row', '');
    	if ($(sIdName).hasClass('is_checked'))
    	{
    		$(sIdName).removeClass('is_checked');
    	}
    	else
    	{
    		$(sIdName).addClass('is_checked');
    	}

    	var iCnt = 0;
   		$("input:checkbox").each(function()
   		{
    		if (this.checked)
    		{
   				iCnt++;
    		}
   		});

   		if (iCnt > 0)
   		{
   			$('.sJsCheckBoxButton').removeClass('disabled');
   			$('.sJsCheckBoxButton').attr('disabled', false);
   		}
   		else
   		{
   			$('.sJsCheckBoxButton').addClass('disabled');
   			$('.sJsCheckBoxButton').attr('disabled', true);
   		}
    });

    $('.js_drop_down_link').click(function () {
        var eleOffset = $(this).offset();

        $('#js_drop_down_cache_menu').remove();

        $(this).parent().find('ul').addClass('dropdown-menu');
        $('body').prepend('<div id="js_drop_down_cache_menu" style="position:absolute; left:' + (eleOffset.left + 7) + 'px; top:' + (eleOffset.top + 45) + 'px; z-index:9999;"><div class="link_menu dropdown open">' + $(this).parent().find('.link_menu:first').html() + '</div></div>');
        $Core.loadInit();

        $('#js_drop_down_cache_menu .link_menu').hover(function () {

            },
            function () {
                $('#js_drop_down_cache_menu').remove();
        });

        return false;
    });

    $('.link_menu a').click(function() {
      $('#js_drop_down_cache_menu').fadeOut('fast');
    });
    
    //close dropdown when click out
    $(document).mouseup(function(e) {
        if($("#js_drop_down_cache_menu").length > 0){
            var container = $("#js_drop_down_cache_menu");
            if (!container.is(e.target) && container.has(e.target).length === 0) 
            {
                $('#js_drop_down_cache_menu').remove();
            }
        }
      });
    //end

    $('.form_select_active').hover(
        function () {
            $(this).addClass('form_select_is_active');
        },
        function () {
            if (!$(this).hasClass('is_selected_and_active')) {
                $(this).removeClass('form_select_is_active');
            }
        });

    $('.form_select_active').click(function () {
        $('.form_select').hide();
        $('.form_select_active').removeClass('is_selected_and_active').removeClass('form_select_is_active');
        $(this).addClass('form_select_is_active');
        $(this).parent().find('.form_select:first').width($(this).innerWidth()).show();
        $(this).addClass('is_selected_and_active');

        return false;
    });

    $('.form_select li a').click(function () {
        $(this).parents('.form_select:first').hide();
        $('.form_select_active').removeClass('is_selected_and_active').removeClass('form_select_is_active');
        $(this).parents('.form_select:first').parent().find('.form_select_active:first').html($(this).html());

        aParams = $.getParams(this.href);
        var sParams = '';
        for (sVar in aParams) {
            sParams += '&' + sVar + '=' + aParams[sVar] + '';
        }
        sParams = sParams.substr(1, sParams.length);

        $Core.ajaxMessage();
        $.ajaxCall(aParams['call'], sParams + '&global_ajax_message=true');

        return false;
    });

    $(document).click(function () {
        $('.form_select').hide();
        $('.form_select_active').removeClass('is_selected_and_active').removeClass('form_select_is_active');
    });
};

if (typeof $Core.AdminCP === 'undefined') {
  $Core.AdminCP ={};
}

$Core.AdminCP.adminMassAction = function(ele) {
  $('.table_hover_action').append('<div><input type="hidden" name="' + $(ele).attr('name') + '" value="' + $(ele).attr('value') + '" /></div>')
  if ($('.table_hover_action').hasClass('table_hover_action_custom')){
    $Core.ajaxMessage();
    $($('.table_hover_action').parents('form:first')).ajaxCall('user.updateSettings');
    return false;
  }
  else{
    $('.table_hover_action').parents('form:first').submit();
  }
};

$(document).on('click', '#table_hover_action_holder input, #table_hover_action_holder button', function() {
  var th = $(this);
  if (typeof th.data('ajax-box') === 'string') {
    //Get selected
    var selectedId = [];
    $('.table_hover_action').parents('form:first').find('input[name="id[]"]').each(function() {
      if ($(this).prop('checked')) {
        selectedId.push($(this).val());
      }
    });
    tb_show('', $.ajaxBox(th.data('ajax-box'), $.param({
      ids: selectedId
    })))
  } else if (typeof th.data('confirm-message') === 'string') {
    $Core.jsConfirm({
      message: th.data('confirm-message')
    }, function() {
      $Core.AdminCP.adminMassAction(th);
    }, function() {});
  } else {
    $Core.AdminCP.adminMassAction(th);
  }
});

if ($Core.exists('.admincp-fixed-menu')) {
  $(document).on('click', 'ul.dropdown-menu a', function() {
    $(this).closest('ul').parent().find('a[data-toggle="dropdown"]').trigger('click');
  });
}

$Core.editMeta = function(phrase, newTab) {
  var url = PF.url.make('admincp/language/phrase', {q: phrase});
  if (typeof newTab === 'boolean' && newTab) {
    window.open(url, '_blank');
  } else {
    window.open(url, '_self');
  }
}

$Behavior.admincp_alert = function(){
    $('#js_admincp_alert').on('click', function(){
        if(!$("#js_admincp_alert_panel").hasClass('built'))
        {
            $.ajaxCall('admincp.loadLatestAlerts');
        }
    });
}

$Behavior.admincp_time_zone_settings = function(){
    if($('#js_core_admincp_time_zone_settings').length) {
        $('.js_check_time_zone', $('#js_core_admincp_time_zone_settings')).on('click', function () {
            var sRegion = $(this).data('checkbox-module');
            if (!$('[data-checkbox-module="' + sRegion + '"]:checked').length) {
                $('[data-module="' + sRegion + '"]', $('#js_core_admincp_time_zone_settings')).prop('checked', false);
            }
            else if ($('[data-checkbox-module="' + sRegion + '"]:checked').length) {
                $('[data-module="' + sRegion + '"]', $('#js_core_admincp_time_zone_settings')).prop('checked', true);
            }
        });

        $('.js_check_all_module', $('#js_core_admincp_time_zone_settings')).on('click', function (e) {
            e.stopPropagation();
            var sRegion = $(this).data('module');
            if ($(this).prop('checked')) {
                $('.js_toggle_module_' + sRegion).find('input[type="checkbox"]:not(.js_default_timezone)').prop('checked', true);
            }
            else {
                $('.js_toggle_module_' + sRegion).find('input[type="checkbox"]:not(.js_default_timezone)').prop('checked', false);
            }
        });
    }
}

$Behavior.admincp_table_toggle_row = function() {
    if($('.js_admincp_table_header_toggle').length) {
        $('.js_admincp_table_header_toggle').off('click').on('click',function () {
            var sModule = $(this).data('togglecontent');
            $('.js_toggle_module_' + sModule + ':not(".force_hidden")').toggleClass('open');
            $(this).toggleClass('open');
        });
    }
    if ($('.js_admincp_search_app_group_settings').length && typeof admincpAppGroupSettings !== "undefined" && $('#settings_container').length) {
        $('.js_admincp_search_app_group_settings').off('keyup').on('keyup', function () {
            var value = $(this).val(), result = [];
            $('.js_no_settings').remove();
            if (value.length >= 2) {
                result = admincpAppGroupSettings.filter(function(setting, index) {
                    return setting.title && setting.title.toLowerCase().search(value.toLowerCase()) !== -1;
                });
                $('.js_settings').removeClass('open').addClass('force_hidden');
                $('.js_setting_holder').hide();
                if (result.length) {
                    for (i = 0; i < result.length; ++i) {
                        var setting = result[i];
                        $('#settings_container').find('#' + setting.var_name).closest('.js_settings').addClass('open').removeClass('force_hidden');
                        $('#settings_container').find('.js_setting_holder[data-module="' + setting.module_id + '"]').show();
                    }
                } else {
                    $('#settings_container').append('<div class="h5 t_center js_no_settings">' + oTranslations['no_results'] + '</div>');
                }
            } else {
                $('.js_setting_holder').show();
                $('.js_settings').addClass('open').removeClass('force_hidden');
            }
        });
    }
}

$Behavior.admincp_stat_view_more = function(){
    if($('#admincp_stat').length){
        var containerStat = $('#admincp_stat .stats-me'),
            hStatlistOld = containerStat.find('.stat-item').outerHeight(),
            countStat = containerStat.find('.stat-item').length,
            hStatlistNew = Math.floor((countStat-1)/4) * (hStatlistOld + 20);
        if(!(containerStat.hasClass('full'))){
            containerStat.css('max-height',hStatlistOld);    
        }
        $('.js_admincp_stat_more').off('click').click(function () {
            containerStat.css('max-height',hStatlistNew);
            $(this).closest('.content.stats-me').addClass('full');
            $('#admincp_stat .stat-item.hide').removeClass('hide').addClass('less-item');
            $(this).addClass('hide');
            setTimeout(function(){ 
                containerStat.css('max-height','none');
             }, 600);
            
        });
        $('.js_admincp_stat_less').off('click').click(function () {
            $(this).addClass('hide');
            $('#admincp_stat .stat-item.less-item').addClass('hide');
            $('.js_admincp_stat_more').removeClass('hide');
            containerStat.css('max-height',hStatlistOld);
        });
    }
}

$Behavior.admincp_fox_news = function(){
    if($('#carousel-fox-news').length){
        $('#carousel-fox-news').carousel({
          interval: 5000,
          cycle: true
        }); 
    }
}

$Behavior.admincp_toggle_nav = function(){
    if (window.matchMedia('(max-width: 1024px)').matches) {
        $('body').addClass('collapse-nav-active');
        setCookie('admincp-toggle-nav-cookie', 'has-toggle-cookies');
        if($('#phpfox_store').length){
          $('#phpfox_store').css({
            width: $(window).width() + 20
          });
        }
    }
    $( window ).resize(function() {
        if (window.matchMedia('(max-width: 1024px)').matches) {
            $('body').addClass('collapse-nav-active');
            setCookie('admincp-toggle-nav-cookie', 'has-toggle-cookies');
            if($('#phpfox_store').length){
              $('#phpfox_store').css({
                width: $(window).width() + 20
              });
            }
        }
    });
    $('.js_admincp_toggle_nav_btn').off('click').click(function (){
        var menu_width = 200;
        $('body').toggleClass('collapse-nav-active');
        if (window.matchMedia('(max-width: 1024px)').matches) {
            $('.js_admincp_toggle_nav_content').css('opacity',1);
        }
        if($('body').hasClass('collapse-nav-active')){
          setCookie('admincp-toggle-nav-cookie', 'has-toggle-cookies');
          menu_width = -16;
        }else{
          setCookie('admincp-toggle-nav-cookie', '');
        }
        if($('#phpfox_store').length){
          $('#phpfox_store').css({
            width: $(window).width() - menu_width
          });
        }
    });
}

$Core.AdminCP.touchHandler = function(event) {
    var touch = event.changedTouches[0];
    var simulatedEvent = document.createEvent("MouseEvent");
        simulatedEvent.initMouseEvent({
        touchstart: "mousedown",
        touchmove: "mousemove",
        touchend: "mouseup"
    }[event.type], true, true, window, 1,
        touch.screenX, touch.screenY,
        touch.clientX, touch.clientY, false,
        false, false, false, 0, null);

    touch.target.dispatchEvent(simulatedEvent);
    event.preventDefault();
};

$Behavior.admincp_touch_handle = function(){
    var arraydrag = $('.ui-sortable .ui-sortable-handle,.drag_handle');
    for(var i = 0; i < arraydrag.length; i++) {
        arraydrag[i].addEventListener("touchstart", $Core.AdminCP.touchHandler, true);
        arraydrag[i].addEventListener("touchmove", $Core.AdminCP.touchHandler, true);
        arraydrag[i].addEventListener("touchend", $Core.AdminCP.touchHandler, true);
        arraydrag[i].addEventListener("touchcancel", $Core.AdminCP.touchHandler, true);
    }  
}

$Core.AdminCP.banFilters = {
    processCheckAll: function (ele, bIsCheckAll) {
        $(ele, $('#js_admincp_ban_filters_content')).prop('checked', bIsCheckAll);
    },
    processDeleteAllButton: function(){
        if($('.js_ban_checkbox:checked').length == 0)
        {
            $('#js_ban_filters_delete_selected').prop('disabled', true);
        }
        else if($('.js_ban_checkbox:checked').length > 0)
        {
            $('#js_ban_filters_delete_selected').prop('disabled', false);
        }
    }
};

$Behavior.admincp_ban_filters = function(){
    if($('#js_admincp_ban_filters_content').length)
    {
        $Core.AdminCP.banFilters.processDeleteAllButton();
        $('#js_ban_checkbox_all', $('#js_admincp_ban_filters_content')).off('click').on('click', function () {
            $Core.AdminCP.banFilters.processCheckAll('.js_ban_checkbox', $(this).prop('checked'));
            setTimeout(function(){
                $Core.AdminCP.banFilters.processDeleteAllButton();
            },10);
        });

        $('.js_ban_checkbox', $('#js_admincp_ban_filters_content')).off('click').on('click', function(){
            if($('.js_ban_checkbox:checked').length == 0 || ($('.js_ban_checkbox:checked').length < $('.js_ban_checkbox').length))
            {
                $Core.AdminCP.banFilters.processCheckAll('#js_ban_checkbox_all', false);
            }
            else if ($('.js_ban_checkbox:checked').length == $('.js_ban_checkbox').length)
            {
                $Core.AdminCP.banFilters.processCheckAll('#js_ban_checkbox_all', true);
            }
            setTimeout(function(){
                $Core.AdminCP.banFilters.processDeleteAllButton();
            },10);
        });
        $('#js_ban_filters_delete_selected', $('#js_admincp_ban_filters_content')).off('click').on('click', function () {
            $Core.jsConfirm({message: oTranslations['are_you_sure']}, function () {
                var sId = '';
                $('.js_ban_checkbox:checked', $('#js_admincp_ban_filters_content')).each(function(){
                    sId += $(this).data('id') + ',';
                });
                sId = trim(sId,',');
                $.ajaxCall('ban.massAction', 'type=' + $('#js_ban_filters_type', $('#js_admincp_ban_filters_content')).val() + '&id=' + sId);
            }, function () {
            });
        });
    }
}

$Core.AdminCP.processSettings = {
    aInitSettings: [],
    aChangedSettings: [],
    init: function() {
        $Core.AdminCP.processSettings.aInitSettings = $Core.AdminCP.processSettings.storeSettings();
        $('form .change_warning').on('change keydown', function () {
            var type = this.type || this.tagName.toLowerCase();
            var sName = $(this).attr('name');
            var sValue = '';
            if(type == 'radio')
            {
                sValue = $('[name="' + sName + '"]:checked').val();
            }
            else if(type == 'checkbox')
            {
                var tempValue = [];
                $('[name="' + sName + '"]:checked').each(function(){
                    tempValue.push($(this).val());
                });
                sValue = tempValue;
            }
            else
            {
                sValue = $(this).val();
            }

            var bNotChange = true;
            if(Array.isArray(sValue))
            {
                if(Array.isArray($Core.AdminCP.processSettings.aInitSettings[sName]) && sValue.toString() != ($Core.AdminCP.processSettings.aInitSettings[sName]).toString())
                {
                    bNotChange = false;
                    $Core.AdminCP.processSettings.aChangedSettings[sName] = true;
                }
            }
            else if (sValue != $Core.AdminCP.processSettings.aInitSettings[sName])
            {
                bNotChange = false;
                $Core.AdminCP.processSettings.aChangedSettings[sName] = true;
            }

            if(bNotChange)
            {
                delete $Core.AdminCP.processSettings.aChangedSettings[sName];
            }
            window.onbeforeunload = function (){
                if(!bNotChange)
                {
                    return bNotChange;
                }
                if(Object.keys($Core.AdminCP.processSettings.aChangedSettings).length)
                {
                    return false;
                }
            }

            // hide/show inputs
            if (typeof setting_group_class !== 'undefined' && setting_group_class) {
                $('.' + setting_group_class + '.is_option_class').each(function() {
                    var option_class = $(this).data('option-class').split('='),
                        s_key = option_class[0],
                        s_value = option_class[1],
                        i = $(this),
                        t = $('.__data_option_' + s_key + '');
                    if (t.length) {
                        if (t.val() == s_value) {
                            i.show();
                        } else {
                            i.hide();
                        }
                    }
                });
            }
        });
    },
    storeSettings: function() {
        var aSettings = [];
        $('form .change_warning').each(function(key, value){
            var sName = value.name;
            if(empty(aSettings[sName]))
            {
                var type = this.type || this.tagName.toLowerCase();

                if(type == 'radio')
                {
                    aSettings[sName] = $(this).prop('checked') ? value.value : '';
                }
                else if(type == 'checkbox')
                {
                    aSettings[sName] = $(this).prop('checked') ? [value.value] : [];
                }
                else
                {
                    aSettings[sName] = value.value;
                }
            }
            else if (Array.isArray(aSettings[sName]) && $(this).prop('checked'))
            {
                aSettings[sName].push(value.value);
            }
        });
        return aSettings;
    },
};
$Behavior.setting_warning = function () {
    if($('form .change_warning').length) {
        $Core.AdminCP.processSettings.init();
        $('.form-group-save-changes').off('click').on('click', function(){
            window.onbeforeunload = function (){
            }
        });
    }
}

$Core.AdminCP.processUsers = {
    isValidPage: false,
    sExportUrl: '',
    sImportUrl: '',
    sExportFilter: [],
    oImportFile: {},
    oXhr: null,
    sFilePath: '',
    bIsDropCheck: false,
    sFileName: '',
    aImportFields: {},
    iImportGroup: 0,
    sDoneCss: 'padding: 10px;color: #fff;margin: 0 0 8px 0;font-size: 14px;background: rgba(46, 204, 113, 0.8);',
    sTitleCss: 'font-size: 14px;font-weight: bold;',
    sDownloadCss: 'font-size: 16px; margin-top: 24px;',
    exportUsers: function(){
        if($Core.AdminCP.processUsers.isValidPage)
        {
            $('#js_user_export_btn').prop('disabled', true);
            $('.js_user_export_result').html('<div class="js_box_loader"><i class="fa fa-spin fa-spinner"></i></div>').removeClass('hide');
            var aData = $('#js_block_admincp_export_users').serializeArray();
            aData.push({name: 'filter_condition', value: $Core.AdminCP.processUsers.sExportFilter});

            $.ajax({
                type: 'POST',
                url: $Core.AdminCP.processUsers.sExportUrl,
                data: aData,
                timeout: 5 * 60 * 1000,
                dataType: 'json',
                success: function (response) {
                    var sNoticeMessage = '';
                    if(response.status == true)
                    {
                        var sDone = '<div style="' + $Core.AdminCP.processUsers.sTitleCss + '">' + oTranslations['process_message'] +':</div>';
                        sDone += '<div style="'+ $Core.AdminCP.processUsers.sDoneCss + '">' + response.message + '</div>';
                        var sError = '';
                        if(!empty(response.error))
                        {
                            sError += '<div style="' + $Core.AdminCP.processUsers.sTitleCss + '">' + oTranslations['error_message'] + ':</div>';
                            response.error.forEach(function(element) {
                                sError += '<div class="error_message">' + element + '</div>';
                            });
                        }
                        sNoticeMessage = sDone + sError;
                        if(response.download)
                        {
                            sNoticeMessage += '<a style="' + $Core.AdminCP.processUsers.sDownloadCss + ' " href="' + $Core.AdminCP.processUsers.sExportUrl + '?download=' + response.download + '" class="no_ajax_link">' + oTranslations['download'] + '</a>'
                        }
                    }
                    else
                    {
                        var sError = '<div style="' + $Core.AdminCP.processUsers.sTitleCss + '">' + oTranslations['error_message'] + ':</div>';
                        sError += '<div class="error_message">' + response.message + '</div>';
                        sNoticeMessage = sError;
                    }
                    $('.js_user_export_result').html(sNoticeMessage);
                    $('#js_user_export_btn').prop('disabled', false);
                }
            });
        }
    },
    processImportUser: function(oObj){
        $Core.AdminCP.processUsers.oXhr.open("POST", $Core.AdminCP.processUsers.sImportUrl, true);
        var headers = {
            "Accept": "application/json",
            "Cache-Control": "no-cache",
            "X-Requested-With": "XMLHttpRequest"
        };
        for (headerName in headers) {
            headerValue = headers[headerName];
            if (headerValue) {
                $Core.AdminCP.processUsers.oXhr.setRequestHeader(headerName, headerValue);
            }
        }

        var type = $(oObj).data('type');
        if(type == 'upload')
        {
            var oInput = $(oObj).closest('#js_user_admincp_block_import_user').find('input[type="file"]');
            if(oInput.length)
            {
                $Core.AdminCP.processUsers.oImportFile = oInput[0].files[0];
            }
            if(!empty($Core.AdminCP.processUsers.oImportFile))
            {
                var form_data = new FormData();
                form_data.append('import_user_file', $Core.AdminCP.processUsers.oImportFile);
                form_data.append('type', type);
                $Core.AdminCP.processUsers.oXhr.send(form_data);
                $('#js_check_file_message').removeClass('hide_it').html('<div class="js_box_content"><span class="js_box_loader"><i class="fa fa-spin fa-spinner"></i></span></div>');
                $(oObj).prop('disabled', true);
            }
            else
            {
                $('#js_check_file_message').html('<div class="alert alert-danger" style="font-size: 14px;">' + oTranslations['you_need_to_upload_file_first'] + '</div>').removeClass('hide_it');
            }
        }
        else if (type == 'start')
        {
            js_box_remove(oObj);
            tb_show('Information to import', $.ajaxBox('user.selectImportField', 'height=300&width=600&import_fields=' + JSON.stringify($Core.AdminCP.processUsers.aImportFields)));
        }
        else if (type == 'import')
        {
            var oForm = $(oObj).closest('form');
            var aData = oForm.serializeArray();

            var form_data = new FormData();

            $(aData).each(function(key, value) {
                form_data.append(value.name, value.value);
            });

            form_data.append('file_path', $Core.AdminCP.processUsers.sFilePath);
            form_data.append('file_name', $Core.AdminCP.processUsers.sFileName);
            form_data.append('type', type);
            $Core.AdminCP.processUsers.oXhr.send(form_data);
            $('#js_check_file_message').removeClass('hide_it').html('<div class="js_box_content"><span class="js_box_loader"><i class="fa fa-spin fa-spinner"></i></span></div>');
            $(oObj).prop('disabled', true);
        }
        else if (type == 'close')
        {
            js_box_remove(oObj);
        }
    },
    resetImportData: function()
    {
        $Core.AdminCP.processUsers.oImportFile = {};
        $Core.AdminCP.processUsers.sFilePath = '';
        $Core.AdminCP.processUsers.bIsDropCheck = false;
        $Core.AdminCP.processUsers.sFileName = '';
        $Core.AdminCP.processUsers.aImportFields = {};
        $Core.AdminCP.processUsers.iImportGroup = 0;
    },
    resetHistorySearchForm: function(oObj)
    {
        if($(oObj).closest('.js_import_history_content').length)
        {
            var oContent = $(oObj).closest('.js_import_history_content');
            var sCoreDateFormat = oContent.data('date-core-format');
            var sDefaultDateFormat = oContent.data('date-default-format');

            var aDate = explode('/', sDefaultDateFormat);

            $('#from_day').val(aDate[0]);
            $('#from_month').val(aDate[1]);
            $('#from_year').val(aDate[2]);

            $('#to_day').val(aDate[0]);
            $('#to_month').val(aDate[1]);
            $('#to_year').val(aDate[2]);

            oContent.find('input[name="js_from__datepicker"]').val(sCoreDateFormat);
            oContent.find('input[name="js_to__datepicker"]').val(sCoreDateFormat);

            $('#js_search_owner', oContent).val('');
            $('#js_search_status', oContent).val('');
        }
    }

}
$Behavior.import_export_users = function(){
    if((typeof isInAdmincpUserBrowse == 'boolean'))
    {
        $Core.AdminCP.processUsers.isValidPage = true;
        $Core.AdminCP.processUsers.sExportUrl = sExportUsersUrl;
        $Core.AdminCP.processUsers.sImportUrl = sImportUsersUrl;
        $Core.AdminCP.processUsers.sExportFilter = sUserExportFilter;
        $('input[name="import_user_file"]').on('click touchstart' , function(){
            $(this).val('');
            if($('#js_import_start_btn').prop('disabled'))
            {
                $('#js_import_start_btn').prop('disabled', false);
            }
            $Core.AdminCP.processUsers.resetImportData();
            $('#js_import_start_btn').html(oTranslations['upload']).data('type','upload');
            $('#js_check_file_message').addClass('hide_it').html('');
        });

        $('input[name="import_user_file"]').on('change', function(){

            if($('#js_import_start_btn').prop('disabled'))
            {
                $('#js_import_start_btn').prop('disabled', false);
            }
            $Core.AdminCP.processUsers.resetImportData();
            $('#js_import_start_btn').html(oTranslations['upload']).data('type','upload');
            $('#js_check_file_message').addClass('hide_it').html('');

        });

        if(!empty($Core.AdminCP.processUsers.sImportUrl) && $Core.AdminCP.processUsers.oXhr == null)
        {
            $Core.AdminCP.processUsers.oXhr = new XMLHttpRequest();
            $Core.AdminCP.processUsers.oXhr.onload = function(){
                if ($Core.AdminCP.processUsers.oXhr.readyState !== 4) {
                    return;
                }
                var response = $Core.AdminCP.processUsers.oXhr.responseText;
                try {
                    response = $.parseJSON(response);
                    if(response.status)
                    {
                        var next = response.next;
                        if(next == 'start')
                        {
                            $Core.AdminCP.processUsers.bIsDropCheck = response.drop_check;
                            $Core.AdminCP.processUsers.sFilePath = response.file_path;
                            $Core.AdminCP.processUsers.sFileName = response.file_name;
                            $Core.AdminCP.processUsers.aImportFields = response.import_fields;
                            $('#js_import_start_btn').html(oTranslations['start']).data('type','start');
                            $('#js_check_file_message').html( !$Core.AdminCP.processUsers.bIsDropCheck ? '<div style="padding: 10px; margin: 0 0 8px 0;font-size: 14px;' + (response.is_completed ? 'color: #fff; background: rgba(46, 204, 113, 0.8);' : 'background-color: #fffcee;') +'">' + response.message + '</div>' : '<div class="error_message">' + response.message + '</div>').removeClass('hide_it');
                            if($Core.AdminCP.processUsers.bIsDropCheck)
                            {
                                $('#js_import_start_btn').data('type', false);
                            }
                            else
                            {
                                $('#js_import_start_btn').prop('disabled', false);
                            }
                        }
                        else if (next == 'finish')
                        {
                            $Core.AdminCP.processUsers.resetImportData();
                            $('#js_check_file_message').removeClass('hide_it').html('<div style="padding: 10px; margin: 0 0 8px 0;font-size: 14px;color: #fff; background: rgba(46, 204, 113, 0.8);float: left; width: 100%;">' + response.message + '</div>');
                            $('#js_import_start_btn').html(oTranslations['close']).data('type', 'close').prop('disabled', false);
                        }
                    }
                    else
                    {
                        var sErrorMessage = '';
                        if(!empty(response.message))
                        {
                            sErrorMessage = '<div class="error_message">' + response.message + '</div>';
                        }
                        else if (!empty(response.error))
                        {
                            $.each(response.error, function( index, value ) {
                                sErrorMessage += '<div class="error_message">' + value + '</div>';
                            });
                        }

                        if(!empty(sErrorMessage))
                        {
                            $('#js_check_file_message').removeClass('hide_it').html(sErrorMessage);
                        }
                    }
                } catch (_error) {
                    console.log("Invalid JSON response from server.");
                }
            };
            $Core.AdminCP.processUsers.oXhr.onerror = function(){

            };
        }
    }
}

$Behavior.admincp_selectize_on_change = function () {
    if($('body.admincp-block-index').length && $('#m_connection').length) {
       $('#m_connection').off('change').on('change', function() {
           if($(this).val() != '') {
               this.form.submit();
           }
       });
    }
    if($('body.user-admincp-group-add').length && $('#module_id').length) {
        $('#module_id').off('change').on('change', function() {
            if($(this).val() != '') {
                this.form.submit();
            }
        });
    }
}

$Behavior.admincp_process_country = function(){
    $('.js_active_multiple_item').off('click').on('click', function(){
        var bIsChecked = false;
        if($(this).data('type') == 'btn') {
            bIsChecked = $('.js_active_item:checked').length == $('.js_active_item').length ? false : true;
            $(this).html(bIsChecked ? oTranslations['un_select_all'] : oTranslations['select_all']);
            $('input[type="checkbox"].js_active_multiple_item').prop('checked', bIsChecked);
        }
        else {
            bIsChecked = $(this).prop('checked');
            $('button.js_active_multiple_item').html(bIsChecked ? oTranslations['un_select_all'] : oTranslations['select_all']);
        }

        $('.js_active_item').prop('checked', bIsChecked);

        if(bIsChecked) {
            $('.js_count_selected_item').html($('.js_active_item:checked').length + ' ' + oTranslations['items_selected']).removeClass('hide_it');
        }
        else {
            $('.js_count_selected_item').html('').addClass('hide_it');
        }
    });
    $('.js_active_item').off('click').on('click', function(){
        var bIsAllUnselected = $('.js_active_item:checked').length == 0 ? true : false;
        if(bIsAllUnselected) {
            $('.js_active_multiple_item').prop('checked', $(this).prop('checked'));
            $('.js_count_selected_item').html('').addClass('hide_it');
        }
        else {
            $('.js_active_multiple_item').prop('checked', $('.js_active_item:checked').length == $('.js_active_item').length ? $(this).prop('checked') : false);
            $('.js_count_selected_item').html($('.js_active_item:checked').length + ' ' + oTranslations['items_selected']).removeClass('hide_it');
        }
        $('button.js_active_multiple_item').html($('.js_active_item:checked').length == $('.js_active_item').length ? oTranslations['un_select_all'] : oTranslations['select_all']);
    });
    $('.js_active_multiple_item_btn').off('click').on('click',function(){
        var sIso = '';
        $('.js_active_item:checked').each(function(){
            sIso += $(this).val() + ',';
        });
        sIso = trim(sIso, ',');
        if(!empty(sIso)) {
            $.ajaxCall('core.activateCountry','country_iso=' + sIso + '&active=' + $(this).data('type') + '&type=multiple');
        }
    });
}
