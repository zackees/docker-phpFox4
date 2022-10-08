<?php

foreach ($allApps as $key => $app) {
	if ($app instanceof Core\App\Objects) {
		if ($app->id == 'PHPfox_Flavors') {
			unset($allApps[$key]);
		}
	}
}