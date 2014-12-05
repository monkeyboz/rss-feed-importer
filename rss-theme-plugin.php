<?php
    /*
    Plugin Name: RSS Feed System
    Plugin URI: http://www.taureanwooley.com
    Description: Plugin that allows for uploading rss-feeds into wordpress along with uploading full content from certain rss feeds when available (still in development)
    Author: Taurean Wooley
    Version: 1.0.1
    Author URI: http://www.taureanwooley.com
    */
    include_once('ttp-admin.php');
    if (!function_exists('ttp_admin_actions')) {
        function ttp_admin_actions() {
			add_menu_page(
				'TW RSS Feed', 
				'TW RSS Feed', 
				'manage_options', 
				'ttp_admin', 
				'ttp_admin',
				plugins_url('Feed_25x25.png', __FILE__)
			);
			add_submenu_page(
				'ttp_admin',
				'Settings',
				'Settings',
				'manage_options',
				'theme_options',
				'theme_options'
			);
		}
        add_action('admin_menu', 'ttp_admin_actions');
        
		function theme_options() {
		    include_once('ttp-import-settings.php');
		}
        
        function register_new_post_type(){
        	$array = array(
        			"label"	=>	"Feeds",
        			"public"	=>	true,
			        'show_ui' => true,
			        '_builtin' => false,
			        '_edit_link' => 'post.php?post=%d',
			        'capability_type' => 'event',
			        'hierarchical' => false,
			        'rewrite' => array("slug" => "event"),
			        'query_var' => "event",
			        'taxonomies' => array('category'),
        			"supports"	=>	array("title","editor","thumbnail","excerpt"),
        		);
        	register_post_type('feeds',$array);
        }
       	add_action('init','register_new_post_type');
       	
       	function adminstrator_settings(){
       		echo 'testing';
       	}
       	
       	//On plugin activation schedule our daily database backup 
		register_activation_hook( __FILE__, 'tw_rss_feed_schedule' );
		function wi_create_daily_backup_schedule(){
		  //Use wp_next_scheduled to check if the event is already scheduled
		  $timestamp = wp_next_scheduled( 'tw_create_daily_rss_feed' );
		
		  //If $timestamp == false schedule daily backups since it hasn't been done previously
		  if( $timestamp == false ){
		    //Schedule the event for right now, then to repeat daily using the hook 'wi_create_daily_backup'
		    wp_schedule_event( time(), 'daily', 'tw_create_daily_rss_feed' );
		  }
		}
		
		//Hook our function , wi_create_backup(), into the action wi_create_daily_backup
		add_action( 'tw_create_daily_rss_feed', 'tw_create_rss_feed' );
		
		function tw_create_rss_feed(){
			$feeds = get_option('rss_feeds');
			
			$feeds = explode('&',$feeds);
			foreach($feeds as $p){
				$a = explode('|', $p);
				if(sizeof($p) > 3){
					extract_feed(array('feed_name'=>$a[0],'feed_url'=>$a[1],'feed_category'=>$a[2],'full_content'=>$a[3],'feed_content'=>$a[4]));
				} else {
					extract_feed(array('feed_name'=>$a[0],'feed_url'=>$a[1],'feed_category'=>$a[2]));
				}
			}
		}
		
		function extract_feed($arg){
			extract($arg);
			
			$feeds = get_option('rss_feeds');
			
			if(strlen($feed_delete) > 0){
				$p = explode('&', $feed_delete);
				foreach($p as $q){
					$args = array(
						'post_type'		=>	'feeds',
						'meta_query'	=>	array(
							array(
								'value'	=>	$q,
							)
						)
					);
					$my_query = new WP_Query( $args );
					foreach($my_query->posts as $p){
						wp_delete_post($p->ID);
					}
				}
			}
			
			if(strlen($feed_name) > 0){
				$ch = curl_init();
				
				curl_setopt($ch, CURLOPT_URL, $feed_url);
				curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				$output = curl_exec($ch);
				curl_close($ch);
				
				$output = str_replace('"',"'", $output);
				
				$xml = simplexml_load_string($output);
				//print_r($xml);
				$xml = json_encode($xml);
				$xml = json_decode($xml,true);
				
				if(strlen($feed_category) < 1){
					$feed_category = 'uncategorized';
				}
				
				foreach($xml['channel']['item'] as $a){
				    if(isset($full_content)){
				        $content = file_get_contents($a['link']);
				        $dom = new DOMDocument;
				        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is','',$content);
				        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is','',$content);
	                    @$dom->loadHTML($content);
	                    $xpath = new DOMXPath($dom);
	                    
	                    $x = explode('`',$feed_content);
	                    $l = explode('|' ,$x[1]);
	                    if(isset($l[1])){
	                        foreach($xpath->query('//div[contains(@'.$l[0].', "'.$l[1].'")]') as $d){
	                            $content = $d->nodeValue;
	                        }
	                    }
	                    $a['description'] = $content;
				    }
					$query = 'SELECT * FROM '.$wpdb->posts.' WHERE '.$wpdb->posts.'.post_title = "'.$a['title'].'" AND '.$wpdb->posts.'.post_type="feeds"';
    			    $p = $wpdb->get_results($query);
    			    if(sizeof($p) < 1){
        				$post = array(
        						'post_title'=>$a['title'],
        						'post_content'=>str_replace('"', "'", $a['description'].'<br/><br/>Content From: <a href="'.$a['link'].'">'.$xml['channel']['title'].'</a><br/>'),
        						'post_category'=>array($feed_category),
        						'post_author'=>1,
        						'post_status'=>'publish',
        						'post_type'=>'feeds'
        					);
        				$id = wp_insert_post($post);
        				if(isset($full_content)){
        			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category);
        				} else {
        			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category.'|full-content|'.$feed_content);
        				}
    			    }
				}
			}
		}

       	
       	function save_feed_meta( $post_id, $post, $update ) { /*
            * In production code, $slug should be set only once in the plugin,
            * preferably as a class property, rather than in each function that needs it.
            */
            $slug = 'feed';
            
            // If this isn't a 'book' post, don't update it.
            if ( $slug != $post->post_type ) {
                return;
            }
            
            // - Update the post's metadata.
            
            if ( isset( $_REQUEST['book_author'] ) ) {
                update_post_meta( $post_id, 'book_author', sanitize_text_field( $_REQUEST['book_author'] ) );
            }
            
            if ( isset( $_REQUEST['publisher'] ) ) {
                update_post_meta( $post_id, 'publisher', sanitize_text_field( $_REQUEST['publisher'] ) );
            }
            
            // Checkboxes are present if checked, absent if not.
            if ( isset( $_REQUEST['inprint'] ) ) {
                update_post_meta( $post_id, 'inprint', TRUE );
            } else {
                update_post_meta( $post_id, 'inprint', FALSE );
            }
        }
        add_action( 'save_post', 'save_feed_meta', 10, 3 );
        
        function feed_searches($args = array()){
            extract($args);
            $query = 'post_type=feeds';
            $options = array();
            if(is_array($args)){
            	foreach($args as $k=>$a){
            	    if($k != 'title_only'){
            	        $query .= '&'.$k.'='.$a;
            	    } else {
            	        $options = array('title_only'=>true);
            	    }
            	}
            } else {
                $query = 'post_type=feeds&posts_per_page=30';
            }
        	$layout = new Layout(array('post'=>'feeds'));
            $query = new WP_Query($query);
            $layout->get_layout(plugin_dir_path( __FILE__  ).'/layout/layout.php');
           	
           	$string = $layout->get_css();
           	$string .= $layout->get_js();
        	$string .= $layout->populate_layout($query->posts,$options);
        	$string = '<div id="tw-container">'.$string.'</div>';
            $string .= '<script>var container = document.getElementById("tw-container"); var iso = new Isotope( container, { itemSelector: ".content-holder", layoutMode: "masonry" });</script>';

            return $string;
        }
        add_shortcode('feed_searches','feed_searches');
        
        function feed_group($id){
            global $post;
            $layout = new Layout(array('post'=>'feeds'));
            $query = 'post_type=feeds&post_per_page=5';
            $layout->get_layout(plugin_dir_path( __FILE__  ).'/layout/layout.php');
            
            $posts = new WP_Query($query);
            
            $string = '<div class="tw-feed-content">';
            $string .= $layout->get_css();
            $string .= $layout->populate_layout($posts->posts,array('title_only'=>true));
            $string .= '</div>';
            return $string;
        }
        add_shortcode('feed_group','feed_group');
        
        function feed_info($id){
            $feed = new WP_Query('post_type=feeds&ID='.$id);
            //include_once('ttp-import-admin.php'); 
        }
        add_shortcode('feed_info','feed_info');
        
        function ttp_admin() {
            include_once('ttp-import-admin.php');
        }
    }
?>