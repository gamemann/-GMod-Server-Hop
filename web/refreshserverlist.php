<?php

	// Config
	$dbHost = 'localhost';
	$dbUser = 'user';
	$dbPass = 'pass';
	$dbName = 'dbname';
	require 'refreshserverlist/rcon.funcs.php';
	
	function bf3_query($ip, $port, $password) 
	{

		$clientSequenceNr = 0;
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_connect($socket, $ip, $port);
		socket_set_nonblock($socket);

		if (!$socket) 
		{
			echo "bf3: could not open socket. Skipped.\n";
			continue;
		}

		if ($socket !== False) 
		{

			// Get player list, skip if server's empty
			// listPlayers does not require login
			$response = rconCommand($socket, 'listPlayers all');

			if ($response[0] != 'OK') 
			{
				socket_close($socket);
				continue;
			}

			$players = array();
			for ($i = 10; $i < count($response); $i += 8) 
			{
				array_push($players, $response[$i]);
			}

			if (empty($players)) 
			{
				echo "BF3: Empty.\n";
				socket_close($socket);
				continue;
			}

			$numPlayers = count($players);

			// All other needed commands require login
			$response = rconCommand($socket, 'login.hashed');

			if ($response[0] != 'OK') 
			{
				echo "BF3: could not login. Skipped.\n";
				socket_close($socket);
				continue;
			}

			$response = rconCommand($socket, 'login.hashed ' . generatePasswordHash($response[1], $password));

			switch ($response[0]) 
			{
				case 'OK':
				break;

				case 'PasswordNotSet':
					echo "BF3: password not set on server. Skipped.\n";
					socket_close($socket);
					continue 2;
				break;

				case 'InvalidArguments':
					echo "BF3: on login: InvalidArguments. Skipped.\n";
					socket_close($socket);
					continue 2;
				break;

				default:
					echo "BF3: on login: unexpected output. Skipped.\n";
					socket_close($socket);
					continue 2;
				break;
			}

			// Get max players of server
			$response = rconCommand($socket, 'vars.maxPlayers');

			if ($response[0] != 'OK') 
			{
				echo "BF3: on maxPlayers: server error. Skipped.\n";
				socket_close($socket);
				continue;
			}

			$maxPlayers = intval($response[1]);

			rconCommand($socket, 'logout');
		}

		socket_close($socket);
		$info['playersmax'] = $maxPlayers;
		$info['players'] = $numPlayers;
		return $info;
	}
	
	function source_query($ip, $port)
	{

		$server['status'] = 0;
		$server['ip']     = $ip;
		$server['port']   = $port;

		if (!$server['ip'] || !$server['port']) 
		{ 
			exit("EMPTY OR INVALID ADDRESS"); 
		}

		$socket = @fsockopen("udp://{$server['ip']}", $server['port'], $errno, $errstr, 1);

		if (!$socket) 
		{ 
			return $server; 
		}

		stream_set_timeout($socket, 1);
		stream_set_blocking($socket, TRUE);
		fwrite($socket, "\xFF\xFF\xFF\xFF\x54Source Engine Query\x00");
		$packet = fread($socket, 4096);
		@fclose($socket);

		if (!$packet) 
		{ 
			return $server; 
		}

		$header                = substr($packet, 0, 4);
		$response_type         = substr($packet, 4, 1);
		$network_version       = ord(substr($packet, 5, 1));

		$packet_array          = explode("\x00", substr($packet, 6), 5);
		$server['name']        = $packet_array[0];
		$server['map']         = $packet_array[1];
		$server['game']        = $packet_array[2];
		$server['description'] = $packet_array[3];
		$packet                = $packet_array[4];
		$server['players']     = ord(substr($packet, 2, 1));
		$server['playersmax']  = ord(substr($packet, 3, 1));
		$server['bots']        = ord(substr($packet, 4, 1));
		$server['status']      = 1;
		$server['dedicated']   =     substr($packet, 5, 1); 
		$server['os']          =     substr($packet, 6, 1); 
		$server['password']    = ord(substr($packet, 7, 1)); 
		$server['vac']         = ord(substr($packet, 8, 1)); 

		return $server;
	}

	$db = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

	$query = "SELECT * FROM `servers`";
	
	$result = mysqli_query($db, $query);
	while($servers = mysqli_fetch_assoc($result)) 
	{
		$query2 = "SELECT * FROM `games` WHERE `id`='" . $servers['gameid'] . "'";
		$result2 = mysqli_query($db, $query2);
		$special = 0;
		
		if ($result2) 
		{
			$rows = mysqli_num_rows($result2);
			
			if ($rows > 0) 
			{
				while ($temp = mysqli_fetch_assoc($result2)) 
				{
					$special = $temp['special'];
				}
			}
		}
		
		if($special == 1) 
		{
			// BF3 server.
			$q = bf3_query($servers['publicip'], $servers['qport'], $servers['password']);
			$updatequery = "UPDATE `servers` SET `players`=" . $q['players'] . ", `playersmax`=" . $q['playersmax'] . ", `bots`=0, `map`='Unknown' WHERE `id`=" . $servers['id'];
			if(mysqli_query($db, $updatequery)) 
			{
				echo $servers['name'] . ' has been updated!<br />';
			} 
			else 
			{
				echo '<font color="red"><strong>' . $servers['name'] . ' failed to update!</strong></font><br />';
			}
		}
		else 
		{
			$port = $servers['port'];
			if ($servers['qport'] != '') 
			{
				$port = $servers['qport'];
			}
			
			$q = source_query($servers['publicip'], $port);
			
			$updatequery = "UPDATE `servers` SET `players`=" . $q['players'] . ", `playersmax`=" . $q['playersmax'] . ", `bots`=" . $q['bots'] . ", `map`='" . $q['map'] . "' WHERE `id`=" . $servers['id'];
			
			if(mysqli_query($db, $updatequery)) 
			{
				echo $servers['name'] . ' has been updated!<br />';
			} 
			else 
			{
				echo '<font color="red"><strong>' . $servers['name'] . ' failed to update!</strong></font><br />';
			}
		}
	}
	echo '<br />Servers updated!';

?>