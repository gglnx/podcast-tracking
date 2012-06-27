<?php
// Open input
$stdin = fopen('php://stdin', 'r');

// Open database connection
$db = new Mongo('mongodb://127.0.0.1');

// Process input
while ( !feof( $stdin ) ):
	// Parse line
	if ( preg_match( "/(.*) \[(.*)\] \'GET (.*) (.*)\' (\d*) (\d*) \'(.*)\' \'(.*)\'/is", trim( fgets( $stdin ) ), $parts ) ):
		// Check if it's a 200 (OK) or 206 (Partial Content) request
		if ( false == in_array( (int) $parts[5], array( 200, 206 ) ) ) continue;
		
		// Check if it's a audio file
		$episode = pathinfo($parts[3]);
		if ( false == in_array( $episode['extension'], array( "mp3", "ogg", "m4a" ) ) ) continue;

		// Don't track PritTorrent
		if ( "85.10.246.236" == $parts[1] || "-" == $parts[8] ) continue;

		// Build query
		$query = array(
			'episode' => $episode['filename'],
			'type' => $episode['extension'],
			'ip' => md5($parts[1]),
			'user_agent' => $parts[8],
			'downloaded_at' => array('$gte' => new MongoDate(time() - (60*60*6)))
		);
		
		// Check if this download, on this IP and user agent, has already been tracked
		if ( null !== $db->podcasts->downloads->findOne( $query ) ) continue;
		
		// Track download
		$db->podcasts->downloads->insert(array(
			"type" => $episode['extension'],
			"episode" => $episode['filename'],
			"downloaded_at" => new MongoDate(),
			"ip" => md5($parts[1]),
			"user_agent" => $parts[8],
		));
	endif;
	
	// Clear
	unset($parts);
endwhile;

// Close
$db->close();