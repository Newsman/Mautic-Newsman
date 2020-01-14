<?php
/*
 * @copyright   2020 NewsmanApp. All rights reserved
 * @author      NewsmanApp - Lucian
 *
 * @link        https://newsman.app
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use \Swift_Mailer;

/**
 * Class NewsmanTransport.
 */
//class NewsmanTransport extends AbstractTokenHttpTransport implements \Swift_Transport, CallbackTransportInterface
class NewsmanTransport implements \Swift_Transport, CallbackTransportInterface
{
	public $apiKey, $user;
	protected $started = false;
	/**
	 * @var TranslatorInterface
	 */
	private $translator;

	/**
	 * @var TransportCallback
	 */
	private $transportCallback;

	/**
	 * NewsmanTransport constructor.
	 *
	 * @param TranslatorInterface $translator
	 * @param TransportCallback $transportCallback
	 */
	public function __construct(TranslatorInterface $translator, TransportCallback $transportCallback)
	{
		$this->translator = $translator;
		$this->transportCallback = $transportCallback;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getPayload()
	{

	}

	/**
	 * Test if this Transport mechanism has started.
	 *
	 * @return bool
	 */
	public function isStarted()
	{
		return $this->started;
	}

	/**
	 * Stop this Transport mechanism.
	 */
	public function stop()
	{
		$this->started = false;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getHeaders()
	{
	}

	/**
	 * Create a new Swift_Transport_NewsmanApi instance.
	 *
	 * @param string $account_id
	 * @param string $api_key
	 *
	 * @return self
	 */
	public static function newInstance($account_id, $api_key)
	{

	}

	/**
	 * Send the given Message.
	 *
	 * Recipient/sender data will be retrieved from the Message API.
	 * The return value is the number of recipients who were accepted for delivery.
	 *
	 * @param Swift_Mime_Message $message
	 * @param string[] $failedRecipients An array of failures by-reference
	 *
	 * @return int
	 */
	public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
	{
		if(empty($this->user) || empty($this->apiKey))
		{
			throw new \Exception("Add your newsman username & apikey");
		}

		$failedRecipients = (array)$failedRecipients;
		$recipients = array();
		foreach ($message->getTo() as $email => $name)
		{
			$recipients[$email] = $name;
		}
		foreach ($message->getCc() as $email => $name)
		{
			$recipients[$email] = $name;
		}
		foreach ($message->getBcc() as $email => $name)
		{
			$recipients[$email] = $name;
		}
		$json_data = array(
			"mime_message" => $message->toString(),
			"recipients" => array_keys($recipients),
			"account_id" => $this->user,
			"key" => $this->apiKey
		);

		$api_url = $this->getApiEndpoint();

		$ch = curl_init($api_url . "message.send_raw");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$result = @json_decode($response, true);
		if ($http_code != 200)
		{
			if ($http_code == 500 && is_array($result) && array_key_exists("err", $result))
			{
				throw new \Exception($result["err"]);
			} else
			{

				throw new \Exception("Could not call http method. Response code: $http_code - $result");
			}
		}
		return count($result);
	}


	/**
	 * {@inheritdoc}
	 */
	public function getApiEndpoint()
	{
		return "https://cluster.newsmanapp.com/api/1.0/";
	}

	public function getEndpoint()
	{

		$apiKey = $this->getApiKey();
		$user = $this->getUsername();
		return 'https://ssl.newsman.app/api/1.2/rest/' . $user . '/' . $apiKey . '/';
	}

	/**
	 * Start this Transport mechanism.
	 */
	public function start()
	{
		if(empty($this->user) || empty($this->apiKey))
		{
			throw new \Exception("Add your newsman username & apikey");
		}

		$this->started = true;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param $response
	 * @param $info
	 *
	 * @return array
	 *
	 * @throws \Swift_TransportException
	 */
	protected function handlePostResponse($response, $info)
	{

	}

	/**
	 * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
	 *
	 * @return mixed
	 */
	public function getCallbackPath()
	{
		return 'newsman';
	}

	/**
	 * @return int
	 */
	public function getMaxBatchLimit()
	{
		return 0;
	}

	public function setUsername($user){
		$this->user = $user;
	}

	public function setPassword($pass){
		$this->apiKey = $pass;
	}

	/**
	 * @param \Swift_Message $message
	 * @param int $toBeAdded
	 * @param string $type
	 *
	 * @return int
	 */
	public function getBatchRecipientCount(\Swift_Message $message, $toBeAdded = 1, $type = 'to')
	{
		return 0;
	}

	/**
	 * Handle response.
	 *
	 * @param Request $request
	 */
	public function processCallbackRequest(Request $request)
	{

	}

	/**
	 * Register a plugin in the Transport.
	 *
	 * @param Swift_Events_EventListener $plugin
	 */
	public function registerPlugin(\Swift_Events_EventListener $plugin)
	{
		// no plugins
	}
}
