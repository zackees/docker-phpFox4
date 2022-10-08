<?php

if (!Phpfox::isAdminPanel()) {
    $url = Phpfox::getLib('assets')->getAssetUrl('PF.Site/flavors/' . flavor()->active->id . '/assets/autoload.css');
	$sData .= '<link href="' . $url . 'autoload.css?v=' . Phpfox::internalVersion() . '" rel="stylesheet">';
}
