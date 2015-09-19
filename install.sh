#!/bin/sh

# create logfile and lock(file)
touch lock
touch logfile
chmod 777 logfile

# create cache and xml dirs
mkdir cache
mkdir xml
chmod 777 cache xml

