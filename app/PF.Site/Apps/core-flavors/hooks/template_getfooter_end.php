<?php

if (!Phpfox::isAdminPanel()) {
	$url = Phpfox::getLib('assets')->getAssetUrl('PF.Site/flavors/' . flavor()->active->id . '/assets/autoload.js');
    $this->_sFooter .= '<script src="' . $url . '"></script>';
}
