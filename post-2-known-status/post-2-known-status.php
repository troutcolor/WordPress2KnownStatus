<?php
/*
Plugin Name: Post 2 Known
Description: A test plugin to figure out how to post to a known site
Author: John Johnston
Author URI:http://johnjohnston.info
Version: 0.1

Questions
---------
Settings would work for one user, can you have per user settings?
Can you alert user if plugin is active and setting not set?
how do you valadate settings?
Can posts have a note if posted to known on their edit page?
How do we find out if it works? What to do about errors?

If  a post comes to wp from known, it probably should not post back, how do I figure that out

*/

defined( 'ABSPATH' ) or die( 'the codex told me to put this here' );


/* Settings  */
 


add_action( 'admin_init', 'known_plugin_settings' );

function known_plugin_settings() {
	register_setting( 'known-settings-group', 'known_apikey' );
	register_setting( 'known-settings-group', 'known_username' );
	register_setting( 'known-settings-group', 'known_url' );
	register_setting( 'known-settings-group', 'known_prefix' );
	register_setting( 'known-settings-group', 'known_usepermalinks' );
}


 function known_plugin_menu() {
	add_options_page( 'Post 2 Known Options', 'Post 2 Known', 'manage_options', __FILE__, 'post_2_known_plugin_options' );
}

 
function post_2_known_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	 
	?>
	<div class="wrap">
	<h2>Post 2 Known Settings</h2>
 <p>You can find your api key at yourknownsite/admin/apitester/</p>	 
 <p>The uri should be your-known-site/status/edit</p>	 
	<form method="post" action="options.php">
	<?php settings_fields( 'known-settings-group' ); ?>
	<?php do_settings_sections( 'known-settings-group' ); ?>
	<table class="form-table">
	<tr valign="top">
	<th scope="row">Known Username</th>
	<td><input type="text" name="known_username" value="<?php echo esc_attr( get_option('known_username') ); ?>" /></td>
	</tr> 
	<tr valign="top">
	<th scope="row">Known URl</th>
 	<td><input type="text" name="known_url" size="35" value="<?php echo esc_attr( get_option('known_url') ); ?>" /></td>
	</tr>
	<tr valign="top">
	<th scope="row">Known API Key</th>
	<td><input type="text" name="known_apikey" value="<?php echo esc_attr( get_option('known_apikey') ); ?>" /></td>
	</tr>
	<tr valign="top">
	<th scope="row">Status Prefix</th>
	<td><input type="text" name="known_prefix" value="<?php echo esc_attr( get_option('known_prefix') ); ?>" /></td>
	</tr>
	<tr valign="top">
	<th scope="row">Use Permalinks</th><td>  <input type="checkbox" name="known_usepermalinks" <?php checked( 'on', get_option('known_usepermalinks' ), true ) ?> ">   </td>
	
	</tr>
	</table> 

	<?php submit_button(); ?>
 
	</form>
	</div>
	<?php 
	echo '</div>';
}
add_action( 'admin_menu', 'known_plugin_menu' );




function post_to_known( $post_id ) {
	
	
	$known_apikey = get_option('known_apikey');
	$known_username = get_option('known_username');
	$known_url = get_option('known_url');
	$known_prefix = get_option('known_prefix');
	$known_usepermalinks = get_option('known_usepermalinks');	
	
	$known_signiture = base64_encode(hash_hmac("sha256", "/status/edit", $known_apikey,true));
 	if ( wp_is_post_revision( $post_id ) )
		return;

	$post_title = get_the_title( $post_id );
	 if ( $known_usepermalinks == 'on' ) {$post_url = get_permalink( $post_id );
	 } else {
	$siteurl = site_url('/');
	$post_url = $siteurl."?p=".$post_id;
	}
	$data = array("body" => "$known_prefix $post_title  $post_url");
	$data_string = json_encode($data);
 
	$ch = curl_init($known_url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','X-KNOWN-USERNAME:'.$known_username,'X-KNOWN-SIGNATURE:'.$known_signiture)
); 
	$result = curl_exec($ch);
	/*
		It would be great to do something here, let the user know....
	*/
}
add_action( 'publish_post', 'post_to_known' );
?>