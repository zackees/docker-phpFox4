<?php
/**
 * @author phpfox
 * @license phpfox.com
 */

namespace Core\SMS;


class TwilioSmsSender implements SmsSenderInterface
{
	/**
	 * @var string
	 */
	private $accountId;

	/**
	 * @var string
	 */
	private $authToken;

	/**
	 * @var string
	 */
	private $fromNumber;
	/**
	 * @const https://api.twilio.com/2010-04-01/
	 */
	const BASE_URL = 'https://api.twilio.com/2010-04-01';

	/**
	 * @inheritDoc
	 */
	public function __construct($params)
	{
		$this->authToken = $params['token'];
		$this->fromNumber = '+' . trim($params['from_number'], '+');
		$this->accountId = $params['account_id'];
	}


	/**
	 * @link https://www.twilio.com
	 *
	 * @param string $to To phone number
	 * @param string $msg Message content
	 *
	 * @return bool
	 */
	public function sendSMS($to, $msg)
	{
		$userpwd = sprintf('%s:%s', $this->accountId, $this->authToken);

		$endpointUrl = self::BASE_URL . 'Accounts/' . $this->accountId
			. '/Messages.json';
		$postFields = http_build_query([
			'To' => '+' . trim($to, '+'),
			'From' => '+' . trim($this->fromNumber, '+'),
			'Body' => $msg,
		]);

		$ch = curl_init($endpointUrl);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_USERPWD, $userpwd);

		$response = curl_exec($ch);
		$error = curl_errno($ch);
		curl_close($ch);

		if ($error) {
			return false;
		}

		if (empty($response)) {
			return false;
		}

		$response = json_decode($response, true);

		if (isset($response['error_code']) && $response['error_code']) {
			return false;
		}

		return true;
	}
}