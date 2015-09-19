#!/bin/sh

# create logfile and lock(file)
touch lock
touch logfile.log
chmod 777 logfile.log

# create cache and xml dirs
mkdir cache
mkdir xml
chmod 777 cache xml

