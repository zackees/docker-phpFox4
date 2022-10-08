<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\Log;

use Monolog\Handler\SlackHandler as Handler;
use Monolog\Logger;

class SlackHandler extends Handler
{
	/**
	 * SlackHandler constructor.
	 * @param $params
	 * @throws \Monolog\Handler\MissingExtensionException
	 */
	public function __construct($params)
	{
		$token = '';
		$channel = '';
		$username = '';
		$useAttachment = true;
		$iconEmoji = null;
		$level = Logger::DEBUG;
		$bubble = true;
		$useShortAttachment = false;
		$includeContextAndExtra = false;
		$excludeFields = array();

		extract($params, EXTR_IF_EXISTS);

		parent::__construct($token, $channel, $username, $useAttachment, $iconEmoji, $level, $bubble, $useShortAttachment, $includeContextAndExtra, $excludeFields);
	}
}