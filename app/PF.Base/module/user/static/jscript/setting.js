var sSetTimeZone = null;

function findTZFromLocation(bSkip)
{
	if (sSetTimeZone != undefined && bSkip != true)
	{
		var iIndex = 0;
		$('select#time_zone option').each(function(iIndex){

			if ($(this).val() == sSetTimeZone)
			{
				$('select#time_zone').attr('selectedIndex', iIndex);
				return;
			}
			iIndex++;
		});
	}
	else
	{
		// get the selected value in the location
		var sISO = $('select#country_iso option:selected').text();
		// loop through the available time zones
		var bShow = true;
		$('select#time_zone option').each(function(iIndex){
			if ((-1) < $(this).text().replace(' ', '_').indexOf(sISO.replace(' ', '_')))
			{
				// select this one
				$('select#time_zone').attr('selectedIndex', iIndex);
				bShow = false;
				return false;
			}
			return true;
		});
		if (bShow)
		{
			$('select#time_zone').attr('selectedIndex', 0);
		}
	}
}

$Ready( function () {
	findTZFromLocation();
	$('select#country_iso').on('change', function () {
		findTZFromLocation(true)
	});
	$('[name="val[two_step_verification]"]').off('change').on('change', function () {
		var selected = $('[name="val[two_step_verification]"]:checked'), selectedVal = selected.val(),
		currentSetting = $('[name="val[current_two_step_verification]"]').val();
		if (selectedVal !== currentSetting) {
			if (currentSetting === '1') {
				$('#js_warning_disable_tsv').show();
			}
			$('#js_two_step_confirm_password').show();
		} else {
			$('#js_warning_disable_tsv').hide();
			$('#js_two_step_confirm_password').hide();
		}
	});
	$('#js_confirm_change_tsv').off('click').on('click', function () {
		var selected = $('[name="val[two_step_verification]"]:checked'), selectedVal = selected.val();
		$(this).attr('disabled', true).addClass('disabled');
		if (selectedVal === '1') {
			$(this).ajaxCall('user.enableTwoStepVerification', $.param({
				password: $('[name="val[two_step_confirm_password]"]').val(),
				is_validate: 1
			}));

		} else {
			$(this).ajaxCall('user.disableTwoStepVerification', $.param({
				password: $('[name="val[two_step_confirm_password]"]').val(),
				is_validate: 1
			}), true, 'POST');
		}
	})
});

