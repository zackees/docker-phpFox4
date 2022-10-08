<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\SMS;


interface SmsSenderInterface
{
	/**
	 * SmsSenderInterface constructor.
	 *
	 * @param array $params
	 */
	public function __construct($params);

	/**
	 * @param string $to
	 * @param string $msg
	 * @return bool
	 */
	public function sendSMS($to, $msg);
}