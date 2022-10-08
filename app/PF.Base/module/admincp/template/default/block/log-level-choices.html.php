<label>{_p var="log_level"}</label>
<div>
	<label style="font-weight: normal !important;">
		<input type="radio"  value="100" name="val[level]" {if $aForms.level == 100 || !$aForms.level}checked{/if} />
		&nbsp; {_p var="log_debug_desc"}
	</label>
</div>
<div>
	<label style="font-weight: normal !important;">
		<input type="radio" value="200" name="val[level]" {if $aForms.level == 200}checked{/if} />
		&nbsp; {_p var="log_info_desc"}
	</label>
</div>
<div>
	<label style="font-weight: normal !important;">
		<input type="radio" value="250" name="val[level]" {if $aForms.level == 250}checked{/if} />
		&nbsp; {_p var="log_notice_desc"}
	</label>
</div>
<div>
	<label style="font-weight: normal !important;">
		<input type="radio" value="300" name="val[level]" {if $aForms.level == 300}checked{/if} />
		&nbsp; {_p var="log_warning_desc"}
	</label>
</div>
<div>
	<label style="font-weight: normal !important;">
		<input type="radio" value="400" name="val[level]" {if $aForms.level == 400}checked{/if} />
		&nbsp; {_p var="log_error_desc"}
	</label>
</div>
<div>
	<label style="font-weight: normal !important;">
		<input type="radio" value="500" name="val[level]" {if $aForms.level == 500}checked{/if} />
		&nbsp; {_p var="log_critical_desc"}
	</label>
</div>
<div>
	<label style="font-weight: normal !important;">
		<input type="radio" value="550" name="val[level]" {if $aForms.level == 550}checked{/if} />
		&nbsp; {_p var="log_alert_desc"}
	</label>
</div>
<div>
	<label style="font-weight: normal !important;">
		<input type="radio" value="600" name="val[level]" {if $aForms.level == 600}checked{/if} />
		&nbsp; {_p var="log_emergency_desc"}
	</label>
</div>