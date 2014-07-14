<?php
	session_start();
	//print_r($_POST);
	$ini_array = parse_ini_file("../config.ini", true);
	//print_r($ini_array);

	define("USER_KEY", $ini_array['pushover']['user']);
	define("APP_TOKEN", $ini_array['pushover']['token']);

	$mysql_vars = $ini_array['mysql'];
	define("MYSQL_HOST", $mysql_vars['host']);
	define("MYSQL_USER", $mysql_vars['user']);
	define("MYSQL_PASS", $mysql_vars['pass']);
	define("MYSQL_DB", $mysql_vars['database']);

	define("GMAIL_USER", $ini_array['gmail']['user']);
	define("GMAIL_PASS", $ini_array['gmail']['pass']);

	/* server timezone */
	//define('CONST_SERVER_TIMEZONE', 'UTC');
	 
	/* server dateformat */
	//define('CONST_SERVER_DATEFORMAT', 'Y-m-d H:i:s');

	include('lib/Pushover.php');
	include('lib/MailHelper.php');
	include('lib/ScheduleChecker.php');

	switch ($_POST['type']) {
		case 'sandwich':
			$price = 0;

			$string = file_get_contents("assets/sandwiches.json");
			$json=json_decode($string,true);

			$order = array();
			foreach ($json as $s) {
				if ($s['id'] == $_POST['sandwich']) {
					$price += $s['price']/100;
					$order[] = $s['name'];
				}
			}

			$string = file_get_contents("assets/breads.json");
			$json=json_decode($string,true);

			foreach ($json as $b) {
				if ($b['id'] == $_POST['bread']){
					$order[] = $b['name'];
				}
			}

			if (isset($_POST['add-ons'])) {
				$order[] = implode(", ", $_POST['add-ons']);
			}

			if (strlen($_POST['notes']) > 0) {
				$order[] = $_POST["notes"];
			}
			
			$order['price'] = $price;

			SetCart($order);
			echo json_encode($_SESSION['cart']);
			break;

		case 'byo':
			$order = array();
			$order['price'] = 6.49;

			$order[] = "Build Your Own";

			$string = file_get_contents("assets/breads.json");
			$json=json_decode($string,true);

			foreach ($json as $b) {
				if ($b['id'] == $_POST['bread']){
					$order[] = $b['name'];
				}
			}

			$string = file_get_contents("assets/byo.json");
			$json=json_decode($string,true);

			foreach ($json['meats'] as $b) {
				if ($b['id'] == $_POST['meat']){
					$order[] = $b['name'];
				}
			}

			if (isset($_POST['cheese'])) {
				$names = array();

				foreach ($_POST['cheese'] as $x) {
					foreach ($json['cheeses'] as $j) {
						if ($x == $j['id']) {
							array_push($names, $j['name']);
							$order['price'] += $j['price']/100;
						}
					}
				}
				$order[] = implode(", ", $names);
			}

			if (isset($_POST['byo-add-ons'])) {
				$order[] = implode(", ", $_POST['byo-add-ons']);
			}

			if (isset($_POST['extra'])) {
				$names = array();

				foreach ($_POST['extra'] as $x) {
					foreach ($json['extras'] as $j) {
						if ($x == $j['id']) {
							array_push($names, $j['name']);
							$order['price'] += $j['price']/100;
						}
					}
				}
				$order[] = implode(", ", $names);
			}
			if (isset($_POST['spread'])) {
				$names = array();

				foreach ($_POST['spread'] as $x) {
					foreach ($json['spreads'] as $j) {
						if ($x == $j['id']) {
							array_push($names, $j['name']);
							$order['price'] += $j['price']/100;
						}
					}
				}
				$order[] = implode(", ", $names);
			}

			if (strlen($_POST['notes']) > 0) {
				$order[] = $_POST["notes"];
			}

			SetCart($order);
			echo json_encode($_SESSION['cart']);
			break;

		case 'moregrub':
			$price = 0;
			$order = array();

			$string = file_get_contents("assets/moregrub.json");
			$json=json_decode($string,true);

			foreach ($json as $b) {
				if ($b['id'] == $_POST['moregrub']){
					$order[] = $b['name'];
					$order['price'] = $b['price']/100;
				}
			}

			SetCart($order);
			echo json_encode($_SESSION['cart']);
			break;

		case 'resetCart':
			ResetCart();
			break;

		case 'sendOrder':
			$errCount = 0;

			// Check for a name
			if(empty($_POST['contact-name'])) {
				echo "Oops! Name is empty!<br>";
				$errCount++;
			}

			// Check/validate email
			if(empty($_POST['email'])) {
				echo "Oops! Email is empty!<br>";
				$errCount++;
			}
			elseif(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				echo "Hmm, your email doesn't look valid. Please try again.<br>";
				$errCount++;
			}

			// Check/validate phone number
			$regex = "/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i";

			if(empty($_POST['phone'])) {
				echo "Oops! Phone number is empty!<br>";
				$errCount++;
			}
			elseif(!preg_match( $regex, $_POST['phone'] )) {
				echo "Hmm, your phone number doesn't look valid. Please try again.<br>";
				$errCount++;
			}

			if ($errCount > 0) {
				exit("<br>Check the errors and try again.<br>");
			}

			elseif (ScheduleChecker::CheckSchedule() == False) {
				exit("Sorry, we are currently not taking any orders.");
			}

			else {
				$r = SendOrder($_POST['contact-name'], $_POST['email'], $_POST['phone']);

				$message = "We've received your order! <br>";
				$message .= "What's next? Look for a order confirmation email from us before picking up!<br><br>";
				$message .= "If you have any questions, please call us at (415) 688-7116<br><br><br>";
				$message .= "Your order number for reference: <b>".$r."</b>";
				ResetCart();

				$output = array(
					"status" => 'success',
					"message" => $message
				);

				echo json_encode($output);

			}

			break;

		case 'acceptOrder':

			IntakeOrder($_POST['type'], $_POST['uid']);
			$result = QueryByUID($_POST['uid']);

			$email = $result['orderEmail'];

			// Construct the subject
			$subject = "Grubbin' Order #".$result['orderId']." : We've Started On Your Order!";

			// Construct the title
			$title = "We've Started On Your Order!";

			$gmail = new MailHelper(GMAIL_USER, GMAIL_PASS);

			$body = "<p>Great news! We've started on your order. We estimate that it will take about ".$_POST['est_time']. " minutes.</p>";
			$body .= "<p>We'll let you know when your order is ready for pick-up.</p>";
			$html = MailHelper::PrepareEmailHTML($body, $title);

			// Construct the text
			$body = "Great news! We've started on your order. We estimate that it will take about ".$_POST['est_time']. " minutes.\n\n";
			$body .= "We'll let you know when your order is ready for pick-up.";
			$text = MailHelper::PrepareEmailText($body);

			// Send the email
			echo $gmail->SendGmail($email, $subject, $text, $html);

			break;

		case 'rejectOrder':
			IntakeOrder($_POST['type'], $_POST['uid']);
			$reason = $_POST['reason'];

			$result = QueryByUID($_POST['uid']);

			$email = $result['orderEmail'];

			// Construct the subject
			$subject = "Grubbin' Order #".$result['orderId']." : Sorry, We Can't Complete Your Order!";

			// Construct the title
			$title = "Sorry, We Can't Complete Your Order!";

			$gmail = new MailHelper(GMAIL_USER, GMAIL_PASS);

			$body = "<p>Apologies, we are unable to complete your order for the following reason: <br> ".$reason. "</p>";
			$body .= "<p>If you have any additional questions, please feel free to give us a call @ (415) 688-7116.</p>";
			$html = MailHelper::PrepareEmailHTML($body, $title);

			// Construct the text
			$body = "Apologies, we are unable to complete your order for the following reason: \n\n".$reason;
			$body .= "\n\nIf you have any additional questions, please feel free to give us a call @ (415) 688-7116.";
			$text = MailHelper::PrepareEmailText($body);

			// Send the email
			echo $gmail->SendGmail($email, $subject, $text, $html);
			break;

		case 'orderComplete':
			IntakeOrder($_POST['type'], $_POST['uid']);

			$result = QueryByUID($_POST['uid']);

			$email = $result['orderEmail'];

			// Construct the subject
			$subject = "Grubbin' Order #".$result['orderId']." : We've Completed Your Order!";

			// Construct the title
			$title = "We've Completed Your Order!";

			$gmail = new MailHelper(GMAIL_USER, GMAIL_PASS);

			$body = "<p>It's ready! Come on by and pick-up your order.</p>";
			$body .= "<p>See you soon!</p>";
			$html = MailHelper::PrepareEmailHTML($body, $title);

			// Construct the text
			$body = "It's ready! Come on by and pick-up your order.\n\n";
			$body .= "See you soon!";
			$text = MailHelper::PrepareEmailText($body);

			// Send the email
			echo $gmail->SendGmail($email, $subject, $text, $html);

			break;

		case 'viewOrder':
			if(isset($_SESSION['cart'])){
				echo PrettyPrintOrderHTML($_SESSION['cart']);
			}
			else{
				echo "You have nothing in your order!";
			}
			break;

		default:
			exit();
			break;
	}

	function SetCart($data) {
		
		if (!isset($_SESSION['cart'])) {
			$_SESSION['cart'] = array();
		}
		
		array_push($_SESSION['cart'], $data);
	}

	function ResetCart() {
		unset($_SESSION['cart']);
	}

	function SendOrder($name, $email, $phone, $sendEmail=True, $sendPushover=True) {

		$orderId = NewToMySQL($name, $email, $phone, $_SESSION['cart']);

		if ($sendEmail == True) {

			$gmail = new MailHelper(GMAIL_USER, GMAIL_PASS);

			// Construct the HTML
			$body = "<p>Good news! We have received your order. We'll review your order and let you know the next steps.<br><br>";
			$body .= PrettyPrintOrderHTML($_SESSION['cart']);
			$body .= "</p>";

			$html = MailHelper::PrepareEmailHTML($body, "We've Received Your Order!");

			// Construct the text
			$body = "Good news! We have received your order. We'll review your order and let you know the next steps.\n\n";
			$body .= Pushover::ProcessOrderForPushover($_SESSION['cart']);

			$text = MailHelper::PrepareEmailText($body);

			// Send the email
			$subject = "Grubbin' Order #".$orderId." : We Received Your Order!";
			$gmail->SendGmail($email, $subject, $text, $html);
		}

		if ($sendPushover == True) {
			$pushover = new Pushover(APP_TOKEN, USER_KEY);
			$message = Pushover::ProcessOrderForPushover($_SESSION['cart']);

			$customerDetails = array(
					"name" => $name,
					"phone" => $phone,
					"email" => $email
				);

			$pushover->PushToPushover($message,$orderId, $customerDetails);
		}

		return $orderId;
	}

	function NewToMySQL($name, $email, $phone, $order) {
		// Generate a unique idenfitier for the transaction
		$uid = session_id() . "-" . uniqid();

		// Establish connection
		$con=mysqli_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PASS,MYSQL_DB);

		// Check connection
		if (mysqli_connect_errno()) {
		  echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}

		else {
			#echo "successful";
		}

		$json = mysql_real_escape_string(json_encode($order));

		$name = mysql_real_escape_string($name);
		$email = mysql_real_escape_string($email);
		$phone = mysql_real_escape_string($phone);

		$timeNow = now('UTC');

		$insert  = "INSERT INTO `orders` (`timeIn`,	`internalUID`, `orderName`, `orderPhone`, `orderEmail`, `status`, `orderDetails`, `ipAddress`) VALUES ";
		$insert .= "('".$timeNow."', '".$uid."', '".$name."', '".$phone."', '".$email."', 'NEW', '".$json."', '".$_SERVER['REMOTE_ADDR']."');";

		#echo $insert;

		if(!mysqli_query($con, $insert)) {
			echo "Error on insert: (" . $mysqli->errno . ") " . $mysqli->error;
			#echo $insert;
		}

		else {
			// Find the record that was inserted
			$result = mysqli_query($con,"SELECT * FROM orders WHERE internalUID = '".$uid."'");
			$row=mysqli_fetch_array($result,MYSQLI_ASSOC);
			$orderId = $row['orderId'];
			// Free result set
			mysqli_free_result($result);

		}
		mysqli_close($con);
		return $orderId;
	}

	function PrettyPrintOrderHTML($array) {
			$subtotal = 0;

			$message = "";

			foreach ($array as $line ) {
				if (isset($line['price'])){
					$v = $line['price'];
					$subtotal += $v;
					unset($line['price']);
					$line['price'] = "$".number_format($v, 2);
				}
				$message .= "<b>".$line[0]."</b>";
				unset($line[0]);
				foreach ($line as $x) {
					$message .= "<br>".$x;
				}
				$message .= "<br><br>";
			}
			//$message .= "<br>";
			$message .= "<b>Estimated total (before tax): </b>$".number_format($subtotal, 2);

			return $message;
		}

	function IntakeOrder($decision, $uid) {
		switch ($decision) {
			case 'acceptOrder':
				$status = "ACCEPTED";
				$table = "timeAccept";
				break;
			case 'rejectOrder':
				$status = "REJECTED";
				$table = "timeAccept";
				break;
			case 'orderComplete':
				$status = "COMPLETED";
				$table = "timeComplete";
				break;
			default:
				# Do Nothing
				break;
		}

		if (isset($status)) {
			// Establish connection
			$con=mysqli_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PASS,MYSQL_DB);

			// Check connection
			if (mysqli_connect_errno()) {
			  echo "Failed to connect to MySQL: " . mysqli_connect_error();
			}

			else {
				#echo "successful";
			}

			$timeNow = now('UTC');
			$update = "UPDATE orders SET `status`='".$status."', ".$table."='".$timeNow."' WHERE internalUID = '".$uid."';";

			if(!mysqli_query($con, $update)) {
				echo "Error on update: (" . $mysqli->errno . ") " . $mysqli->error;
				echo $update;
			}

			mysqli_close($con);
		}
	}

	function QueryByUID($uid) {
		// Establish connection
		$con=mysqli_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PASS,MYSQL_DB);

		// Check connection
		if (mysqli_connect_errno()) {
		  echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}

		else {
			#echo "successful";
		}

		$result = mysqli_query($con,"SELECT * FROM orders WHERE internalUID = '".$uid."'");
		$row=mysqli_fetch_array($result,MYSQLI_ASSOC);
		
		// Free result set
		mysqli_free_result($result);
		

		mysqli_close($con);

		if(isset($row)) {
			return $row;
		}
	}

	/*
	//Converts current time for given timezone (considering DST)
	 *  to 14-digit UTC timestamp (YYYYMMDDHHMMSS)
	 *
	 * DateTime requires PHP >= 5.2
	 *
	 * @param $str_user_timezone
	 * @param string $str_server_timezone
	 * @param string $str_server_dateformat
	 * @return string
	 */
	function now($str_user_timezone,
	       $str_server_timezone = CONST_SERVER_TIMEZONE,
	       $str_server_dateformat = CONST_SERVER_DATEFORMAT) {
	 
	  // set timezone to user timezone
	  date_default_timezone_set($str_user_timezone);
	 
	  $date = new DateTime('now');
	  $date->setTimezone(new DateTimeZone($str_server_timezone));
	  $str_server_now = $date->format($str_server_dateformat);
	 
	  // return timezone to server default
	  date_default_timezone_set($str_server_timezone);
	 
	  return $str_server_now;
	}

?>