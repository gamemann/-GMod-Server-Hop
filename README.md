# Installation
1. Create a MySQL database.
2. Import the mysql.sql file.
3. Go to the servers table and insert a row with your server details.
4. Put the "Server Hop" addon on your server.
5. Configure addons/serverhop/lua/shp_server.lua (MySQL information, etc).
6. Put the web files on your web server.
7. Configure the web file web/refreshserverlist.php (MySQL information, etc).
7. Create a cronjob and set the file as web/refreshserverlist.php (this is to refresh the server's details every x minutes, seconds, etc. GFL has theirs set to refresh every 5 minutes).

# Description
As promised in June, here's the Garry's Mod server list! As you may tell from looking at the SQL file, GFL has a global server list. However, I am currently not releasing my Server Hop plugin for SourceMod. I've spent the thirty minutes preparing this addon. However, I did not test this. Therefore, there may be addon-breaking issues. If there is, let me know (submit an issue under this repo) and I will get it resolved. I also didn't originally plan on releasing this to the public. Therefore, the code may seem a bit messy. Eventually, I plan on recoding this and making it more public-friendly. Enjoy!

# MySQL Column Descriptions
`id` - ID of the server (auto increment)
`name` - The server name that displays in the ad (e.g. TTT)
`location` - The location ID (1 - USA, 2 - Germany, ...)
`ip` - The IP of the server. This is mainly used for A records (e.g. ze.gflclan.com)
`publicip` - The number IP of the server (e.g. 64.74.97.72)
`port` - The join port for the server (e.g. 27015)
`qport` - The query port for the server (e.g. 27015, not used)
`description` - A description of the server. Not used in the GMod Server Hop.
`gameid` - The server's game ID (e.g. 1 for Garry's Mod)
`players` - The current amount of players (value should be 0 when inserting a new row)
`playersmax` - The maximum players of the server (value should be 0 when inserting a new row)
`bots` - The amount of bots the server currently has (value should be 0 when inserting a new row)
`map` - The current map the server is on
`password` - The password of the server (currently useless)

