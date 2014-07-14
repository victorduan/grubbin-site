<?php

	$ini_array = parse_ini_file("../config.ini", true);

	define("SANDWICH_ID", $ini_array['square']['sandwich']);
	define("BREAD_ID", $ini_array['square']['bread']);
	define("CHEESE_ID", $ini_array['square']['cheese']);
	define("SAUCE_ID", $ini_array['square']['sauce']);
	define("MEAT_ID", $ini_array['square']['meat']);
	define("EXTRAS_ID", $ini_array['square']['extras']);
	define('MORE_GRUB', $ini_array['square']['moregrub']);
	define('SQUARE_TOKEN', $ini_array['square']['token']);

	

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

	function SquareConnect($endpoint) {
		curl_setopt_array($ch = curl_init(), array(
		CURLOPT_URL => $endpoint,
		CURLOPT_RETURNTRANSFER => true
		));

		$headers = array(
		    'Authorization: bearer '. SQUARE_TOKEN,
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		curl_close($ch);

		return json_decode($result);
	}

	function GenerateItems($results, $cat_id) {
		$menu = array();
	
		foreach ($results as $item) {
			$item = objectToArray($item);

			if ((isset($item['category_id'])) && ($item['category_id'] == $cat_id)) {

				if (count($item['variations']) == 1) {
					$s = array();
					$s['id'] = $item['id'];
					$s['name'] = $item['name'];
					$s['price'] = $item['variations'][0]['price_money']['amount'];

					if (isset($item['description'])) {
						$s['description'] = $item['description'];
					}
					array_push($menu, $s);
				}

				elseif (count($item['variations']) > 1) {
					foreach ($item['variations'] as $v) {
						$temp_array = array();
						$temp_array['id'] = $v['id'];
						$temp_array['name'] = $item['name']." - ".$v['name'];
						$temp_array['price'] = $v['price_money']['amount'];

						if (isset($item['description'])) {
							$temp_array['description'] = $item['description'];
						}

						array_push($menu, $temp_array);
					}

				}
			}
		}

		return $menu;
	}

	function GenerateModifiers($results, $cat_id) {
		$array = array();

		foreach ($results as $modifier) {
			$modifier = objectToArray($modifier);

			if ($modifier['id'] == $cat_id) {
				foreach ($modifier['modifier_options'] as $mod) {
					$x = array();
					$x['id'] = $mod['id'];
					$x['name'] = $mod['name'];
					$x['price'] = (isset($mod['price_money']) ? $mod['price_money']['amount'] : 0 );

					array_push($array, $x);
				}
			}
		}

		return $array;

	}

	if (isset($_GET['type'])) {
		switch($_GET['type']) {
			case 'sandwiches':
				$items = SquareConnect("https://connect.squareup.com/v1/me/items");
				$sandwiches = GenerateItems($items, SANDWICH_ID);
				try {
					$f = fopen("assets/sandwiches.json", "w");
					fwrite($f, json_encode($sandwiches));
					fclose($f);
				}
				catch(Exception $e) {
					echo 'Error message: ' .$e->getMessage();
				}

				break;

			case 'moregrub':
				$items = SquareConnect("https://connect.squareup.com/v1/me/items");
				$moregrub = GenerateItems($items, MORE_GRUB);
				try {
					$f = fopen("assets/moregrub.json", "w");
					fwrite($f, json_encode($moregrub));
					fclose($f);
				}
				catch(Exception $e) {
					echo 'Error message: ' .$e->getMessage();
				}

				break;

			case 'byo':
				$byo = array();

				$modifiers = SquareConnect("https://connect.squareup.com/v1/me/modifier-lists");
				$cheeses = GenerateModifiers($modifiers, CHEESE_ID);
				$sauces = GenerateModifiers($modifiers, SAUCE_ID);
				$meats = GenerateModifiers($modifiers, MEAT_ID);
				$extras = GenerateModifiers($modifiers, EXTRAS_ID);

				$byo['meats'] = $meats;
				$byo['cheeses'] = $cheeses;
				$byo['extras'] = $extras;
				$byo['spreads'] = $sauces;

				try {
					$f = fopen("assets/byo.json", "w");
					fwrite($f, json_encode($byo));
					fclose($f);
				}
				catch(Exception $e) {
					echo 'Error message: ' .$e->getMessage();
				}

				break;

			case 'bread':
				$modifiers = SquareConnect("https://connect.squareup.com/v1/me/modifier-lists");
				$breads = GenerateModifiers($modifiers, BREAD_ID);

				try {
					$f = fopen("assets/breads.json", "w");
					fwrite($f, json_encode($breads));
					fclose($f);
				}
				catch(Exception $e) {
					echo 'Error message: ' .$e->getMessage();
				}

				break;

			default:
				echo "Unknown parameter. Exiting";
				break;

		}
	}

	else {
		echo "Keep Calm and Move Along. There is nothing to see here.";
	}
	
?>