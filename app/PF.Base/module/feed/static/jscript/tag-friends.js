/* Implements Google Places into the Feed to achieve a Check-In, it also checks for existing Pages first */
if (!$Core.hasOwnProperty('FeedTag') || typeof $Core.FeedTag !== "object") {
  var buildingTagCache = false;
  $Core.FeedTag =
      {
        sButtonId: 'js_btn_display_with_friend',

        sSelector: 'js_input_tagging',

        iFeedId: 0,

        oldVal: '',

        bIsLoadingFriend: false,

        customSelector: function () {
          return '_' + Math.random().toString(36).substr(2, 9);
        },
        init: function () {
          var startInit = function () {
            var btnTagFriend = $('.' + $Core.FeedTag.sButtonId);
            if (btnTagFriend.closest('.activity_feed_form').prop('built')) {
              return false;
            }
            btnTagFriend.parents('.activity_feed_form').prop('built', true);
            btnTagFriend.each(function () {
              if ($(this).prop('built')) {
                return false;
              }
              $(this).prop('built', true);
              $(this).click(function () {
                var findDiv = '.activity_feed_form:not(.feed_detached)',
                    findChild = '.js_feed_compose_extra.js_feed_compose_tagging',
                    parentsDiv = $(this).parents(findDiv).find(findChild);
                var visible = parentsDiv.is(":visible");
                if (visible) {
                  $(this).removeClass('is_active');
                  parentsDiv.hide('fast');
                } else {
                  $('.js_btn_display_with_schedule').removeClass('is_active');
                  $(this).addClass('is_active');
                  $(' .feed_compose_extra').hide('fast');
                  $(findDiv + ' #js_location_input').hide('fast');
                  parentsDiv.show('fast');
                  parentsDiv.find('.js_input_tagging').focus().trigger('click');
                }
                return false;
              });
            });
            var increment = 0;
            $('.' + $Core.FeedTag.sSelector).each(function () {
              increment++;
              var t = $(this), selector = '_custom_tag_' + $Core.FeedTag.customSelector() + '_' + increment;
              if (t.data('selector')) {
                t.removeClass(t.data('selector').replace('.', ''));
              }
              t.addClass(selector);
              t.data('selector', '.' + selector);
            });

            $('.' + $Core.FeedTag.sSelector).keyup(function (e) {
              var t = $(this),
                  sInput = t.val();
              if (e.keyCode == 8) {
                if ($Core.FeedTag.oldVal == '') {
                  $(this).closest('.js_feed_compose_tagging').find('.js_feed_tagged_items .feed_tagged_item:last-child a').trigger('click');
                }
              }
              $Core.FeedTag.oldVal = sInput;
              if (sInput == "") {
                $($(this).data('selector')).siblings('.chooseFriend').remove();
                return;
              }
              /* loop through friends */
              var aFoundTagFriends = [],
                  sOut = '',
                  aTaggedFriends = [],
                  inputTagged = $('#feed_input_tagged_' + $Core.FeedTag.iFeedId);

              if (typeof inputTagged !== undefined && inputTagged.val()) {
                aTaggedFriends = inputTagged.val().split(',');
              }

              for (var i in $Cache.friends) {
                if ($Cache.friends[i]['full_name'].toLowerCase().indexOf(sInput.toLowerCase()) >= 0
                    && aTaggedFriends.indexOf($Cache.friends[i]['user_id']) === -1
                    && (!$Cache.hasOwnProperty('disallowedTaggingFriends') || $Cache.disallowedTaggingFriends.indexOf($Cache.friends[i]['user_id']) === -1)
                ) {
                  var sNewInput = sInput.replace(/\'/g, '&#39;').replace(/\"/g, '&#34;');
                  sToReplace = sNewInput;
                  aFoundTagFriends.push({
                    user_id: $Cache.friends[i]['user_id'],
                    full_name: $Cache.friends[i]['full_name'],
                    user_image: $Cache.friends[i]['user_image']
                  });
                  if (($Cache.friends[i]['user_image'].substr(0, 5) == 'http:') || ($Cache.friends[i]['user_image'].substr(0, 6) == 'https:')) {
                    PF.event.trigger('urer_image_url', $Cache.friends[i]);

                    $Cache.friends[i]['user_image'] = '<img src="' + $Cache.friends[i]['user_image'] + '" class="_image_32 image_deferred">';
                  }

                  sOut += '<div class="tagFriendChooser" onclick="$Core.FeedTag.selectElement($(this));" data-name="' + $Cache.friends[i]['full_name'] + '" data-id="' + $Cache.friends[i]['user_id'] + '" data-selector="' + $(this).data('selector') + '"><div class="tagFriendChooserImage">' + $Cache.friends[i]['user_image'] + '</div><span>' + (($Cache.friends[i]['full_name'].length > 25) ? ($Cache.friends[i]['full_name'].substr(0, 25) + '...') : $Cache.friends[i]['full_name']) + '</span></div>';
                  sOut = sOut.replace("\n", '').replace("\r", '');
                }
              }
              $($(this).data('selector')).siblings('.chooseFriend').remove();
              if (!empty(sOut)) {
                $($(this).data('selector')).after('<div class="chooseFriend style="width: ' + $(this).width() + 'px;">' + sOut + '</div>');
                $('.chooseFriend').mCustomScrollbar({
                  theme: "minimal-dark",
                }).addClass('dont-unbind-children');
              }
            });
            var inputTagged = $('#feed_input_tagged_' + $Core.FeedTag.iFeedId);
            if (!inputTagged.length) return;
            var editTaggedVal = inputTagged.val(),
                sSelector = inputTagged.closest('.js_feed_compose_tagging').find('.js_input_tagging').data('selector'),
                taggedItem = $(sSelector).closest('.js_feed_compose_tagging').find('.js_feed_tagged_items'),
                taggedVal = $.map(editTaggedVal.split(','), function (v) {
                  return v === "" ? null : parseInt(v);
                });
            if (!taggedItem.hasClass('built')) {
              for (var i = 0; i < taggedVal.length; i++) {
                var oUser = $Core.FeedTag.getFriendObjByAttr('user_id', taggedVal[i]);
                if (oUser) {
                  taggedItem.append('<span class="feed_tagged_item" data-id="' + oUser['user_id'] + '">' + oUser['full_name'] + '<a href="javascript:void(0)" data-id="' + oUser['user_id'] + '" onclick="$Core.FeedTag.removeTagged($(this));"><i class="fa fa-times" aria-hidden="true"></i></a></span>');
                }
              }
              taggedItem.addClass('built');
            }
            $Core.FeedTag.previewTagged(taggedVal);
          }

          if ($('.' + $Core.FeedTag.sSelector).length && !buildingTagCache && (typeof $Cache == 'undefined' || typeof $Cache.friends == 'undefined')) {
            buildingTagCache = true;
            $Core.FeedTag.bIsLoadingFriend = true;
            $('.' + $Core.FeedTag.sSelector).ajaxCall('friend.buildCache', '', true, 'GET', function () {
              $Core.FeedTag.bIsLoadingFriend = false;
              startInit();
            });
          } else if (!$Core.FeedTag.bIsLoadingFriend) {
            startInit();
          }
        },
        selectElement: function (item, sName, iId, sSelector, sContainer) {
          $Core.FeedTag.resetFeedId();
          var selectedTagText = sName ? sName : item.data("name"),
              selectedTagId = iId ? iId : parseInt(item.data("id")),
              selectorId = sSelector ? sSelector : item.data('selector'),
              $container = sContainer ? sContainer : item.closest('.chooseFriend'),
              taggedItems = $container.parents('.js_feed_compose_tagging').find('.js_feed_tagged_items'),
              taggedInput = $('#feed_input_tagged_' + $Core.FeedTag.iFeedId);
          var taggedValues = $.map(taggedInput.val().split(','), function (v) {
            return v === "" ? null : parseInt(v);
          });
          if (taggedValues.indexOf(selectedTagId) >= 0) {
            $(selectorId).siblings('.chooseFriend').remove();
            return;
          }
          taggedValues.push(selectedTagId);
          taggedItems.append('<span class="feed_tagged_item" data-id="' + selectedTagId + '">' + selectedTagText + '<a href="javascript:void(0)" data-id="' + selectedTagId + '" onclick="$Core.FeedTag.removeTagged($(this));"><i class="fa fa-times" aria-hidden="true"></i></a></span>');
          $container.parent().find('.js_input_tagging').val('').focus();
          $container.hide();
          $Core.FeedTag.previewTagged(taggedValues);
        },
        previewTagged: function (taggedValues) {
          $Core.FeedTag.resetFeedId();
          var friend, friend_0, friend_1, sTagged_0, sTagged_1, sTooltips,
              findDiv = '.activity_feed_form:not(.feed_detached)',
              taggedInput = $('#feed_input_tagged_' + $Core.FeedTag.iFeedId),
              tagReview = taggedInput.closest(findDiv).find('.js_tagged_review');
          if (taggedValues.length) {
            tagReview.addClass('tagged_review');
            if ($sCurrentForm && ['global_attachment_photo', 'custom'].indexOf($sCurrentForm) !== -1
                && !$('#js_activity_feed_edit_form').length) {
              $('.activity_feed_form_button_status_info').show();
            }
          }
          for (var i = 0; i < taggedValues.length; i++) {
            friend = $Core.FeedTag.getFriendObjByAttr('user_id', taggedValues[i]);
            if (!friend) {
              taggedValues.splice(i, 1);
            }
          }
          taggedInput.val(taggedValues.toString()).trigger('change');
          if (taggedValues.length == 1) {
            friend_0 = $Core.FeedTag.getFriendObjByAttr('user_id', taggedValues[0]);
            sTagged_0 = '<a href="javascript:void(0)" onclick="$(\'#btn_display_with_friend\').trigger(\'click\');">' + friend_0['full_name'] + '</a>';
            tagReview.html(oTranslations['with_name'].replace('{name}', sTagged_0)).show();
          } else if (taggedValues.length == 2) {
            friend_0 = $Core.FeedTag.getFriendObjByAttr('user_id', taggedValues[0]);
            sTagged_0 = '<a href="javascript:void(0)" onclick="">' + friend_0['full_name'] + '</a>';
            friend_1 = $Core.FeedTag.getFriendObjByAttr('user_id', taggedValues[1]);
            sTagged_1 = '<a href="javascript:void(0)" onclick="$(\'#btn_display_with_friend\').trigger(\'click\');">' + friend_1['full_name'] + '</a>';
            tagReview.html(oTranslations['with_name_and_name'].replace('{name_0}', sTagged_0).replace('{name_1}', sTagged_1)).show();
          } else if (taggedValues.length > 2) {
            friend_0 = $Core.FeedTag.getFriendObjByAttr('user_id', taggedValues[0]);
            sTagged_0 = '<a href="javascript:void(0)" onclick="$(\'#btn_display_with_friend\').trigger(\'click\');">' + friend_0['full_name'] + '</a>';
            sTagged_1 = '<a href="#" onclick="$(\'#btn_display_with_friend\').trigger(\'click\'); return false">' + oTranslations['number_others'].replace('{number}', taggedValues.length - 1) + '</a>';
            tagReview.html(oTranslations['with_name_and_name'].replace('{name_0}', sTagged_0).replace('{name_1}', sTagged_1)).show();
          } else {
            tagReview.html('').hide();
          }
        },
        removeTagged: function (item) {
          $Core.FeedTag.resetFeedId();
          var id = item.data("id");
          if (typeof id == 'undefined')
            return;
          var taggedInput = $('#feed_input_tagged_' + $Core.FeedTag.iFeedId);
          var taggedValues = $.map(taggedInput.val().split(','), function (v) {
            return v === "" ? null : v;
          });
          taggedValues.splice(taggedValues.indexOf(id.toString()), 1);
          item.closest('.feed_tagged_item').remove();
          $Core.FeedTag.previewTagged(taggedValues);
        },
        getFriendObjByAttr: function (att, value) {
          for (var i in $Cache.friends) {
            if ($Cache.friends[i][att] == value)
              return $Cache.friends[i];
          }
          return null;
        },
        resetFeedId: function () {
          if (!$('#feed_input_tagged_' + $Core.FeedTag.iFeedId).length) {
            $Core.FeedTag.iFeedId = 0;
          }
        }
      }
}
