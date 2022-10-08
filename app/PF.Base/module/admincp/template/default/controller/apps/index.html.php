<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if isset($vendorCreated)}
	<i class="fa fa-spin fa-circle-o-notch"></i>
	{literal}
		<script>
			$Ready(function() {
				$Behavior.addDraggableToBoxes();
				$('.admin_action_menu .popup').trigger('click');
			});
		</script>
	{/literal}
{else}
	<div class="admincp_apps_holder">
        {if isset($warning) && $warning}
        <section class="apps">
            <div class="text-danger text-center">{$warning}</div>
        </section>
        {/if}
        <section class="apps">
			<div class="table-responsive admincp_apps_installed">
                <input type="text" onkeyup="$Core.searchTable(this, 'list_apps', 'app_column_index');" placeholder="{_p var='search_for_app_names_dot'}" class="form-control">
                <div class="ajax" data-url="{url link='admincp.apps.ajax'}"></div>
			</div>
		</section>
		<section class="preview">
			<h1>{_p var='featured_apps'}</h1>
			<div class="phpfox_store_featured" data-type="apps" data-parent="{url link='admincp.store' load='apps'}">
			</div>
		</section>
	</div>
{/if}