# aas2rss
Turn the AAS job register into a RSS feed

A live version is hosted at [pmelchior.net](http://aas2rss.pmelchior.net/)

Simply clone this repository on your webserver and run `install.sh` to create files and directories. The directories are used to store the relevant pieces of each AAS job posting and the aggregated RSS files, respectively. The `lock` file is to prevent the script from corrupting the feeds when called in short succession, while the `logfile.log` maintains a log of the requests. The latter can analyzed with the `.extra/logstats.py` script to show the number of requests per day.
