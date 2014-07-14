<html>
<head>
	<?php
		define('TIME_FORMAT', "m/d h:i A");
		date_default_timezone_set("UTC");
		$ini_array = parse_ini_file("../config.ini", true);

		$mysql_vars = $ini_array['mysql'];
		define("MYSQL_HOST", $mysql_vars['host']);
		define("MYSQL_USER", $mysql_vars['user']);
		define("MYSQL_PASS", $mysql_vars['pass']);
		define("MYSQL_DB", $mysql_vars['database']);

		function objectToArray($d) {
			if (is_object($d)) {
				// Gets the properties of the given object
				// with get_object_vars function
				$d = get_object_vars($d);
			}
	 
			if (is_array($d)) {
				/*
				* Return array converted to object
				* Using __FUNCTION__ (Magic constant)
				* for recursive call
				*/
				return array_map(__FUNCTION__, $d);
			}
			else {
				// Return array
				return $d;
			}
		}

		function PrettyPrintOrder($array) {
			$message = "";

			$array = objectToArray($array);

			foreach ($array as $line ) {
				$message .= "<b>".$line[0]."</b>";
				unset($line[0]);
				unset($line['price']);
				foreach ($line as $x) {
					$message .= "<br>".$x;
				}
				$message .= "<br><br>";
			}

			return $message;
		}

		/* Takes the datetime string from MySQL and formats
		to local time (PHP timezones)

		*/

		function UTCtoLocal($timeString, $locale_string) {
			$utc_date = DateTime::createFromFormat(
                'Y-m-d H:i:s', 
                $timeString, 
                new DateTimeZone('UTC')
				);

			$output = $utc_date;
			$output->setTimeZone(new DateTimeZone($locale_string));

			return $output->format(TIME_FORMAT);
		}
	?>

	<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>
	<link href='styles/magnific-popup.css' rel='stylesheet' type='text/css'>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>	
	<script src="js/jquery.magnific-popup.min.js"></script> 

	<style>
		body { font-family: 'Open Sans', sans-serif; }
		th, td { border-left:1px solid black; border-bottom:1px solid black;}
		table { border-spacing: 0px; border-top:1px solid black; border-right:1px solid black;}
		/*th, td { border-bottom: 1px solid black; border-left: 1px solid black; border-right: 1px solid black; }*/
		td, th {
			padding-left: 5px;
			padding-right: 5px;
			padding-top: 10px;
			padding-bottom: 10px;
			font-size: 1em;
			font-family: "Courier New", Courier, monospace;
			vertical-align: top;
		}

		td.center {
			text-align: center;
		}

		td.accepted { background: yellow; }
		td.completed { background: #CCCCCC; }
		td.rejected { background: #E60000; }
		td.new { background: #2BE01B; }

		.white-popup {
			position: relative;
			background: #FFF;
			padding: 20px;
			width: auto;
			max-width: 500px;
			margin: 20px auto;
		}

		button {
		    padding: 10px 15px;
		    background: #4479BA;
		    color: #FFF;
		    -webkit-border-radius: 4px;
		    -moz-border-radius: 4px;
		    border-radius: 4px;
		    border: solid 1px #20538D;
		    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.4);
		    -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);
		    -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);
		    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);
		    -webkit-transition-duration: 0.2s;
		    -moz-transition-duration: 0.2s;
		    transition-duration: 0.2s;
		    -webkit-user-select:none;
		    -moz-user-select:none;
		    -ms-user-select:none;
		    user-select:none;
		    font-size: 1.5em;
		}
		button:hover {
		    background: #356094;
		    border: solid 1px #2A4E77;
		    text-decoration: none;
		}
		button:active {
		    -webkit-box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.6);
		    -moz-box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.6);
		    box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.6);
		    background: #2E5481;
		    border: solid 1px #203E5F;
		}

		button.acceptOrderButton {
			background: green;
		}

		button.rejectOrderButton {
			background: #E60000;
		}
	
	</style>

	<script>
		$(document).ready(function(){

			var uid = "";

			// ACCEPT ORDER
			$('.acceptOrderButton').click(function() {
				uid = this.id;
				console.log(uid);
				$('#order-accept').append("<input type='hidden' name='uid' value='"+ uid +"' />");
			})
			.magnificPopup({
				  items: {
				      src: '#order-accept',
				      type: 'inline'
				  }
			});

			$('#accept').click(function(e) { 
		        e.preventDefault();  //prevent form from submitting
		        $('#order-accept').append("<input type='hidden' name='type' value='acceptOrder' />");
				var data = $("#order-accept :input").serializeArray();

				console.log(data); //use the console for debugging, F12 in Chrome, not alerts

				$.post( "process.php", 
						data,
						function(output) {
							console.log(output);
						}
				);

				$("#acceptDiv-" + uid).hide();
				$("#completeDiv-" + uid).show();
				$.magnificPopup.close();

			});

			// REJECT ORDER
			$('.rejectOrderButton').click(function() {
				uid = this.id;
				console.log(uid);
				$('#order-reject').append("<input type='hidden' name='uid' value='"+ uid +"' />");
			})
			.magnificPopup({
				  items: {
				      src: '#order-reject',
				      type: 'inline'
				  }
			});

			$('#reject').click(function(e) { 
		        e.preventDefault();  //prevent form from submitting
		        $('#order-reject').append("<input type='hidden' name='type' value='rejectOrder' />");
				var data = $("#order-reject :input").serializeArray();

				console.log(data); //use the console for debugging, F12 in Chrome, not alerts

				$.post( "process.php", 
						data,
						function(output) {
							console.log(output);
						}
				);
				//$("#acceptDiv").css({ display: "none" });
				$("#acceptDiv-" + uid).hide();
				$.magnificPopup.close();


			});

			// COMPLETE ORDER
			$('.completeOrderButton').click(function() {
				uid = this.id;
				console.log(uid);
				$('#order-complete').append("<input type='hidden' name='uid' value='"+ uid +"' />");
			})
			.magnificPopup({
				  items: {
				      src: '#order-complete',
				      type: 'inline'
				  }
			});

			$('#completed').click(function(e) { 
		        e.preventDefault();  //prevent form from submitting
		        $('#order-complete').append("<input type='hidden' name='type' value='orderComplete' />");
				var data = $("#order-complete :input").serializeArray();

				console.log(data); //use the console for debugging, F12 in Chrome, not alerts

				$.post( "process.php", 
						data,
						function(output) {
							console.log(output);
						}
				);

				console.log("#completeDiv-" + uid)
				$("#completeDiv-" + uid).hide();
				$.magnificPopup.close();

			});

			$('#notcompleted').click(function(e) { 
				$.magnificPopup.close();
			});

		});
	</script>
</head>
<body>
<?php
	// Establish connection
	$con=mysqli_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PASS,MYSQL_DB);
	// Check connection
	if (mysqli_connect_errno()) {
	  echo "Failed to connect to database: " . mysqli_connect_error();
	}

	$result = mysqli_query($con,"SELECT * FROM orders ORDER BY timeIn DESC");

	mysqli_close($con);

	echo "<table>";
	echo "<tr>";
	echo "<th>Order ID</th>";
	echo "<th>Name</th>";
	echo "<th>Order</th>";
	echo "<th>Time In</th>";
	echo "<th>Action Item</th>";
	echo "<th>Status</th>";
	echo "</tr>";

	while($row = mysqli_fetch_array($result)) {
		$i = 0;
		$uid = $row['internalUID'];

		$customer = "<b>" . $row['orderName'] . "</b><br>" . $row['orderEmail'] . "<br>" . $row['orderPhone']; 

		if (is_null($row['timeAccept'])) {
			$action  = "<div id='acceptDiv-".$uid."'><button class='acceptOrderButton' id='".$uid."'>ACCEPT</button><br><br><button class='rejectOrderButton' id='".$uid."'>REJECT</button></div>";
			$action .= "<div id='completeDiv-".$uid."' style='display: none;'><button class='completeOrderButton' id='".$uid."'>COMPLETE</button></div>";
		}

		elseif (is_null($row['timeComplete']) && ($row['status'] ==  "ACCEPTED") ) {
			$action = "<div id='completeDiv-".$uid."'><button class='completeOrderButton' id='".$uid."'>COMPLETE</button></div>";
		}

		else {
			$action = "";
		}

		$i++;

		//$timeAccept = (is_null($row['timeAccept'])) ? "<button class='acceptOrderButton' id='".$uid."'>ACCEPT</button><button class='rejectOrderButton' id='".$uid."'>REJECT</button>" : date(TIME_FORMAT, strtotime($row['timeAccept']));
		
		// Nested logic to generate Complete button
		//$complete = (is_null($row['timeComplete'])) ? (($row['status'] ==  "ACCEPTED") ? "<button class='completeOrderButton' id='".$uid."'>COMPLETE</button>" : '') : date(TIME_FORMAT, strtotime($row['timeComplete']));

		//$timeIn = date(TIME_FORMAT, strtotime($row['timeIn']));
		$timeIn = UTCtoLocal($row['timeIn'], 'America/Los_Angeles');

		echo "<tr>";
		echo "<td>".$row['orderId']."</td>";
		echo "<td>".$customer."</td>";
		echo "<td>".PrettyPrintOrder(json_decode($row['orderDetails']))."</td>";
		echo "<td class='center'>".$timeIn."</td>";
		echo "<td class='center'>".$action."</td>";
		echo "<td class='center ".strtolower($row['status'])."'>".$row['status']."</td>";
		echo "</tr>";
	}
	echo "</table>"
	
?>
<!-- Hidden Order Accept Button -->
<div id="order-accept" class="white-popup mfp-hide" style="text-align:center;">
	<form id='form-order-accept'>
		Estimated Time: 
		<select name="est_time">
			<option value="15">15 min</option>
			<option value="20">20 min</option>
			<option value="25">25 min</option>
			<option value="30">30 min</option>
			<option value="35">35 min</option>
			<option value="40">40 min</option>
			<option value="45">45 min</option>
		</select>
	</form>
	<button id="accept" class="form-order-accept" form="form-order-accept">Accept the Order</button>
</div>

<!-- Hidden Order Accept Button -->
<div id="order-reject" class="white-popup mfp-hide" style="text-align:center;">
	<form id='form-order-reject'>
		Please enter the reason for reject.
		<textarea name="reason" rows="8" cols="50"></textarea>
	</form>
	<button id="reject" class="form-order-accept" form="form-order-accept">Reject the Order</button>
</div>

<!-- Hidden Complete Order Button -->
<div id="order-complete" class="white-popup mfp-hide" style="text-align:center;">
	<form id='form-complete-order'>
		Are you sure you want to mark this order as Complete?
	</form>
	<button id="completed" class="form-complete-order" form="form-complete-order">Yes</button>
	<button id="notcompleted" class="form-complete-order" form="form-complete-order">No</button>
</div>
</body>
</html>