<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\SMS;


class NexmoSmsSender implements SmsSenderInterface
{
	/**
	 * @const https://rest.nexmo.com/
	 */
	const BASE_URL = 'https://rest.nexmo.com/';

	/**
	 * @var string
	 */
	private $fromNumber;

	/**
	 * @var string
	 */
	private $apiKey;

	/**
	 * @var string
	 */
	private $apiSecret;


	public function __construct($params)
	{
		$this->fromNumber = $params['from_number'];
		$this->apiKey = $params['key'];
		$this->apiSecret = $params['secret'];
	}

	/**
	 * @link https://www.twilio.com
	 *
	 * @param $to
	 * @param $msg
	 * @return bool
	 */
	public function sendSMS($to, $msg)
	{


		$endpointUrl = self::BASE_URL . 'sms/json';
		$postFields = http_build_query([
			'api_key' => $this->apiKey,
			'api_secret' => $this->apiSecret,
			'from' => $this->fromNumber,
			'to' => $to,
			'text' => $msg,
		]);

		$ch = curl_init($endpointUrl);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

		$response = curl_exec($ch);

		if (empty($response) || curl_error($ch)) {
			curl_close($ch);
			return false;
		}

		curl_close($ch);

		$result = json_decode($response, true);

		if (empty($result['messages'][0]['status']))
			return true;

		return false;
	}
}