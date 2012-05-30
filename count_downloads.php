<?php
// Load WordPress and set domain (needed for WordPress Multi-Site setups)
$_SERVER['HTTP_HOST'] = "ho.st";
include '/var/www/wordpress/wp-load.php';

// Open input
$stdin = fopen('php://stdin', 'r');

// Process input
while ( !feof( $stdin ) ):
	// Parse line
	if ( preg_match( "^(\+|\-) (.*) \- \[(.*)\] \"(GET|POST) (.*) (.*)\" (\d*) (\d*) \"(.*)\" \"(.*)\"^", trim( fgets( $stdin ) ), $parts ) ):
		// Check for bot
		if ( false !== strpos( strtolower( $parts[10] ), "bot" ) ) continue;
		
		// Check if status is between 200 and 299
		if ( false == ( $parts[7] > 199 && $parts[7] < 300 ) ) continue;
		
		// Get show id
		if ( preg_match( "^\(.*)\.(mp3|m4a|ogg)^", $parts[5], $show_id ) ):
			// Get post id
			$post_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'audio' AND meta_value = %s", $show_id[1]));

			// Meta key
			$meta_key = ( "+" == $parts[1] ) ? "downloads" : "streams";

			// Update download counter
			$downloads = (int) get_post_meta($post_id, $meta_key . "_" . $show_id[2], true);
			update_post_meta($post_id, $meta_key . "_" . $show_id[2], $downloads+1);
		endif;
	endif;
	
	// Clear
	unset($parts);
	unset($show_id);
	unset($line);
endwhile;