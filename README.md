# aas2rss
Turn the AAS job register into a RSS feed

Simply put this script on your webserver, create subdirectories `cache` and `xml` and an empty file name `lock` with permissions for the webserver to read-write-execute. The directories are used to store the relevant pieces of each AAS job posting and the aggregated RSS files, respectively. The `lock` file is to prevent the script from corrupting the feeds when called in short succession.
