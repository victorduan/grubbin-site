<?php session_start(); ?>
<?php
	include ('lib/ScheduleChecker.php');
	$status = ScheduleChecker::CheckSchedule();

	// Check against schedule
	if ($status == False) {
		echo "<html><body>";
		echo "<img src='assets/closed-sign.png' style='display:block; margin: auto;'>";
		echo "</body></html>";

		die();
	}
?>
<?php 
	
	if (isset($_SESSION['cart'])) {
		//print_r($_SESSION['cart']);
		$price = 0;
		foreach ($_SESSION['cart'] as $item) {
			$price += $item['price'];
		}
	}

	$string = file_get_contents("assets/sandwiches.json");
	$json=json_decode($string,true);

	$string = file_get_contents("assets/breads.json");
	$breads=json_decode($string,true);

	$string = file_get_contents("assets/byo.json");
	$byo=json_decode($string,true);	

	$string = file_get_contents("assets/moregrub.json");
	$moregrub=json_decode($string,true);	

	$ini_array = parse_ini_file("../config.ini", true);
	//print_r($ini_array);
?>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, user-scalable=yes, target-densityDpi=device-dpi" />

	<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>
	<link href='styles/magnific-popup.css' rel='stylesheet' type='text/css'>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>	
	<script src="js/jquery.magnific-popup.min.js"></script> 
	<style>
		body { font-family: 'Open Sans', sans-serif; font-size: 14px; font-weight: normal; }
		h3 { font-family: 'Open Sans', sans-serif; font-size: 20px; font-weight: normal; background-color: #cccccc; border: 1px solid black; padding: 10px; margin: 10px; }
		span { font-size: 0.75em; }
		h3:hover { color: red; cursor: pointer; } 
		.view-order:hover { cursor: pointer; }
		.sandwich-name { font-family: 'Open Sans', sans-serif; font-size: 20px; font-weight: normal; }
		.sandwich-desc { font-family: 'Open Sans', sans-serif; font-size: 12px; margin-left: 18px; }
		.sandwich-xtra { font-family: 'Open Sans', sans-serif; font-size: 14px; }
		.main-categories { padding-left: 1em; padding-right: 0.25em; }
		.span-header { font-size: 16px; }

		.white-popup {
			position: relative;
			background: #FFF;
			padding: 20px;
			width: auto;
			max-width: 500px;
			margin: 20px auto;
		}

		.hidden-breaks {
			display: none;
		}	

		#innerDiv {
		    width: 75%;
		    margin: 0 auto;
		}

		#brown_bag {
			width: 50px;
			height: 50px;
			padding: 10px;
		}

		#submitOrderForm label{
		    display: inline-block;
		    float: left;
		    clear: left;
		    width: 180px;
		    text-align: right;
		    padding: 8px;
		}
		#submitOrderForm input {
			display: inline-block;
			float: left;
			font-size: 12px;
			padding: 8px;
			width: 180px;
		}

	</style>

	<link rel="stylesheet" type="text/css" media="screen and (max-device-width: 720px)" href="styles/small-devices.css" />

	<script>
		$(document).ready(function(){
			// Check one of the breads
			$(function() {
			    var $radios = $('input:radio[name=bread]');
			    if($radios.is(':checked') === false) {
			        $radios.filter('[value=d433c499-ad93-47d8-884f-6bd92441771c]').prop('checked', true);
			    }
			});

			$('input:radio[name=moregrub]').click(function() {
				if($('input:radio[name=moregrub]').is(':checked')) { 
					// Enable the button
					$("#moregrub-button").prop('disabled', false);
				}
			});
			$("#sandwiches").click(function(){
				$("#byo-list").hide(100);
				$("#more-list").hide(100);
				$("#sandwich-list").toggle(200);
		  	});
			$("#byo").click(function(){
				$("#sandwich-list").hide(100);
				$("#more-list").hide(100);
				$("#byo-list").toggle(200);
		  	});
			$("#more-grubs").click(function(){
				$("#byo-list").hide(100);
				$("#sandwich-list").hide(100);
				$("#more-list").toggle(200);
		  	});

		  	// Enable sandwich button after selection has been made
		  	$(".pick-sandwich").change(function(){
		  		$("#sandwich-button").prop('disabled', false);
		  	});

		  	// Get current count and totals
		  	function GetCountAndTotal(JSONstring) {
		  		var price = 0;

				var JSON = $.parseJSON(JSONstring);
				console.log(JSON.length);
				for (var key in JSON) {
					//console.log(JSON[key]['price']);
					price += JSON[key]['price'];
				}
				console.log(price);

				htmlString = "View Pick-up Order ( " + JSON.length + " )<br>Estimated Total: $" + price.toFixed(2);
				// Update cart totals
				$('#update-order').html(htmlString);
		  	};

		  	// Capture sandwich
		  	$('#sandwich-form').on('submit', function(e) { //use on if jQuery 1.7+
		        e.preventDefault();  //prevent form from submitting
				var data = $("#sandwich-form :input").serializeArray();
				console.log(data); //use the console for debugging, F12 in Chrome, not alerts

				$.post( "process.php", 
						data,
						function(output) {
							GetCountAndTotal(output);
						}
				);
				
				// Reset the form
				$('#sandwich-form').each(function(){
					this.reset();
				});
				// Disable the button
				$("#sandwich-button").prop('disabled', true);
				$(".main-categories").hide(100);

			});

		  	// Build your own sandwich form
			$('#byo-form').on('submit', function(e) { //use on if jQuery 1.7+
		        e.preventDefault();  //prevent form from submitting
				var data = $("#byo-form :input").serializeArray();
				console.log(data); //use the console for debugging, F12 in Chrome, not alerts

				$.post( "process.php", 
						data,
						function(output) {
							GetCountAndTotal(output);
						} );
				
				// Reset the form
				$('#byo-form').each(function(){
					this.reset();
				});

				$(".main-categories").hide(100);
			});

			// More grubs form
			$('#moregrub-form').on('submit', function(e) { //use on if jQuery 1.7+
		        e.preventDefault();  //prevent form from submitting
				var data = $("#moregrub-form :input").serializeArray();
				console.log(data); //use the console for debugging, F12 in Chrome, not alerts

				$.post( "process.php",
						 data,
						 function(output){
						 	GetCountAndTotal(output);
						 } );
				
				// Reset the form
				$('#moregrub-form').each(function(){
					this.reset();
				});

				// Disable the button
				$("#moregrub-button").prop('disabled', true);

				$(".main-categories").hide(100);
			});

			// Reset the cart
			$("#resetCart").click(function(){
				data = { "type" : "resetCart" };
				console.log(data);
				$.post( "process.php", data );

				htmlString = "View Pick-up Order ";
				$('#update-order').html(htmlString);
		  	});

		  	// Order what's in the cart
			$("#order-button").click(function(){
				var data = $("#submitOrderForm :input").serializeArray();
				console.log(data);
				$.post( "process.php", 
						data,
						function(output) {
							$("#php-order").html(output);
							console.log(output);
						});

		  	})
		  	.magnificPopup({
				  items: {
				      src: '#order-results',
				      type: 'inline'
				  }
			});;

		  	// VIEW ORDER
			$('.view-order').click(function() {
				data = { "type" : "viewOrder" };
				$.post( "process.php", 
						data,
						function(output) {
							console.log(output);
							$("#dynamic-order").html(output);
							if(output == "You have nothing in your order!") {
								// Disable the button
								$("#order-button").prop('disabled', true);
							}
							else {
								// Enable the button
								$("#order-button").prop('disabled', false);
							}
						}
				);
			})
			.magnificPopup({
				  items: {
				      src: '#view-order-form',
				      type: 'inline'
				  }
			});
		});
	</script>
</head>
<body>
	<div id="outerDiv">
		<div id="innerDiv">
		<div class="view-order">
			<table>
				<tr><td><img id="brown_bag" src="assets/paperbag_brown.png"></td><td><span id="update-order"> View Pick-up Order 
					<?php
						if (isset($price)) {
							echo " ( ".count($_SESSION['cart'])." )<br>Estimated Total: $".number_format($price,2);
						}
					?><span></td></tr>
			</table>
	</div>
	<h3 id="sandwiches">Sandwiches</h3>
	<div id="sandwich-list" style="display: none;" class="main-categories">
		All sandwiches include Grub sauce, mayo, lettuce, tomatoes, red onions<br><br>
		<form id="sandwich-form">
			<?php
				foreach ($json as $sandwich){
					if ($sandwich['id'] == "d744d4b6-97e6-4943-8f86-58e67655b917") {
						// Skip BYO
						continue;
					}
					$chars_to_remove = array("'");
					$name 			= $sandwich["name"];
					$description 	= (isset($sandwich["description"])) ? $sandwich["description"] : "";
					$price			= number_format($sandwich["price"]/100, 2);
					echo "<input id='".$sandwich['id']."' class='pick-sandwich' type='radio' name='sandwich' value='".$sandwich['id']."'><label for='".$sandwich['id']."'><span class='sandwich-name'>".$name." - $".$price."</span></label>";
					echo "<br><div class='sandwich-desc'>".$description."</div><br>";
				}
			?>
			<span class="sandwich-xtra">
				<hr class="top-hr">
				<span style="padding-bottom: 10px;">Select Your Bread</span>
				<hr class="bottom-hr">
				<span class='hidden-breaks'><br></span>
				<?php
					foreach ($breads as $bread) {
						echo "<input id='".$bread['id']."' type='radio' class='bread' name='bread' value='".$bread['id']."'><label for='".$bread['id']."'>".$bread['name']."</label>\n";
						echo "<span class='hidden-breaks'><br></span>";
					}
				?>
				<hr class="top-hr">
				<span style="padding-bottom: 10px;">Free Add-ons</span>
				<hr class="bottom-hr">

				<span class='hidden-breaks'><br></span>
				<input id="pickles" type="checkbox" name="add-ons[]" value="Pickles"><label for="pickles">Pickles</label><span class='hidden-breaks'><br></span>
				<input id="peppers" type="checkbox" name="add-ons[]" value="Pepperoncinis"><label for="peppers">Pepperoncinis</label><span class='hidden-breaks'><br></span>
				<input id="jalepenos" type="checkbox" name="add-ons[]" value="Jalepenos"><label for="jalepenos">Jalepeños</label>
				<br><br><span style="padding-bottom: 10px;">Additional Notes<span><br>
				<textarea name="notes" rows="4" cols="30"></textarea>
			</span>
			<input type="hidden" name="type" value="sandwich">
		</form>
		<br>
		<button id="sandwich-button" disabled="disabled" form="sandwich-form" type="submit">Add to Order</button>
	</div>
	<h3 id="byo">Build Your Own</h3>
	<div id="byo-list" style="display: none;" class="main-categories">
		All sandwiches include Grub sauce, mayo, lettuce, tomatoes, red onions<br><br>
		<form id="byo-form">
			<hr>
			<span class="span-header">Select Your Bread</span>
			<hr>
				<?php
					$counter = 0;
					foreach ($breads as $bread) {
						echo "<input id='byo-".$bread['id']."' type='radio' class='bread' name='bread' value='".$bread['id']."'><label for='byo-".$bread['id']."'>".$bread['name']."</label>\n";
						echo "<span class='hidden-breaks'><br></span>";
						$counter++;
					}
				?>
			<hr>
			<span class="span-header">Select Your Meat</span>
			<hr>
				<?php
					foreach ($byo["meats"] as $meat) {
						echo "<input id='".$meat['id']."' type='radio' class='meat' name='meat' value='".$meat["id"]."'><label for='".$meat['id']."'>".$meat["name"]."</label>\n";
						echo "<span class='hidden-breaks'><br></span>";
					}
				?>
			<hr>
			<span class="span-header">Select Your Cheese ($0.50 Each)</span>
			<hr>
				<?php
					foreach ($byo["cheeses"] as $cheese) {
						echo "<input id='".$cheese['id']."' type='checkbox' class='cheese' name='cheese[]' value='".$cheese["id"]."'><label for='".$cheese['id']."'>".$cheese["name"]."</label>\n";
						echo "<span class='hidden-breaks'><br></span>";
					}
				?>
			<hr>
			<span style="padding-bottom: 10px;" class="span-header">Free Add-ons</span>
			<hr>
				<input id="byo-pickles" type="checkbox" name="byo-add-ons[]" value="Pickles"><label for="byo-pickles">Pickles</label><span class='hidden-breaks'><br></span>
				<input id="byo-peppers" type="checkbox" name="byo-add-ons[]" value="Pepperoncinis"><label for="byo-peppers">Pepperoncinis</label><span class='hidden-breaks'><br></span>
				<input id="byo-jalepenos" type="checkbox" name="byo-add-ons[]" value="Jalepenos"><label for="byo-jalepenos">Jalepeños</label><span class='hidden-breaks'><br></span>
			<hr>
			<span class="span-header">Add Extras</span>
			<hr>
				<?php
					foreach ($byo["extras"] as $extra) {
						echo "<input id='".$extra['id']."' type='checkbox' class='extra' name='extra[]' value='".$extra["id"]."'><label for='".$extra['id']."'>".$extra["name"]. " ($".number_format($extra["price"]/100, 2).")" ."</label>\n";
						echo "<span class='hidden-breaks'><br></span>";
					}
				?>
			<hr>
			<span class="span-header">Spreads</span><hr>
				<?php
					$counter = 0;
					$rowCounter = 0;
					foreach ($byo["spreads"] as $spread) {
						echo "<input id='".$spread['id']."' type='checkbox' class='spread' name='spread[]' value='".$spread["id"]."'><label for='".$spread['id']."'>".$spread["name"]."</label>\n";
						$rowCounter++;
						$counter++;
						if ($rowCounter == 3 && $counter != count($byo["spreads"])) {
							$rowCounter = 0;
							echo "<br><br>";
						}
					}
				?>
			<br><br><span style="padding-bottom: 10px;" class="span-header">Additional Notes<span><br>
			<textarea name="notes" rows="4" cols="50"></textarea>
			<input type="hidden" name="type" value="byo">
		</form>
		<br><button id="byo-button" form="byo-form" type="submit">Add to Order</button>
	</div>
	<h3 id="more-grubs">More Grubs</h3>
	<div id="more-list" style="display: none;" class="main-categories">
		<form id="moregrub-form">
			<?php
					foreach ($moregrub as $grub) {
						echo "<input id='".$grub['id']."' type='radio' class='moregrub' name='moregrub' value='".$grub["id"]."'><label for='".$grub['id']."'>".$grub["name"]." ($".number_format($grub["price"]/100, 2).")</label>\n";
						echo "<br><br>";
					}
				?>
				<input type="hidden" name="type" value="moregrub">
		</form>
		<button id="moregrub-button" form="moregrub-form" type="submit" disabled="disabled">Add to Order</button>
	</div>
	<table style="width: 100%; border-spacing: 0px;"><tr>
		<td style="text-align: center;"><h3 class="view-order" style="background-color: #80E680;">Place Order</h3></td>
		<td style="text-align: center;"><h3 id="resetCart" style="background-color: #CD9B9B;">Clear Order</h3></td>
	</tr></table>
<!-- END INNER DIV --></div>
	<div id="view-order-form" class="white-popup mfp-hide" style="text-align:center;">
	<div id="dynamic-order">Nothing's in your order!</div>
	<form id="submitOrderForm">
		<div>
			<input type="hidden" name="type" value="sendOrder"><br>
			<label for="sof1">Name</label><input id="sof1" type="text" name="contact-name" placeholder="[required]"><br>
			<label for="sof2">Email</label><input id="sof2" type="email" name="email" placeholder="[required]"><br>
			<label for="sof3">Phone</label><input id="sof3" type="tel" name="phone" placeholder="[required]"><br>
		</div>
	</form>
	<br><br><br><br>
		<span>Information collected will only be used for communication about this order.<br><br></span>
		<button id="order-button">Place Order</button>
	</div>

	<div id="order-results"class="white-popup mfp-hide" style="text-align:center;">
		<div id="php-order"></div>
		<span>You are connected from: <?php echo $_SERVER['REMOTE_ADDR']; ?><br><br></span>
	</div>
</div>
</body>
</html>