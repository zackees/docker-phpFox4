if (typeof $Core.User == 'undefined') {
	$Core.User = { Spam: {} }
}

$Core.User.Spam = {
	iTotalAnswers: 0,

	addAnswer: function () {
		this.iTotalAnswers++
		var oTpl = $('#tpl_answer .valid_answer').clone()
		$('#div_add_answer').before(oTpl)
		$('.div_add_answers .valid_answer').show()
	},

	deleteAnswer: function (oObj) {
		$Core.jsConfirm({}, function () {
			$(oObj).parents('.valid_answer').remove()
		}, function () {})
	},

	/* Sets a hidden input so the process service knows when to remove the previous image, this feature (deleting an image) may lead to the following situations
 * 	1- Admin deletes the image because he doesnt want any image to be shown
 * 	2- Admin deletes the image because he wants a new image to be shown (deletes then chooses a new one)
 * 	3- Admin chooses a new image but does not delete the previous image
 */
	deleteImage: function () {
		$('#div_edit_image').
			html(
				'<p id="p_will_not_show_image">This question will not show an image</p>')
	},

	/* When editing a question the admin can choose to change the image for that question, this function couples deleteImage() */
	fileChanged: function () {
		if ($('#img_previous_image').length > 0) {
			/* Since the image has changed, let the admin know that he does not need to click the delete previous image button */
			$('#div_edit_image').
				html(
					'Your previous image will be replaced with the one you have selected')
		}
		if ($('#p_will_not_show_image').length > 0) {
			$('#div_edit_image').
				html(
					'<p id="p_will_replace_image">This question will use the image you have just selected</p>')
		}
	},

	deleteQuestion: function (id) {
		var iQuestionId = parseInt(id.replace('img_delete_question_', ''))

		$Core.jsConfirm({}, function () {
			$.ajaxCall('user.deleteSpamQuestion', 'iQuestionId=' + iQuestionId)
			$('#img_delete_question_' + iQuestionId).
				attr({ 'onclick': '', 'src': oJsImages['ajax_small'] }).
				unbind('click')
		}, function () {})
	},

	deleteQuestions: function(obj) {
		let _this = $(obj),
			btn = $('#table_hover_action_holder .js_admincp_spam_question_delete_btn'),
			btnText = btn.length ? btn.text() : null,
			textWidth = btn.length ? btn.width() : null,
			textHeight = btn.length ? btn.height() : null;

		if (_this.length) {
			if (btn.length) {
				btn.prop('disabled', true);
				btn.html('<span class="js_box_loader" style="width: ' + textWidth + 'px; height: ' + textHeight + 'px; display: flex; justify-content: center;"><i class="fa fa-spin fa-spinner" style="font-size: 16px;"></i></span>');
			}
			$.fn.ajaxCall('user.deleteMultiSpamQuestions', _this.serialize(), null, null, function() {
				if (btn.length) {
					btn.prop('disabled', false);
					btn.text(btnText);
				}
			});
		}

		return false;
	},
}