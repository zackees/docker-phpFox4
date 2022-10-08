<?php

defined('PHPFOX') or define('PHPFOX', 1);

$out = [];
try {
	if (!file_exists(__DIR__ . '/../file/settings/license.sett.php')) {
		throw new Exception('phpFox does not seem to be installed.');
	}
	require(__DIR__ . '/../file/settings/license.sett.php');
	$version = '';

	$re = '/^\s*const\s+VERSION\s*=\s*\'([\w\.]+)\'/m';
	if (preg_match($re, file_get_contents(__DIR__ . '/../include/library/phpfox/phpfox/phpfox.class.php'), $matches)) {
		$version = $matches[1];
	}

	if (empty($_REQUEST['license_id']) || empty($_REQUEST['license_key'])) {
		throw new Exception('Missing License ID/Key');
	}

	if ($_REQUEST['license_id'] != PHPFOX_LICENSE_ID) {
		throw new Exception('License ID does not match.');
	}

	if ($_REQUEST['license_key'] != PHPFOX_LICENSE_KEY) {
		throw new Exception('License Key does not match.');
	}

	$out = $version;
} catch (Exception $e) {
	$out = ['error' => $e->getMessage()];
}

header('Content-type: application/json; charset=utf-8');
echo json_encode($out, JSON_PRETTY_PRINT);