<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<phpfox>
	<install><![CDATA[		
	$aGateways = array(
		array(
			'gateway_id' => 'paypal',
			'title' => 'PayPal',
			'description' => 'Some information about PayPal...',
			'is_active' => '0',
			'is_test' => '0',
			'setting' => serialize(array(
					'paypal_email' => ''
				)
			)
		)
	);	
	foreach ($aGateways as $aGateways)
	{
		$this->database()->insert(Phpfox::getT('api_gateway'), $aGateways);	
	}	
	]]></install>
</phpfox>