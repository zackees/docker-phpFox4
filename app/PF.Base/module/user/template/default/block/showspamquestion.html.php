<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
{foreach from=$aQuestions item=aQuestion}
	<div class="table form-group">
		<div class="table_left">
			{required}{$aQuestion.question_phrase|convert}:
		</div>
		<div class="table_right">
			{if isset($aQuestion.image_path) && !empty($aQuestion.image_path)}
                {img server_id=$aQuestion.server_id path='user.url_user_spam' file=$aQuestion.image_path}
            {/if}
			<div {if isset($aQuestion.image_path) && !empty($aQuestion.image_path)}class="m_top_15"{/if}>
				<input type="text" name="val[spam][{$aQuestion.question_id}]" value="" placeholder="{_p var='your_answer'}" />
                {if !empty($aQuestion.case_sensitive)}
                    <div class="help-block">{_p var='this_question_will_check_answer_in_a_case_sensitive'}</div>
                {/if}
			</div>
		</div>
	</div>
{/foreach}