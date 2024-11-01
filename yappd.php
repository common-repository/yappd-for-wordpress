<?php

/*
Plugin Name: Yappd for Wordpress
Version: 1.0
Plugin URI: http://rick.jinlabs.com/code/yappd
Description: Displays your public yappd messages for all to read. Based on <a href="http://cavemonkey50.com/code/pownce/">Pownce for Wordpress</a> by <a href="http://cavemonkey50.com/">Cavemonkey50</a>.
Author: Ricardo Gonz&aacute;lez
Author URI: http://rick.jinlabs.com/
*/

/*  Copyright 2007  Ricardo González Castro (rick[in]jinlabs.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('MAGPIE_CACHE_AGE', 120);

// Display yappd messages
function yappd_messages($username = '', $num = 5, $list = true, $update = true, $linked  = false) {
	include_once(ABSPATH . WPINC . '/rss.php');
	
	$messages = fetch_rss('http://www.yappd.com/rss/'.$username);

	if ($list) echo '<ul class="yappd">';
	
	if ($username == '') {
		if ($list) echo '<li>';
		echo 'RSS not configured';
		if ($list) echo '</li>';
	} else {
			if ( empty($messages->items) ) {
				if ($list) echo '<li>';
				echo 'No public yappd messages.';
				if ($list) echo '</li>';
			} else {
				foreach ( $messages->items as $message ) {
					$msg = $message['description'];
					$updated = yappd_relative($message['pubdate']);
					$link = $message['link'];
				
					if ($list) echo '<li class="yappd-item">'; elseif ($num != 1) echo '<p class="yappd-message">';
					if ($linked) { 
            echo '<a href="'.$link.'" class="yappd-link">'.$msg.'</a>'; // Puts a link to the status of each tweet
          } else {
            echo $msg; // Only the message, no link.
          }
					if ($update) echo ' <span class="yappd-timestamp">' . $updated . '</span>';
					if ($list) echo '</li>'; elseif ($num != 1) echo '</p>';
				
					$i++;
					if ( $i >= $num ) break;
				}
			}
			
			if ($list) echo '</ul>';
		}
	}
// Present the date nicer

function yappd_relative($time) {
	$time_orig = strtotime($time);

	$diff = $just = time()-$time_orig;
    $months = floor($diff/2592000);
    $diff -= $months*2419200;
    $weeks = floor($diff/604800);
    $diff -= $weeks*604800;
    $days = floor($diff/86400);
    $diff -= $days*86400;
    $hours = floor($diff/3600);
    $diff -= $hours*3600;
    $minutes = floor($diff/60);
    $diff -= $minutes*60;
    $seconds = $diff;
    
	if ($just<=0) {
		return 'Just Now!';	
	} else {
	    if ($months>0) {
	        // over a month old, just show date (yyyy/mm/dd format)
	        return 'on '.date('Y/m/d', $time_orig);
	    } else {
	        if ($weeks>0) {
	            // weeks and days
	            $relative_date .= ($relative_date?', ':'').$weeks.' '.__('week').($weeks>1?'s':'');
	            $relative_date .= $days>0?($relative_date?', ':'').$days.' '.__('day').($days>1?'s':''):'';
	        } elseif ($days>0) {
	            // days and hours
	            $relative_date .= ($relative_date?', ':'').$days.' '._('day').($days>1?'s':'');
	            $relative_date .= $hours>0?($relative_date?', ':'').$hours.' '.__('hour').($hours>1?'s':''):'';
	        } elseif ($hours>0) {
	            // hours and minutes
	            $relative_date .= ($relative_date?', ':'').$hours.' '.__('hour').($hours>1?'s':'');
	            $relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' '.__('minute').($minutes>1?'s':''):'';
	        } elseif ($minutes>0) {
	            // minutes only
	            $relative_date .= ($relative_date?', ':'').$minutes.' '.__('minute').($minutes>1?'s':'');
	        } else {
	            // seconds only
	            $relative_date .= ($relative_date?', ':'').$seconds.' '.__('second').($seconds>1?'s':'');
	        }
	    }
	}
    // show relative date and add proper verbiage
    return $relative_date.' ago';
}

// yappd widget stuff
function widget_yappd_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;
	
	$check_options = get_option('widget_yappd');
  if ($check_options['number']=='') {
    $check_options['number'] = 1;
    update_option('widget_yappd', $check_options);
  }
  
	function widget_yappd($args) {
		
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		include_once(ABSPATH . WPINC . '/rss.php');
		$options = get_option('widget_yappd');
		$title = $options['title'];
		$username = $options['username'];
		$num = $options['num'];
		$update = ($options['update']) ? true : false;
		$linked = ($options['linked']) ? true : false;
		$messages = fetch_rss("$username/");

		// These lines generate our output. Widgets can be very complex
		// but as you can see here, they can also be very, very simple.
		echo $before_widget . $before_title . $title . $after_title;
		yappd_messages($username, $num, true, $update, $linked);
		echo $after_widget;
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function widget_yappd_control() {

		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_yappd');
		if ( !is_array($options) )
			$options = array('title'=>'', 'username'=>'', 'num'=>'1', 'update'=>true, 'linked'=>true);
		if ( $_POST['yappd-submit'] ) {

			// Remember to sanitize and format use input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST['yappd_title']));
			$options['username'] = strip_tags(stripslashes($_POST['yappd_username']));
			$options['num'] = strip_tags(stripslashes($_POST['yappd_num']));
			$options['update'] = isset($_POST['yappd_update']);
			$options['linked'] = isset($_POST['yappd_linked']);		
			update_option('widget_yappd', $options);
		}

		// Be sure you format your options to be valid HTML attributes.
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$username = htmlspecialchars($options['username'], ENT_QUOTES);
		$num = htmlspecialchars($options['num'], ENT_QUOTES);
		$update_checked = ($options['update']) ? 'checked="checked"' : '';
		$linked_checked = ($options['linked']) ? 'checked="checked"' : '';

		
		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
		echo '<p style="text-align:right;"><label for="yappd_title">' . __('Title:') . ' <input style="width: 200px;" id="yappd_title" name="yappd_title" type="text" value="'.$title.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="yappd_username">' . __('username:') . ' <input style="width: 200px;" id="yappd_username" name="yappd_username" type="text" value="'.$username.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="yappd_num">' . __('Number of Messages:') . ' <input style="width: 25px;" id="yappd_num" name="yappd_num" type="text" value="'.$num.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="yappd_update">' . __('Show timestamps:') . ' <input id="yappd_update" name="yappd_update" type="checkbox"'.$update_checked.' /></label></p>';
		echo '<p style="text-align:right;"><label for="yappd_linked">' . __('Linked yapps:') . ' <input id="yappd_linked" name="yappd_linked" type="checkbox"'.$linked_checked.' /></label></p>';
		echo '<input type="hidden" id="yappd-submit" name="yappd-submit" value="1" />';
	}
	
	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	register_sidebar_widget(array('yappd', 'widgets'), 'widget_yappd');

	// This registers our optional widget control form. Because of this
	// our widget will have a button that reveals a 300x100 pixel form.
	register_widget_control(array('yappd', 'widgets'), 'widget_yappd_control', 300, 180);
}

// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'widget_yappd_init');

?>