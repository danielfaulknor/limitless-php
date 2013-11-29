#!/usr/bin/php
<?

function fire($light_name, $light_function, $light_data = false) {
		global $status;
		global $hub_list;
		global $light_list;

		//Setup Hub variables for the chosen light.
		$light_hub = $light_list["$light_name"]['hub'];
		$ip = $hub_list["$light_hub"]['ip'];
		$port = $hub_list["$light_hub"]['port'];

		switch ($light_function) {
			case "white_on":
				$status["$light_name"]['status'] = true;
				exec("./sendcmd.sh $ip $port white_zone" . $light_list["$light_name"]['group'] . "on");
			break;
			case "rgbw_on":
				$status["$light_name"]['status'] = true;
				exec("./sendcmd.sh $ip $port rgbw_zone" . $light_list["$light_name"]['group'] . "on");
			break;
			case "white_off":
				$status["$light_name"]['status'] = false;
				exec("./sendcmd.sh $ip $port white_zone" . $light_list["$light_name"]['group'] . "off");
			break;
			case "rgbw_off":
				$status["$light_name"]['status'] = false;
				exec("./sendcmd.sh $ip $port white_zone" . $light_list["$light_name"]['group'] . "off");
			break;
			case "white_brightness":
				$loop = 0;
				do {
					//require on status and then fire on command so the light is listening for
					fire($light_name, "white_on");
					$currentbrightness = $status["$light_name"]['brightness'];
					$done = true;
					$loop++;

					//check that we got a valid brightness option and that we're not already where they requested
					if ($light_data > 10 || $light_data < 0 || !is_numeric($light_data)) die("Invalid option!");
					if ($light_data == $currentbrightness) {
						echo("Already there!");
						$skip = true;
					}
					echo "Current: $currentbrightness \n Requested: $light_data \n";
					//We don't have a brightness set, so we're going to loop twice, the first one takes the brightness to 0
					if (is_null($currentbrightness)) {
						$steps = -10;
						$done = false;
						$status["$light_name"]['brightness'] = 0;
					}
					//We're good to go, calculate the difference between the steps.
					else {
						$steps = $light_data - $currentbrightness;
					}

					//Sleep for 200ms to allow light to respond
					usleep(200000);

					// if steps required is less than zero then it means brightness down.
					if ($steps < 0 && $skip == false) {
						$steps = $steps * -1;
						$iteration = 0;
						do {
							echo "Step down: $iteration \n";
							exec("./sendcmd.sh $ip $port white_brightnessdown");
							$iteration++;
							//sleep for 100ms so the light has time to respond
							usleep(200000);
						} while ($iteration < $steps);
					}
					// otherwise we are going up!
					else if ($skip == false) {
						$iteration = 0;
						do {
							echo "Step up: $iteration \n";
							exec("./sendcmd.sh $ip $port white_brightnessup");
							$iteration++;
							//sleep for 100ms so the light has time to respond
							usleep(200000);
		                		} while ($iteration < $steps);
					}
				} while ($done == false);
				$status["$light_name"]['brightness'] = $light_data;
			break;
		}
}

//import user config
require_once("config.php");

//turn config into a format that is easier to use (for the program)
foreach ($hub as $hubs) {
	$name = $hubs['name'];
	$hub_list["$name"]['ip'] = $hubs['ip'];
	$hub_list["$name"]['port'] = $hubs['port'];
}
foreach ($light as $lights) {
	$name = $lights['name'];
	$light_list["$name"]['hub'] = $lights['hub'];
	$light_list["$name"]['type'] = $lights['type'];
	$light_list["$name"]['group'] = $lights['group'];
}


//if we have a status array, then import it, otherwise carry on with no info
//TO DO: Move to MongoDB or MySQL
if (file_exists("status.json")) $status = json_decode(file_get_contents("status.json"), true);
else $status = array();

//Parse command line
$light_name = $argv[1];
$light_function = $argv[2];
if(isset($argv[3])) $light_options = $argv[3];

if(is_null($light_list["$light_name"])) die("Invalid light selected!");

switch ($light_function) {
	case "on":
		switch ($light_list[$light_name][type]) {
			case "white":
				fire($light_name, "white_on");
			break;
		}
		
	break;
	case "off":
		switch ($light_list[$light_name][type]) {
			case "white":
				fire($light_name, "white_brightness", "0");
				fire($light_name, "white_off");
			break;
		}
	break;
	case "brightness":
		switch ($light_list[$light_name][type]) {
			case "white":
				fire($light_name, "white_brightness", $light_options);
			break;
		}
	break;
}

file_put_contents("status.json",json_encode($status));

