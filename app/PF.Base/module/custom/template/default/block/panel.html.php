<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
{if !empty($customFieldTypeId)}
    {module name='custom.display' type_id=$customFieldTypeId item_id=$aUser.user_id template=$customFieldTemplate ignored_fields=$ignoredFields}
{/if}