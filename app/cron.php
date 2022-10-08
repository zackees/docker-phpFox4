<?php

ignore_user_abort(true);

/**
 * Key to include phpFox
 *
 */
define('PHPFOX', true);

/**
 * Directory Separator
 *
 */
define('PHPFOX_DS', DIRECTORY_SEPARATOR);

/**
 * phpFox Root Directory
 *
 */
define('PHPFOX_DIR', dirname(__FILE__) . PHPFOX_DS . 'PF.Base' . PHPFOX_DS);

/**
 * No SESSIONS
 *
 */
define('PHPFOX_NO_SESSION', true);

/**
 * Do not set user sessions
 *
 */
define('PHPFOX_NO_USER_SESSION', true);

/**
 * Do not run
 */
define('PHPFOX_NO_RUN', true);

define('PHPFOX_CRON', true);


// Require all phpfox methods
require PHPFOX_DIR . 'start.php';

$token = Phpfox::getLib("request")->get("token");

if ((empty($token) || $token =! setting('pf_cron_task_token'))
	&& php_sapi_name() !== 'cli') {
    exit("Unknown token. Exist!");
}

// load crons table then runs
$jobs = Phpfox::getLib('cron')->getReadyJobs();
$lastJobs = [];
foreach ($jobs as $job) {
    try {
        if($job == 'Phpfox_Queue::instance()->work();') {
            $lastJobs[] = $job;
            continue;
        }
        eval($job);
    }
    catch (\Exception $e) {
        Phpfox::getLog('cron.log')->error("Cron execute error: " . $e->getMessage());
    }
}

// run last jobs
foreach ($lastJobs as $job) {
    try {
        eval($job);
    }
    catch (\Exception $e) {
        Phpfox::getLog('cron.log')->error("Cron execute error: " . $e->getMessage());
    }
}

Phpfox::getLog('cron.log')->info('Process ' . count($jobs));

if(defined('PHPFOX_DEBUG') && PHPFOX_DEBUG) {
    $time = (microtime(true) - PHPFOX_TIME_START);
    $message = 'Process ' . count($jobs) . ' job(s) in ' . $time;
    echo $message;
}

exit();
