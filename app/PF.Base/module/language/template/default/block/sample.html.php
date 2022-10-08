<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="p_4">
	<div class="form-group row">
		<div class="col-md-3 text-right"><b>{_p var='php'}</b>:</div>
		<div class="col-md-9"><input type="text" class="form-control" name="php" value="_p('{$sCachePhrase}')" size="40" onclick="this.select();" /></div>
	</div>
	<div class="form-group row">
		<div class="col-md-3 text-right"><b>{_p var='php_single_quoted'}</b>:</div>
		<div class="col-md-9"><input type="text" class="form-control" name="php" value="' . _p('{$sCachePhrase}') . '" size="40" onclick="this.select();" /></div>
	</div>
	<div class="form-group row">
		<div class="col-md-3 text-right"><b>{_p var='php_double_quoted'}</b>:</div>
		<div class="col-md-9"><input type="text" class="form-control" name="php" value="&quot; . _p('{$sCachePhrase}') . &quot;" size="40" onclick="this.select();" /></div>
	</div>
	<div class="form-group row">
		<div class="col-md-3 text-right"><b>{_p var='html'}</b>:</div>
		<div class="col-md-9"><input type="text" class="form-control" name="html" value="{literal}{{/literal}_p var='{$sCachePhrase}'{literal}}{/literal}" size="40" onclick="this.select();" /></div>
	</div>
	<div class="form-group row">
		<div class="col-md-3 text-right"><b>{_p var='js'}</b>:</div>
		<div class="col-md-9"><input type="text" class="form-control" name="html" value="oTranslations['{$sCachePhrase}']" size="40" onclick="this.select();" /></div>
	</div>
	<div class="form-group row">
		<div class="col-md-3 text-right"><b>{_p var='text'}</b>:</div>
		<div class="col-md-9"><input type="text" class="form-control" name="html" value="{$sCachePhrase}" size="40" onclick="this.select();" /></div>
	</div>
</div>