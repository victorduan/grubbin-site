<?php
	class Pushover
	{
		/**
		 * @var string app token.
		 */
		protected $_app_token = '';

		/**
		 * @var string user token.
		 */
		protected $_user_key = '';

		public function __construct($app_token, $user_key)
		{
			$this->_app_token = $app_token;
			$this->_user_key  = $user_key;
		}

		public function ProcessOrderForPushover($order) {
			$message = "";

			foreach ($order as $line ) {
				$message .= $line[0];
				unset($line[0]);
				foreach ($line as $x) {
					$message .= "\n".$x;
				}
				$message .= "\n--------\n";
			}

			return $message;
		}

		public function PushToPushover($message, $orderNum, $details) {
			/* 
				$details - an array with customer details

				$details = array(
					"name" => name,
					"phone" => phone,
					"email" => email
				);
			*/ 

			curl_setopt_array($ch = curl_init(), array(
			CURLOPT_URL => "https://api.pushover.net/1/messages.json",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => array(
			  "token" => $this->_app_token,
			  "user" => $this->_user_key,
			  "message" => $message,
			  "title" => "New Order #".$orderNum." for ".$details['name']
			)));
			
			$result = curl_exec($ch);
			curl_close($ch);
		}
	}
?>