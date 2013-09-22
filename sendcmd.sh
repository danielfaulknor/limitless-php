#!/bin/bash

if [ -z "$1" ] ; then
    echo "You must enter a parameter: "
    echo "  e.g. $0 172.18.1.3 8899 allon"
    exit 1
fi

incmd="$3"
ipaddress="$1"
portnum="$2"


allon="\x35\00\x55"
alloff="\x39\00\x55"
zone1on="\x38\00\x55"
zone1off="\x3B\00\x55"
zone2on="\x3D\00\x55"
zone2off="\x33\00\x55"
zone3on="\x37\00\x55"
zone3off="\x3A\00\x55"
zone4on="\x32\00\x55"
zone4off="\x36\00\x55"
brightnessup="\x3C\00\x55"
brightnessdown="\x34\00\x55"

eval incmd=\$$incmd

echo -n -e "$incmd" >/dev/udp/"$ipaddress"/"$portnum"
