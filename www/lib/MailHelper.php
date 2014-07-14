<?php
	class MailHelper
	{

		/**
		 * @var string GMail username.
		 */
		protected $_username = '';

		/**
		 * @var string GMail password.
		 */
		protected $_password = '';

		public function __construct($user, $pass)
		{
			$this->_username = $user;
			$this->_password  = $pass;
		}

		public function PrepareEmailHTML($body, $title) 
		{
			$baseHTML = file_get_contents(getcwd()."/basic_template.html");
			$html = str_replace("{{{TITLE}}}", $title, $baseHTML);
			$html = str_replace("{{{BODY}}}", $body, $html);

			return $html;
		}

		public function PrepareEmailText($body) 
		{
			$message = "Dear Customer, \n";
			$message .= $body;
			$message .= "\n\nSincerely, Grubbin'";

			return $body;
		}

		public function SendGmail($to, $subject, $text, $html) 
		{
			// Pear Mail Library
			require_once "Mail.php";
			include('Mail/mime.php');

			$from = $this->_username;
			$fromName = "Grubbin'";

			$crlf = "\n";

			$headers = array(
						'From'          => $fromName . " <" . $from . ">\r\n",
						'Return-Path'   => $from,
						'Subject'       => $subject
						);
			// Creating the Mime message
	        $mime = new Mail_mime($crlf);

	        // Setting the body of the email
	        $mime->setTXTBody($text);
	        $mime->setHTMLBody($html);

	        $body = $mime->get();
	        $headers = $mime->headers($headers);

			$smtp = Mail::factory('smtp', array(
			        'host' => 'ssl://smtp.gmail.com',
			        'port' => '465',
			        'auth' => true,
			        'username' => $this->_username,
			        'password' => $this->_password
			    ));

			$mail = $smtp->send($to, $headers, $body);

			if (PEAR::isError($mail)) {
			    return '<p>' . $mail->getMessage() . '</p>';
			} else {
			    return '<p>Message successfully sent!</p>';
			}
		}
	}
?>