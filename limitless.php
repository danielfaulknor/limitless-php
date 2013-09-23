#!/usr/bin/php
<?

function fire($light_name, $light_function, $light_data = false) {
		global $status;

		//Setup Hub variables for the chosen light.
		$light_hub = $light_list["$light_name"]['hub'];
		$ip = $hub_list["$light_hub"]['ip'];
		$port = $hub_list["$light_hub"]['port'];

		switch ($light_function) {
			case "on":
				$status["$light_name"]['status'] = true;
				exec("./sendcmd.sh $ip $port zone" . $light_list["$light_name"]['group'] . "on");
			break;
			case "off":
				$status["$light_name"]['status'] = false;
				exec("./sendcmd.sh $ip $port zone" . $light_list["$light_name"]['group'] . "off");
			break;
			case "brightness":
				$loop = 0;
				do {
					//require on status and then fire on command so the light is listening for
					require_status($light_name, "on");
					fire($light_name, "on");
					$currentbrightness = $status["$light_name"]['brightness'];
					$done = true;
					$loop++;

					//check that we got a valid brightness option and that we're not already where they requested
					//TO DO: Add support for RGB/RGB+W Bulbs
					if ($light_data > 10 || $light_data < 0 || !is_numeric($light_data)) die("Invalid option!");
					if ($light_data == $currentbrightness) die("Already there!");

					//We don't have a brightness set, so we're going to loop twice, the first one takes the brightness to 0
					if (is_null($currentbrightness)) {
						$steps = -10;
						$done = false;
						$status["$light_name"]['brightness'] = 0;
					}
					//We're good to go, calculate the difference between the steps.
					else {
						$steps = $light_options - $currentbrightness;
					}

					//Sleep for 200ms to allow light to respond
					usleep(200);

					// if steps required is less than zero then it means brightness down.
					if ($steps < 0) {
						$steps = $steps * -1;
						$iteration = 0;
						do {
							exec("./sendcmd.sh $ip $port brightnessdown");
							$iteration++;
							//sleep for 100ms so the light has time to respond
							usleep(100);
						} while ($iteration < $steps);
					}
					// otherwise we are going up!
					else {
						$iteration = 0;
						do {
							exec("./sendcmd.sh $ip $port brightnessup");
							$iteration++;
							//sleep for 100ms so the light has time to respond
							usleep(100);
		                } while ($iteration < $steps);
					}
				} while ($done == false);
				$status["$light_name"]['brightness'] = $light_data;
			break;
		}
		
}

function require_status($light_name, $status_required) {
	if ($status["$light_name"]['status'] != $status_required) die ("Light must be ". $status_required . "!");
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
if(isset($argv[4])) $light_options2 = $argv[4];

switch ($light_function) {
	case "on":
		require_status($light_name, "off");
		fire($light_name, "on");
	break;
	case "off":
		require_status($light_name, "on");
		fire($light_name, "brightness", "0");
		fire($light_name, "off");
	break;
	case "brightness":
		fire($light_name, "brightness", $light_options);
	break;

	case "strobe":
		$to_time = time() + $light_options;
		$speed = $light_options2;
	        $command = "zone" . $light_list["$light_name"]['group'] . "on";
        	exec("./sendcmd.sh $ip $port $command");
		echo $command;
                $i = 0;
	       	$steps = (10 - $status["$light_name"]['brightness']) * -1;
                while ($i < $steps) {
                	$command = "brightnessup";
                        exec("./sendcmd.sh $ip $port $command");
                        $i++;
                        echo "Step: $i \n";
                }
		do {
			$command = "zone" . $light_list["$light_name"]['group'] . "off";
                	exec("./sendcmd.sh $ip $port $command");
			sleep($speed);
			$command = "zone" . $light_list["$light_name"]['group'] . "on";
                	exec("./sendcmd.sh $ip $port $command");
			sleep($speed);
		} while (time() < $to_time);

        $command = "zone" . $light_list["$light_name"]['group'] . "on";
        	exec("./sendcmd.sh $ip $port $command");
		$i = 0;
		$steps = (0 - $status["$light_name"]['brightness']) * -1;
		echo $steps;
		while ($i < $steps) {
			$command = "brightnessdown";
			exec("./sendcmd.sh $ip $port $command");
			$i++;
			echo "Step: $i \n";
		}
		$command = "zone" . $light_list["$light_name"]['group'] . "off";
		exec("./sendcmd.sh $ip $port $command");
		$status["$light_name"]['status'] = false;
		$status["$light_name"]['brightness'] = 0;
	break;
}

file_put_contents("status.json",json_encode($status));
