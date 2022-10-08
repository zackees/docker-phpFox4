<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\SMS;

use InvalidArgumentException;

class Admincp
{
	/**
	 * @return array
	 */
	public function getSmsServices()
	{
		return db()->select('d.*')
			->from(':core_sms_service', 'd')
			->execute('getSlaveRows');
	}

	/**
	 * @param string $service_id
	 * @return array|null
	 */
	public function getSmsServiceById($service_id)
	{
		return db()->select('d.*')
			->from(':core_sms_service', 'd')
			->where(['d.service_id' => (string)$service_id])
			->execute('getSlaveRows');
	}

	/**
	 * @return array|null
	 */
	public function getSmsSenders()
	{
		return db()->select('d.*, s.edit_link, s.service_phrase_name')
			->from(':core_sms_sender', 'd')
			->join(':core_sms_service', 's', 's.service_id=d.service_id')
			->execute('getSlaveRows');
	}

	/**
	 * @param string $sender_id
	 * @return array|null
	 */
	public function getSenderById($sender_id)
	{
		return db()->select('d.*, s.edit_link, s.service_phrase_name')
			->from(':core_sms_sender', 'd')
			->join(':core_sms_service', 's', 's.service_id=d.service_id')
			->where(['d.sender_id' => (string)$sender_id])
			->execute('getSlaveRow');

	}

	/**
	 * @param string $service_class
	 * @param array $config
	 * @return SmsSenderInterface
	 * @throws InvalidArgumentException
	 */
	public function makeSender($service_class, $config)
	{
		if (!class_exists($service_class)) {
			throw new InvalidArgumentException("Could not found sms sender '$service_class'");
		}

		return new $service_class($config);
	}

	/**
	 * @param string $sender_id
	 * @param array $config
	 * @param string $to phone number
	 * @param string $msg text message
	 * @return bool
	 */
	public function verifySenderConfig($sender_id, $config, $to, $msg)
	{
		$service = $this->getSenderById($sender_id);

		if (!$service) {
			throw new InvalidArgumentException("Could not found sms sender '$sender_id'");
		}

		$sender = $this->makeSender($service['service_class'], $config);

		return $sender->sendSMS($to, $msg);
	}

	/**
	 * @param string $sender_id
	 * @param string $service_id
	 * @param string $is_default
	 * @param array $config
	 *
	 * @return bool
	 */
	public function updateSenderConfig($sender_id, $service_id, $is_default, $config)
	{
		$service = $this->getSenderById($sender_id);

		if (!$service) {
			throw new InvalidArgumentException("Could not found sms sender '$sender_id'");
		}

		$data = [
			'service_id' => $service_id,
			'is_default' => $is_default,
			'config' => json_encode($config, JSON_FORCE_OBJECT),
		];

		if (!$sender_id) {
			return db()->insert(':core_sms_sender', $data);
		} else {
			db()->update(':core_sms_sender', $data, [
				'service_id' => (string)$sender_id,
			]);
		}
		return true;
	}
}