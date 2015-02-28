#!/usr/bin/python
import os
from sys import argv
lines = os.popen("bzcat " + argv[1] + " | grep '>' | awk '{ print $1 }'").readlines()
access  = {}
for line in lines:
	line = line.strip()
	if line in access.keys():
		access[line] = access[line] + 1
	else:
		access[line] = 1

keys = access.keys()
keys.sort()
for key in keys:
	print key + " " + str(access[key])
