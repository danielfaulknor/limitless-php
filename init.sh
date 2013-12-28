#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PREVIOUSDIR=`pwd`
cd $DIR
./limitlessD.py &
cd $PREVIOUSDIR
