<?php

	/* server timezone */
	define('CONST_SERVER_TIMEZONE', 'UTC');
	 
	/* server dateformat */
	define('CONST_SERVER_DATEFORMAT', 'Y-m-d H:i:s');

	class ScheduleChecker
	{

		public function now($str_user_timezone,
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

		public function UTCtoLocal($timeString, $locale_string) {
			$utc_date = DateTime::createFromFormat(
	            'Y-m-d H:i:s', 
	            $timeString, 
	            new DateTimeZone('UTC')
				);

			$output = $utc_date;
			$output->setTimeZone(new DateTimeZone($locale_string));

			return $output->format('Y-m-d H:i:s');
		}

		public function CheckSchedule(){
			$string = file_get_contents("assets/schedule.json");
			$schedule=json_decode($string,true);
			// Get the current UTC time and convert it to local
			$utc = self::now('America/Los_Angeles');
			$local = self::UTCtoLocal($utc, 'America/Los_Angeles');

			$dayofweek = date('l', strtotime($local));
			$date = date('Y-m-d', strtotime($local));

			$openTime = $schedule[strtolower($dayofweek)]['open'];
			$open = new DateTime($date . " " . $openTime);

			$closeTime = $schedule[strtolower($dayofweek)]['close'];
			$close = new DateTime($date . " " . $closeTime);

			$now = new DateTime($local);

			//echo $local;
			//echo $open->format('Y-m-d H:i:s');
			//echo $close->format('Y-m-d H:i:s');
			//echo $openTime;
			//echo $closeTime;

			if (($now >= $open) && ($now <= $close)) {
				return True;
			}
			if ($now > $close) {
				return False;
			}
			if ($now < $open) {
				return False;
			}
		}
	}
?>