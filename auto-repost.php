<?php
/*
Plugin Name:Auto-Repost
Plugin URI: 
Description:Automatically repost old articles.
Version: 1.2
Author: yukimaru222
Author URI: http://tool.potalstyle.net/
License: GPL2
*/

/*
Copyright 2017 yukimaru (email:tool@potalstyle.net)
 
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action( 'plugins_loaded', 'rpym_load_textdomain' );
function rpym_load_textdomain() {
    load_plugin_textdomain( 'auto-repost', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
}

//menu add
function rpym_menu() {
	add_options_page('Auto-repost', 'Auto-repost', 'level_8', 'repost_menu', 'rpym_postpage');
}

//menu hook
add_action( 'admin_menu', 'rpym_menu' );

//option page
function rpym_postpage(){
 
    if (isset($_POST["sendpost"])) {
        
		check_admin_referer('formnonce');
		
		$p_start = htmlspecialchars($_POST["start"],ENT_QUOTES);
		$p_retime_h = htmlspecialchars($_POST["retime_h"],ENT_QUOTES);
		$p_retime_m = htmlspecialchars($_POST["retime_m"],ENT_QUOTES);
		
		update_option('rpym_start', $p_start);
        update_option('rpym_retime_h', $p_retime_h);
        update_option('rpym_retime_m', $p_retime_m);
        
        $start = get_option( 'rpym_start' );
     }
    ?>
    
    <?php echo _e('<h1>Auto-repost Plugin</h1>' , 'auto-repost');  ?>
    
    <form method="post" action="" >
    
    <input type="hidden" name="start" value="0">

    <label>
    <input type="checkbox" name="start" value="1" <?php if(get_option( 'rpym_start' ) == 1 ){echo 'checked';} ?> />
    <?php echo _e('activation' , 'auto-repost');  ?>
    </label>
    
    
    <?php echo _e('<h2>Repost time</h2>' , 'auto-repost');  ?>
    Next Post
    <select name="retime_h">
        <?php for ($h = 0; $h <= 23; $h++) : ?>
            <option value="<?php echo $h; ?>" <?php if( get_option( 'rpym_retime_h' ) == $h ){echo 'selected';} ?> >
            <?php echo $h; ?>
            </option>
        <?php endfor; ?>
    </select><?php echo _e('hour' , 'auto-repost'); ?>
    
    <select name="retime_m">
        <?php for ($m = 0; $m <= 59; $m++) : ?>
            <option value="<?php echo $m; ?>" <?php if( get_option( 'rpym_retime_m' ) == $m ){echo 'selected';} ?>  >
            <?php echo $m; ?>
            </option>
        <?php endfor; ?>
    </select><?php echo _e('Minute' , 'auto-repost'); ?>&nbsp;

	<?php wp_nonce_field('formnonce');?>    
    <p><input type="submit" name="sendpost" value="<?php _e('Send', 'auto-repost'); ?>" class="formBtn" ></p>

    </form>
<?php
    if (isset($_POST["sendpost"])) {

	check_admin_referer('formnonce');
        echo _e('Setting completion' , 'auto-repost');

     }
?>

<p style="padding-top:50px;"><?php echo _e('SITE' , 'auto-repost'); ?>:
<a href="http://tool.potalstyle.net/" target="_blank" rel="nofollow" >http://tool.potalstyle.net/</a>
</p>

    <?php


    if  (get_option( 'rpym_start' ) == 1) {

        $now = current_time('Y-m-d H:i:s');

        $th = get_option( 'rpym_retime_h' );
        $tm = get_option( 'rpym_retime_m' );
		
        //diffrent time
        $p = ( current_time( 'timestamp' ) - time( ) ) / 3600;
        
        //timezoon
        $my_time = date( "Y-m-d $th:$tm:00", current_time( 'timestamp' ) );

        if (strtotime($my_time) < strtotime($now )) {
			$my_time = date( "Y-m-d $th:$tm:00" ,strtotime("+1 day") );
        }

        //UNIX
        $task_time = strtotime( -1 * $p."hour", strtotime( $my_time ));

        //wp_cron if
        if(!wp_next_scheduled('artym_cron_hook')){
            //wp_cron add
            wp_schedule_event($task_time, 'daily', 'artym_cron_hook');
        
        }else{
            //wp_cron delete
            wp_clear_scheduled_hook('artym_cron_hook');
            wp_schedule_event($task_time, 'daily', 'artym_cron_hook');
        }

    }else{
    
        //OFF wp_cron Schedule delete
        wp_clear_scheduled_hook('artym_cron_hook');
    }

 
} //function rpym_postpage END


//artym_cron_hook
add_action('artym_cron_hook', 'artym_cron_action');


//artym_cron_hook
function artym_cron_action() {
        
        $now = current_time('Y-m-d H:i:s');
		$nowutc = gmdate( 'Y-m-d H:i:s' );	

        $th = get_option( 'rpym_retime_h' );
        $tm = get_option( 'rpym_retime_m' );

        $uptime = date( "Y-m-d $th:$tm:00");

        $diff_hour = (strtotime($nowutc) - strtotime($now)) / 3600;

		$thgmt = $th + $diff_hour;
        $uptimegmt = date( "Y-m-d $thgmt:$tm:00");


        global $wpdb;


		$result = $wpdb->prepare( "SELECT * FROM wp_posts WHERE post_status = 'publish' ORDER BY post_date ASC LIMIT 1",null);


		$result = $wpdb->get_results($result);

		foreach ($result as $value){
			$before = substr($value->post_date, 0, 10);
		}
			
		$result = $wpdb->prepare( "SELECT * FROM wp_posts WHERE post_status = 'publish'",null );
		$result = $wpdb->get_results($result);
	
	
		foreach ($result as $value){
			$now = substr($value->post_date, 0, 10);
		
			if($now == $before){
				$post_id = $value->ID;
	
				$data = array(
							'post_date' => $uptime,
							'post_date_gmt' => $uptimegmt,
							'post_modified' => $now,
							'post_modified_gmt' => $nowutc
				);
	
	
				$condition = array(
					'ID' => $post_id
				);
	
				$dataFormat = array('%s');
				$conditionsFormat = array('%d');
				$wpdb->update('wp_posts', $data, $condition,$dataFormat,$conditionsFormat);
			}//endif
		}

}//artym_cron_action END

//OFF DELETE
function artym_deact() {
    wp_clear_scheduled_hook('artym_cron_hook');
}
register_deactivation_hook(__FILE__, 'artym_deact');
?>