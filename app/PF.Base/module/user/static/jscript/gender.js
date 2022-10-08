$Core.userGender = {
    elementID: 'gender',
    aUserGenderCustom: {},
    aGenderCustomTemp: [],
    init: function () {
        var element = $('#' + this.elementID), form = element.closest('form');
        if ($.isEmptyObject(this.aUserGenderCustom) && typeof aUserGenderCustom !== 'undefined') {
            this.aUserGenderCustom = aUserGenderCustom;
        }
        var oOption = {
            value: "custom",
            text: oTranslations['custom']
        };

        if (typeof bIsCustomGender === 'undefined') {
            bIsCustomGender = false;
        }

        if (bIsCustomGender) {
            oOption['selected'] = true;
        }

        if (!$('option[value="custom"]', element).length) {
            element.append($('<option>', oOption));
        }

        if (!form.find('#js_add_custom_gender_content').length) {
            var oContent = $('<div>', {
                id: 'js_add_custom_gender_content',
                class: (bIsCustomGender ? '' : 'hide')
            });
            var oSearchContent = $('<div>', {
                id: "js_add_custom_gender",
                class: "form-control clearfix"
            });
            var oCustomTag = $('<span>', {
                id: "js_custom_gender_tag"
            });
            var oBorder = $('<div>', {
                class: "item-input-gender-wrapper"
            });
            var oAddGenderCustomInput = $('<input/>').attr({
                type: 'text',
                id: 'js_add_custom_gender_input',
                class: 'form-control ',
                autocomplete: 'off'
            });
            var oClick = $('<div>', {
                id: "js_click_custom_gender",
                class: "custom-gender-list hide"
            });

            oSearchContent.appendTo(oContent);
            oCustomTag.appendTo(oSearchContent);
            oAddGenderCustomInput.appendTo(oBorder);
            oClick.appendTo(oBorder);
            oBorder.appendTo(oSearchContent);
            oContent.insertAfter(element);
        }

        element.off('change').on('change', function () {
            if ($(this).val() !== "custom") {
                $('#js_add_custom_gender_content').addClass('hide');
            }
            else {
                $('#js_add_custom_gender_content').removeClass('hide');
            }
        });

        $('#js_add_custom_gender_input').off('keyup').keyup(function (e) {
            var sContent = trim($(this).val());
            if (sContent.length) {
                $('#js_click_custom_gender').html('<div class="item-gender-custom">' + $Core.htmlEntityEncode(sContent) + '</div>').removeClass('hide');
            }
            else {
                $('#js_click_custom_gender').html('').addClass('hide');
            }
        });

        $('#js_click_custom_gender').off('click').click(function () {
            var sContent = $('#js_add_custom_gender_input').val();
            if (typeof sContent === "string") {
                sContent = $Core.htmlEntityEncode(sContent).trim();
            }
            $('#js_click_custom_gender').html('').addClass('hide');
            $('#js_add_custom_gender_input').val('');
            var sContentLower = sContent.toLowerCase();
            if ($.inArray(sContentLower, $Core.userGender.aGenderCustomTemp) < 0) {
                sContent = sContent.replace(new RegExp('"', 'g'), '&quot;');
                var sSpan = '<span class="js_custom_gender_item"><div class="item-gender-title">' + sContent + '</div><a role="button" class="" title="Remove" data-content="' + sContentLower + '" onclick="$Core.userGender.removeTag(this);"><i class="ico ico-close"></i></a><input type="hidden" name="val[custom_gender][]" value="' + sContent + '"></span>';
                $('#js_custom_gender_tag').append(sSpan);
                $Core.userGender.aGenderCustomTemp.push(sContentLower);
            }
            $('#js_add_custom_gender_input').focus();
        });

        this.genderInputExpand();
        $('#js_add_custom_gender').off('click').click(function () {
            $(this).find('#js_add_custom_gender_input').focus();
        });

        if (!$.isEmptyObject(this.aUserGenderCustom) && !$('#js_custom_gender_tag').prop('built')) {
            this.initTag();
        }
    },
    removeTag: function (oObj) {
        if ($(oObj).length) {
            this.aGenderCustomTemp.splice($.inArray($(oObj).data('content'), this.aGenderCustomTemp), 1);
            $(oObj).closest('.js_custom_gender_item').remove();
        }
    },
    initTag: function () {
        $('#js_custom_gender_tag').prop('built', true);
        $(this.aUserGenderCustom).each(function (index, value) {
            $Core.userGender.aGenderCustomTemp[index] = value.toLowerCase(); // update all to lowercase to check exists
            value = value.replace(new RegExp('"', 'g'), '&quot;');
            var sSpan = '<span class="js_custom_gender_item"><div class="item-gender-title">' + value + '</div><a role="button" class="" title="Remove" onclick="$Core.userGender.removeTag(this);"><i class="ico ico-close"></i></a><input type="hidden" name="val[custom_gender][]" value="' + value + '"></span>';
            $('#js_custom_gender_tag').append(sSpan);
        });
    },
    closeCustomTag: function () {
        $('#js_add_custom_gender_content').addClass('hide');
        $('#js_custom_gender_tag').html('');
    },
    genderInputExpand: function () {
        $.fn.textWidth = function (text, font) {
            if (!$.fn.textWidth.fakeEl) $.fn.textWidth.fakeEl = $('<span>').hide().appendTo(document.body);
            $.fn.textWidth.fakeEl.text(text || this.val() || this.text() || this.attr('placeholder')).css('font', font || this.css('font'));
            return $.fn.textWidth.fakeEl.width();
        };

        $('#js_add_custom_gender_input').on('input', function () {
            var inputWidth = $(this).textWidth();
            $(this).css({
                width: inputWidth
            })
        }).trigger('input');
    }
}

$Behavior.user_gender = function () {
    if (typeof oParams.allowCustomGender === 'undefined' || !oParams.allowCustomGender) {
        return;
    }
    if (!$('#' + $Core.userGender.elementID).length) {
        return;
    }
    $Core.userGender.init();
}