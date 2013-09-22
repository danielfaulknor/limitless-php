#!/usr/bin/php
<?
require_once("config.php");

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
$lights = "";

print_r($hub_list);
print_r($light_list);

if (file_exists("status.json")) $status = json_decode(file_get_contents("status.json"), true);
else $status = array();

$light_name = $argv[1];
$light_function = $argv[2];
if(isset($argv[3])) $light_options = $argv[3];
if(isset($argv[4])) $light_options2 = $argv[4];
$light_hub = $light_list["$light_name"]['hub'];
$ip = $hub_list["$light_hub"]['ip'];
$port = $hub_list["$light_hub"]['port'];
print_r($status);
switch ($light_function) {
	case "on":
		if ($status["$light_name"]['status']) die ("Light must be off!");
		$command = "zone" . $light_list["$light_name"]['group'] . "on";
		$status["$light_name"]['status'] = true;
		exec("./sendcmd.sh $ip $port $command");
	break;
	case "off":
		if (!$status["$light_name"]['status']) die ("Light must be on!");
		$command = "zone" . $light_list["$light_name"]['group'] . "on";
		exec("./sendcmd.sh $ip $port $command");
		$i = 0;
		$steps = (-1 - $status["$light_name"]['brightness']) * -1;
		echo $steps;
		while ($i < $steps) {
			$command = "brightnessdown";
			exec("./sendcmd.sh $ip $port $command");
			$i++;
			echo "Step: $i \n";
			sleep(1);
		}
		$command = "zone" . $light_list["$light_name"]['group'] . "off";
		exec("./sendcmd.sh $ip $port $command");
		$status["$light_name"]['status'] = false;
		$status["$light_name"]['brightness'] = 0;
	break;
	case "brightness":
		$loop = 0;
		do {
			if (!$status["$light_name"]['status']) die ("Light must be on!");
			$command = "zone" . $light_list["$light_name"]['group'] . "on";
			$currentbrightness = $status["$light_name"]['brightness'];
				echo "Current: $currentbrightness \n";
			exec("./sendcmd.sh $ip $port $command");
			$done = true;
			$loop++;
			if ($light_options > 10 || $light_options < 0 || !is_numeric($light_options)) die("Invalid option!");
			if ($light_options == $currentbrightness) die("Already there!");
			if (is_null($currentbrightness)) {
				$steps = -10;
				$done = false;
				$status["$light_name"]['brightness'] = 0;
			}
			else {
				$steps = $light_options - $currentbrightness;
				echo "Current: $currentbrightness \n";
				sleep(1);
			}
			if ($steps < 0) {
				$steps = $steps * -1;
				$iteration = 0;
				do {
				        echo "Step down: " . $iteration . "\n";
					$command = "brightnessdown";
					exec("./sendcmd.sh $ip $port $command");
					$iteration++;
					usleep(100);
				} while ($iteration < $steps);
			}
			else {
				$iteration = 0;
				do {
               	         		echo "Step up: " . $iteration . "\n";
					$command = "brightnessup";
					exec("./sendcmd.sh $ip $port $command");
					$iteration++;
					usleep(100);
                        	} while ($iteration < $steps);
			}
		echo "Loop: $loop \n";
		} while ($done == false);
		$status["$light_name"]['brightness'] = $light_options;
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
