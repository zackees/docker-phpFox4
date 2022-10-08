<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\SMS;

class SmsManager
{
	/**
	 * @var SmsSenderInterface
	 */
	private $sender;


	public function getSender()
	{
		if (null == $this->sender) {

		}
		return $this->sender;
	}

	/**
	 * @param string $to phone number
	 * @param string $msg text message
	 * @return bool
	 */
	public function sendSMS($to, $msg)
	{
		$sender = $this->getSender();

		return $sender->sendSMS($to, $msg);
	}

	/**
	 * @return array|null
	 */
	public function getDefaultSender()
	{
		return db()->select('d.*, s.edit_link, s.service_phrase_name')
			->from(':core_sms_sender', 'd')
			->join(':core_sms_service', 's', 's.service_id=d.service_id')
			->where(['d.is_active' => 1])
			->execute('getSlaveRow');
	}
}