
function admincp_close_search_settings(){
    $('.admincp_search_settings').removeClass('is_active');
    $('.admincp_search_settings_results').html('').addClass('hide');
    $('.admincp_search_settings input').val('');
}

$Behavior.adminMenuClick = function()
{
	var s = $('#phpfox_store_load');
	if (s.length && !$('#phpfox_store').length) {
		var url = oParams.sStoreUrl;
		if (s.data('url')) {
			url = s.data('url');
		}

		$('body').prepend('<iframe src="' + url + $('#phpfox_store_load').data('load') + '?iframe-mode=' + $('#phpfox_store_load').data('token') + '" id="phpfox_store"></iframe>');
		 var menu_width = 200;
        if($('body').hasClass('collapse-nav-active')){
          menu_width = -16;
        }
		$('#phpfox_store').addClass('built').css({
			width: $(window).width() - menu_width,
			height: $(window).height() - 60
		});
	}

	if ($('.phpfox-product').length) {
		$('.phpfox-product').each(function() {
			var t = $(this);

			var url = oParams.sStoreUrl + 'product/' + t.data('internal-id') + '/';
			$.ajax({
				url: url + 'view.json',
				success: function(e) {
					if (PF.tools.versionCompare(e.version, t.data('version'), '>')) {
						$('.am_top').append('<div class="upgrade-product"><a href="' + getParam('sJsHome') + 'admincp/store/?upgrade=' + t.data('internal-id') + '">Upgrade Product</a></div>');
					}
				}
			});
		});
	}

	$('.js-acp-header-section.ajax_menu ul li a').click(function() {
		if ($(this).hasClass('no_ajax')) return;
		if ($('.active_app').length) {
			var t = $(this);
			if (t.attr('href').substr(0, 1) == '#') {
				$('#custom-app-content, ._is_app_settings, ._is_user_group_settings').hide();

				$('.js-acp-header-section ul li a.active').removeClass('active');
				t.addClass('active');
				$('#app-custom-holder').hide();
				$('#app-content-holder').show();
				switch (t.attr('href')) {
					case '#settings':
						$('._is_app_settings').show();
						break;
					case '#user_group_settings':
						$('._is_user_group_settings').show();
						break;
					default:
						$('#custom-app-content').show();
						break;
				}
				$Behavior.buildSettingControl();
				return false;
			}
			var holder =$('#app-custom-holder');

			$('.js-acp-header-section ul li a.active').removeClass('active');
			t.addClass('active');

			if(!holder.length){
				$('#site_content').html('');
                holder = $('<div id="app-custom-holder"></div>').appendTo('#site_content');
            }

            holder.hide();
            holder.html('<i class="fa fa-spin fa-circle-o-notch"></i>').show();

			$.ajax({
				url: $(this).attr('href'),
				contentType: 'application/json',
				success: function(e) {
					// support redirect
					if(typeof e == 'string' && /^window.location.href/.test(e)){
						eval(e);
					}
                    holder.html(e.content).show();
					$Core.loadInit();
				}
			});

			return false;
		}
	});

	if ($('#app-custom-holder').length > 0 && empty($('#app-custom-holder').html())) {
        $('.js-acp-header-section ul li:first a').trigger('click');
	}

	var storeFeatured = $('.phpfox_store_featured');
	if (storeFeatured.length && !storeFeatured.hasClass('is_built')) {
		var parentUrl = storeFeatured.data('parent');
		var url = oParams.sStoreUrl + 'featured';

		storeFeatured.addClass('is_built');
		$.ajax({
			url: url,
			data: 'v=1&type=' + storeFeatured.data('type'),
			success: function(e) {
				var html = '', className = 'admincp_apps', articleImage = '', icon = '';
				if (typeof(e) == 'object') {
					switch (storeFeatured.data('type')) {
						case 'themes':
							className = 'themes';
							break;
					}
				}

				html += '<div class="' + className + '">';
				for (var i in e) {
					var t = e[i];

					icon = '';
					articleImage = '';
					if (typeof(e) == 'object') {
						switch (storeFeatured.data('type')) {
							case 'themes':
								articleImage = ' style="background-image:url(' + t.icon + ')"';
								icon = '';
								break;
							case 'apps':
							case 'language':
								icon = '<div class="app_icons image_load" data-src="' + t.icon + '"></div>';
								break;
						}
					}
					html += '<article' + articleImage + '><h1><a href="' + parentUrl + '&open=' + encodeURIComponent(t.url) + '">' + icon + '<span>' + t.name + '</span></a></h1></article>';
				}
				html += '</div>';

				storeFeatured.html(html);
				$Core.loadInit();
			}
		});
	}

	var options = {
			keys: ['title'],
			includeScore: false,
			sort: true,
            threshold: 0.6,
            location: 0,
            distance: 100,
            minMatchCharLength: 2,
            maxPatternLength: 100
		},
		fuse = new Fuse(admincpSettings, options);

	$('.admincp_search_settings span.remove').on('click',function() {
		admincp_close_search_settings();
	});

  $('.admincp_search_settings input').on('keyup',function(e) {
    var t = $(this),
      value = t.val(),
      word = value.split(' '),
      result = fuse.search(value),
      html = '',
      mainOutput = $('.admincp_search_settings_results');

    var words = jQuery.grep(word, function(w, i) {
      return w !== '';
    });

    words = jQuery.map(words, function(word, i) {
      return word.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
    });

    var pattern = '(' + words.join('|') + ')';
    var re = new RegExp(pattern, 'gi');
    var i;

    if (value.length <= 1) {
      $('.admincp_search_settings').removeClass('is_active');
      mainOutput.html(html).addClass('hide');
      return;
    }

    if (result) {
      for (i = 0; i < result.length && i < 20; ++i) {
        var term = result[i],
          title = result[i].title.replace(re, '<mark>$1</mark>');

        html += '<a href="' + term.link +
          '" onclick="admincp_close_search_settings()">' + title + '<span>' +
          term.category + '</span></a>';
      }

      $('.admincp_search_settings').addClass('is_active');
      mainOutput.html(html).removeClass('hide');
    }
  });
	
	$('.main_menu_link').on('click',function(){
		
		if ($(this).attr('href') === '#') {
		
			if ($(this).hasClass('active')){
				$(this).parent().find('.main_sub_menu:first').hide();
				$(this).removeClass('active');
				bIsAdminMenuClickSet = false;
			}
			else
			{				
				$('.main_sub_menu').hide();
				$('.main_menu_link').removeClass('active');
				if (bIsAdminMenuClickSet) {
					$(this).parent().find('.main_sub_menu:first').show();
				}
				else {
					$(this).parent().find('.main_sub_menu:first').show();
				}				
				$(this).addClass('active');
				
				if (bIsAdminMenuClickSet === false) {
					bIsAdminMenuClickSet = true;
				}
			}
			
			return false;
		}
	});
};

function onChangeUserGroupSettings(obj) {
	if ($(obj).closest('#app-custom-holder').length > 0) {
		var holder = $(obj).closest('#app-custom-holder');
        holder.html('<i class="fa fa-spin fa-circle-o-notch"></i>').show();
		var form = $(obj).closest('form');
        $.ajax({
            url: form.data('url'),
			type: 'get',
			data: form.serialize(),
            contentType: 'application/json',
            success: function(e) {
                // support redirect
                if(typeof e == 'string' && /^window.location.href/.test(e)){
                    eval(e);
                }
                holder.html(e.content).show();
                $Core.loadInit();
            }
        });
	}
	else {
		$(obj).closest('form').submit();
	}
}