#!/usr/bin/python

import mosquitto
import json
import time
from subprocess import call

def on_connect(rc):
        print "Connected to MQTT"

def on_message(msg):
        inbound = json.loads(msg.payload)
	light = inbound[0]
	func = inbound[1]
	try:
		data = inbound[2]
	except IndexError:
		data = ""	

	#print './limitless.php "' + light + '" ' + func + ' ' + data
	call(['./limitless.php', light, func, data])

mqttc = mosquitto.Mosquitto("limitlessD")

mqttc.on_message = on_message
mqttc.on_connect = on_connect

mqttc.connect("127.0.0.1", 1883, 60, False)

mqttc.subscribe("limitless", 0)

while mqttc.loop() == 0:
        pass

