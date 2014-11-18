<?php
/*
	Plugin Name: User Map
	Plugin URI: http://www.dotsquares.com/
	Description: User Map plugin is a great addon to your wordpress site, it will display all the users of your 											blog/website on google maps, you will be able to see all the registered users on google map, which will help you and other people see from where most of the users are registering to your site.
It also shows active live users on google map, this adds a realtime access to stats of all logged in users. 
	Version: 1.2.2
	Author: Dotsquares
    Text Domain: user-location
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

function fifteen_min_interval($interval) {

    $interval['minutes_15'] = array('interval' => 10, 'display' => 'Once 15 minutes');

    return $interval;
}

add_filter('cron_schedules', 'fifteen_min_interval');

function installScript() {
    include('installScript.php');
	wp_schedule_event(time(), 'minutes_15', 'fifteen_min_event');
}

register_activation_hook( __FILE__, 'installScript' );

add_action( 'fifteen_min_event', 'cron_fifteen_min' );
/**
 * On the scheduled action hook, run the function.
 */
function cron_fifteen_min() { 
   
	    global $wpdb;
	    $tbl_useronline = $wpdb->prefix . "useronline";
	
		$qry = "DELETE FROM $tbl_useronline  where (CURRENT_TIMESTAMP - last_time_login) > 1800";
		
		$ress = $wpdb->query($qry);
		
}

add_action( 'init', 'userMap' );

function userMap() {
	    $user =  wp_get_current_user(); 
		$user_id = $user->data->ID;
		
		global $wpdb;
		$tbl_useronline = $wpdb->prefix . "useronline";
        $tbl_users = $wpdb->prefix . "users";
		if($user_id > 0)
		{
			$query = "SELECT wpuo.user_id FROM $tbl_useronline AS wpuo INNER JOIN $tbl_users AS wpu ON wpu.ID = wpuo.user_id where wpu.ID = $user_id";
		$res = $wpdb->get_results($query);
		//echo "dfsdf<pre>"; print_r($res); die;
			if($res[0]->user_id > 0)
			{
			 $sql = "UPDATE $tbl_useronline SET `last_time_login` = NOW() WHERE $tbl_useronline.`user_id` = $user_id";
			 $wpdb->query($sql);
			}
			
		}
		
	    add_action( 'show_user_profile', 'address_custom_user_profile_fields' );
		add_action( 'edit_user_profile', 'address_custom_user_profile_fields' );
		
		add_action( 'personal_options_update', 'address_save_custom_user_profile_fields' );
		add_action( 'edit_user_profile_update', 'address_save_custom_user_profile_fields' );

	 }
	 
function address_custom_user_profile_fields( $user ) { ?>
	<h3><?php _e('Extra Profile Information', 'user-location'); ?></h3>
	
	<table class="form-table">
		<tr>
			<th>
				<label for="country"><?php _e('country', 'user-location'); ?></label>
            </th>
			<td>
				<input type="text" name="country" id="country" value="<?php echo esc_attr( get_the_author_meta( 'country', $user->data->ID ) ); ?>" class="regular-text" /><br />
				<span class="description"><?php _e('Please enter your country.', 'user-location'); ?></span>
			</td>
		</tr>
        
        
        
        <tr>
			<th>
				<label for="state"><?php _e('state', 'user-location'); ?></label>
            </th>
			<td>
				<input type="text" name="state" id="state" value="<?php echo esc_attr( get_the_author_meta( 'state', $user->data->ID ) ); ?>" class="regular-text" /><br />
				<span class="description"><?php _e('Please enter your state.', 'user-location'); ?></span>
			</td>
		</tr>
        
        
        
        <tr>
			<th>
				<label for="city"><?php _e('city', 'user-location'); ?></label>
            </th>
			<td>
				<input type="text" name="city" id="city" value="<?php echo esc_attr( get_the_author_meta( 'city', $user->data->ID ) ); ?>" class="regular-text" /><br />
				<span class="description"><?php _e('Please enter your city.', 'user-location'); ?></span>
			</td>
		</tr>


	</table>
	
<?php }

function address_save_custom_user_profile_fields( $user_id ) {
	
	if ( !current_user_can( 'edit_user', $user_id ) )
		return FALSE;
	
	update_usermeta( $user_id, 'country', $_POST['country'] );
	update_usermeta( $user_id, 'state', $_POST['state'] );
	update_usermeta( $user_id, 'city', $_POST['city'] );
}

/**
 *  Map for all register users
 */
add_action( 'wp_ajax_mapregisteruser', 'mapregisteruser_callback' );
add_action( 'wp_ajax_nopriv_mapregisteruser', 'mapregisteruser_callback' );

function mapregisteruser_callback(){
	 
	    $dom = new DOMDocument("1.0"); 
		$dom->formatOutput = true;
     
		$node = $dom->createElement("markers");
		$parnode = $dom->appendChild($node);
		
		global $wpdb;
		$table_name = $wpdb->prefix . "usermap";
		$users = $wpdb->prefix . "users";
		$sql = "SELECT ID FROM $users where ID not in (select userid from $table_name)";
		$results = $wpdb->get_results($sql); 
        
		if(!empty($results)){
        foreach ($results as $user) {
			
			$usermetatbl = $wpdb->prefix . "usermeta";
			$userinfo = "SELECT * FROM $usermetatbl where `user_id` = $user->ID";
		    $userinfo = $wpdb->get_results($userinfo); 
            $addr = "0";
            if (isset($userinfo[0]->user_id)) {
                 
				 
                foreach($userinfo as $value)
				{
					if($value->meta_key == 'city')
					{
						$address['city'] = $value->meta_value; 
					}
					else { $address['city'] = ''; }
					
					if($value->meta_key == 'state')
					{
						$address['state'] = $value->meta_value; 
					}
					else { $address['state'] = ''; }
					
					if($value->meta_key == 'country')
					{
						$address['country'] = $value->meta_value; 
					}
					else { $address['country'] = ''; }
					
				}
			}
			
			
				$prepAddr = @implode('+',$address);
                
				$geocode = file_get_contents('http://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false');
                $output = json_decode($geocode);
                if(isset($output->results[0]->formatted_address))
				{
					$country = trim(array_pop(explode(',',$output->results[0]->formatted_address)));
				}
				else
				{
					$country = "";
				}

('test');
                if (isset($output->results[0]->geometry->location->lat)) {
                    $latitude = $output->results[0]->geometry->location->lat;
                    $longitude = $output->results[0]->geometry->location->lng;
                   
					 $isql = "INSERT INTO $table_name (
							`id` ,
							`userid` ,
							`ip` ,
							`country` ,
							`latitude` ,
							`longitude`
							)
							VALUES (
							NULL , '$user->ID', '', '{$address[country]}', '$latitude', '$longitude'
							);
							
							";
					$wpdb->query($isql); 
					
                }
            }
		}
			
		/* for show content */
		
		$usermaptbl = $wpdb->prefix . "usermap";
		$sqls = "SELECT *, count(id)as totaluser FROM $usermaptbl group by latitude,longitude";
		$usermapinfo = $wpdb->get_results($sqls);
		header("Content-type: text/xml");
		foreach ($usermapinfo as $key => $usermap) {
            if (isset($usermap->latitude)) {              
                // ADD TO XML DOCUMENT NODE  
                $node = $dom->createElement("marker");
                $newnode = $parnode->appendChild($node);               
                $newnode->setAttribute("name",'Total User');
                $newnode->setAttribute("address", $usermap->totaluser);
                $newnode->setAttribute("lat", $usermap->latitude);
                $newnode->setAttribute("lng", $usermap->longitude); 
            }
        }
                  
        echo $dom->saveXML();
		die;

	}


/**
 * Map for all online users
 */
add_action( 'wp_ajax_maponlineuser', 'maponlineuser_callback' );
add_action( 'wp_ajax_nopriv_maponlineuser', 'maponlineuser_callback' );

function maponlineuser_callback(){
	 
	    $dom = new DOMDocument("1.0"); 
		$dom->formatOutput = true;
     
		$node = $dom->createElement("markers");
		$parnode = $dom->appendChild($node);
		
		global $wpdb;
		$table_name = $wpdb->prefix . "usermap";
		$useronline = $wpdb->prefix . "useronline";
		$sql = "SELECT user_id FROM $useronline where user_id not in (select userid from $table_name)";
		$results = $wpdb->get_results($sql); 
		
					
		if(!empty($results)){
        foreach ($results as $user) {
			
			$usermetatbl = $wpdb->prefix . "usermeta";
			$userinfo = "SELECT * FROM $usermetatbl where `user_id` = $user->ID";
		    $userinfo = $wpdb->get_results($userinfo); 
            $addr = "0";
            if (isset($userinfo[0]->user_id)) {
                 
				 
                foreach($userinfo as $value)
				{
					if($value->meta_key == 'city')
					{
						$address['city'] = $value->meta_value; 
					}
					if($value->meta_key == 'state')
					{
						$address['state'] = $value->meta_value; 
					}
					if($value->meta_key == 'country')
					{
						$address['country'] = $value->meta_value; 
					}
					
				}
			}
			
			
				$prepAddr = @implode('+',$address);
                
				$geocode = file_get_contents('http://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false');
                $output = json_decode($geocode);
                if(isset($output->results[0]->formatted_address))
				{
					$country = trim(array_pop(explode(',',$output->results[0]->formatted_address)));
				}
				else
				{
					$country = "";
				}

('test');
                if (isset($output->results[0]->geometry->location->lat)) {
                    $latitude = $output->results[0]->geometry->location->lat;
                    $longitude = $output->results[0]->geometry->location->lng;
                   
					 $isql = "INSERT INTO $table_name (
							`id` ,
							`userid` ,
							`ip` ,
							`country` ,
							`latitude` ,
							`longitude`
							)
							VALUES (
							NULL , '$user->ID', '', '{$address[country]}', '$latitude', '$longitude'
							);
							
							";
					$wpdb->query($isql); 
					
                }
            }
		}
		/* for show content */
		
		$usermaptbl = $wpdb->prefix . "usermap";
		$useronlinetbl = $wpdb->prefix . "useronline";
	    $sqls = "SELECT *, count(umap.id)as totaluser FROM $usermaptbl as `umap` inner join $useronlinetbl as `uonline` on umap.userid = uonline.user_id  group by umap.latitude,umap.longitude";
		$usermapinfo = $wpdb->get_results($sqls);
		header("Content-type: text/xml");
		foreach ($usermapinfo as $key => $usermap) {
            if (isset($usermap->latitude)) {              
                // ADD TO XML DOCUMENT NODE  
                $node = $dom->createElement("marker");
                $newnode = $parnode->appendChild($node);               
                $newnode->setAttribute("name",'Total User');
                $newnode->setAttribute("address", $usermap->totaluser);
                $newnode->setAttribute("lat", $usermap->latitude);
                $newnode->setAttribute("lng", $usermap->longitude); 
            }
        }
                  
        echo $dom->saveXML();
		die;

}

/**
 * get user IP
 */
function get_ip() {
		if ( isset( $_SERVER["HTTP_X_FORWARDED_FOR"] ) )
			$ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
		else
			$ip_address = $_SERVER["REMOTE_ADDR"];

		list( $ip_address ) = explode( ',', $ip_address );

		return $ip_address;
}
	
function insertLoggedinUser($user_login, $user){
	
	global $wpdb;

	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ){
			$user_agent = strip_tags( $_SERVER['HTTP_USER_AGENT'] );
	}
		else{
			$user_agent = '';
		}
			
	if ( empty( $page_url ) ){
	    	$page_url = $_SERVER['REQUEST_URI'];
	}
	
	    $user_ip = get_ip();
		
		$current_user = $user;
		
			
			if ( $current_user->ID ) {
				// Check For Member
				$user_id = $current_user->ID;
				$user_name = $current_user->display_name;
				$user_type = 'member';
				$where = $wpdb->prepare( "WHERE user_id = %d", $user_id );
			}

		// Current GMT Timestamp
		$timestamp = current_time( 'mysql' );
		
		/**
		 * check that  login User Id,  is  or not in  wp useronline table
		 */
		$table_useronline = $wpdb->prefix . "useronline";
		$table_users = $wpdb->prefix . "users";
		$users = $wpdb->prefix . "users";
		$sql = "SELECT wpuo.user_id FROM $table_useronline AS wpuo INNER JOIN $table_users AS wpu ON wpu.ID = wpuo.user_id where wpu.ID = $current_user->ID";
		$results = $wpdb->get_results($sql);
		
		$arr = array();
		foreach($results as $key => $val){
			$arr =  (array) $val;
		}
		$results = $arr;
		// end of logic wp useronline table
		
		if(!in_array($current_user->ID,$results))
		{
			$table_name = $wpdb->prefix . "useronline";
			
			// Insert Users
			
			$wpdb->insert( 
							$table_name, 
							array( 
								'id' => NULL, 
								'user_type' => $user_type, 
								'user_id' => $user_id,  
								'user_name' => $user_name 
							), 
							array( 
								'%d', 
								'%s', 
								'%d', 
								'%s' 
							) 
						 );
		}
		
		
}
add_action('wp_login', 'insertLoggedinUser',10, 2);
	
	
function updateUserOnlie() {
        $users =  wp_get_current_user(); 
	    $userId = $users->data->ID;//die;
	global $wpdb;

	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ){
			$user_agent = strip_tags( $_SERVER['HTTP_USER_AGENT'] );
	}
		else{
			$user_agent = '';
		}
			
	if ( empty( $page_url ) ){
	    	$page_url = $_SERVER['REQUEST_URI'];
	}
	
	    $user_ip = get_ip();
	  

		// Current GMT Timestamp
		$timestamp = current_time( 'mysql' );
		$table_name = $wpdb->prefix . "useronline";
		// Purge table
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE user_id = %d", $userId) );

		
}
add_action('wp_logout', 'updateUserOnlie');	
	
	
	
function addScripts(){
	wp_enqueue_script('jquery');
	wp_enqueue_script('googleMapAPI', plugins_url('googleMapAPI.js', __FILE__ ));
	wp_enqueue_script('main', plugins_url('main.js', __FILE__ ));
}
	
function showMap( $atts ) {
	//require_once('googleMapAPI.php');
    require_once('default.php');
}

/* wp_enqueue_scripts hook is used for  enqueuing both scripts and styles on the front end. */
add_action('wp_enqueue_scripts', 'addScripts');
add_shortcode('showmap', 'showMap');

function geoCheckIP($ip)
{

	//check, if the provided ip is valid
	
	if (!filter_var($ip, FILTER_VALIDATE_IP)) {
	
		return 0;
		// throw new InvalidArgumentException("IP is not valid");
	}
	
	//contact ip-server
	
	$response = @file_get_contents('http://www.netip.de/search?query=' . $ip);
	
	if (empty($response)) {
	
		return 0;
		//throw new InvalidArgumentException("Error contacting Geo-IP-Server");
	}
	
	//Array containing all regex-patterns necessary to extract ip-geoinfo from page
	
	$patterns = array();
	
	// $patterns["domain"] = '#Domain: (.*?)&nbsp;#i';
	
	$patterns["country"] = '#Country: (.*?)&nbsp;#i';
	
	$patterns["state"] = '#State/Region: (.*?)<br#i';
	
	$patterns["town"] = '#City: (.*?)<br#i';
	
	//Array where results will be stored
	
	$ipInfo = array();
	
	
	
	//check response from ipserver for above patterns
	$useraddressinfo = "";
	foreach ($patterns as $key => $pattern) {
	
	//store the result in array
	
	$ipInfo[$key] = preg_match($pattern, $response, $value) &&!empty($value[1]) ? $value[1] : '0';
	
	}
	
	$useraddressinfo = implode('+', $ipInfo);
	
	if( $ipInfo['country'] != "0")
	{
		$ipdata = @$useraddressinfo; //["country"];        
		return $ipdata;
	}
	else
	{
		return 0;
	}
}

add_action( 'user_register', 'myplugin_registration_save', 10, 1 );

function myplugin_registration_save( $user_id ) {
	global $wpdb;
	$usermaptbl = $wpdb->prefix . "usermap";
	$args = array();
	$remoteHost = $_SERVER['REMOTE_ADDR'];
	//$remoteHost = '182.71.16.178';
	
	if(isset($remoteHost))
	{
	   
		$addr = geoCheckIP($remoteHost);
		
	
					if($addr != '0')
				{
	
					$prepAddr = str_replace(' ', '+', $addr);
	
					$geocode=file_get_contents('http://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false');
	
					$output= json_decode($geocode);
					if(isset($output->results[0]->formatted_address))
					{
						$country = trim(array_pop(explode(',',$output->results[0]->formatted_address)));
					}
					else
					{
						$country = "";
					}
						if(isset($output->results[0]->geometry->location->lat))
						{
							$latitude = $output->results[0]->geometry->location->lat;
							$longitude = $output->results[0]->geometry->location->lng;
							
								$isql = "INSERT INTO $usermaptbl (
								`id` ,
								`userid` ,
								`ip` ,
								`country` ,
								`latitude` ,
								`longitude`
								)
								VALUES (
								NULL , '$user_id', '', '$country', '$latitude', '$longitude'
								);
								
								";
						$wpdb->query($isql); 
						}
				}
	
				
	
		}

}

register_deactivation_hook( __FILE__, 'prefix_deactivation' );
/**
 * On deactivation, remove all functions from the scheduled action hook.
 */
function prefix_deactivation() {
	wp_clear_scheduled_hook( 'fifteen_min_event');
}

