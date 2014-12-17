<?php
    /*
    Plugin Name: RSS Feed System
    Plugin URI: http://www.taureanwooley.com
    Description: Plugin that allows for uploading rss-feeds into wordpress along with uploading full content from certain rss feeds when available (still in development)
    Author: Taurean Wooley
    Version: 1.0.1
    Author URI: http://www.taureanwooley.com
    */
    include_once('tw_tp-admin.php');
    if (!function_exists('tw_rss_admin_actions')) {
        function tw_rss_admin_actions() {
			add_menu_page(
				'TW RSS Feed', 
				'TW RSS Feed', 
				'manage_options', 
				'twp_admin', 
				'twp_admin',
				plugins_url('Feed_25x25.png', __FILE__)
			);
			add_submenu_page(
				'twp_admin',
				'Settings',
				'Settings',
				'manage_options',
				'tw_theme_options',
				'tw_theme_options'
			);
			add_submenu_page(
				'twp_admin',
				'View Feed Content',
				'View Feed Content',
				'manage_options',
				'tw_view_content',
				'tw_view_content'
			);
			add_submenu_page(
				'twp_admin',
				'Manual Update',
				'Manual Update',
				'manage_options',
				'tw_manual_update',
				'tw_manual_update'
			);
		}
        add_action('admin_menu', 'tw_rss_admin_actions');
        
        add_filter( 'widget_text', 'do_shortcode');
        
		function tw_custom_meta_boxes() {
			add_meta_box('text_info',__( 'Feed Images', 'myplugin_textdomain' ),'myplugin_meta_box_callback','feeds');
		}
		add_action('admin_init','tw_custom_meta_boxes');
		
		function myplugin_meta_box_callback($args){
			global $post;
			// Add an nonce field so we can check for it later.
			wp_nonce_field( 'myplugin_inner_custom_box', 'myplugin_inner_custom_box_nonce' );
			
			// Use get_post_meta to retrieve an existing value from the database.
			$value = get_post_meta( $post->ID, '_my_meta_value_key', true );
			include_once('feed_meta_box.php');
		}
        
		function tw_theme_options() {
		    include_once('ttp-import-settings.php');
		}
		
		function get_post_image($id,$description){
		    preg_match_all("/img(.*?)src=['\"](.*?)['\"](.*?)\/\>/i", $description, $img);
			if(is_array($img)){
				//print '<pre>'.print_r($img,true).'</pre>';
				$count = 0;
				foreach($img[2] as $k=>$i){
					$img_id = upload_files($i);
					if($k == 0){
						set_post_thumbnail($id,$img_id);
					} else {
						if(is_numeric($img_id)){
							add_post_meta($id,'rss-feed-image-'.$k,$img_id);
						}
					}
					if($count > 4){
					    return true;
					}
					++$count;
				}
			}
		}
		
		function upload_files($url,$alt="TW RSS Feed Importer"){
			$tmp = download_url( $url );
		    $desc = $alt;
		    $file_array = array();
		
		    // Set variables for storage
		    // fix file filename for query strings
		    preg_match("/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i", $url, $matches);
		    $file_array['name'] = basename($matches[0]);
		    $file_array['tmp_name'] = $tmp;
		
		    // If error storing temporarily, unlink
		    if ( is_wp_error( $tmp ) ) {
		        @unlink($file_array['tmp_name']);
		        $file_array['tmp_name'] = '';
		    }
		
		    // do the validation and storage stuff
		    $id = media_handle_sideload( $file_array, $postID, $desc);
		
		    // If error storing permanently, unlink
		    if ( is_wp_error($id) ) {
		        @unlink($file_array['tmp_name']);
		        return $id;
		    }
		    return $id;
		}
		
		function query_feed_group($feed){
		    $args = array(
				'post_type'		=>	'feeds',
				'meta_query'	=>	array(
						array(
								'meta_key'  => 'tw_rss_feed_options',
								'value'     => $feed,
								'compare'   => 'LIKE'
							)
					),
				'posts_per_page'    => -1,
			);
			
			$my_query = new WP_Query( $args );
			return $my_query;
		}
		
		function tw_view_content(){
		    include_once('tw_view_feed.php');
		}
		
		function tw_manual_update(){
		    tw_create_rss_feed();
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
		    global $wpdb;
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
			
			if(strlen($feed_category) < 1){
				$feed_category = 'uncategorized';
			}
			
			$count = 0;
			
			$dom = new DOMDocument;
			@$dom->loadXML($output);
			$ypath = new DOMXPath($dom);
			
			$selectOption = array('*/content','*/item');
			$option = '';
			foreach($selectOption as $f){
				if($ypath->query($f)->length > 0){
				    $option = $f;
				}
			}
			
			if($option != ''){
    			foreach($ypath->query($option) as $a){
    				$info = array();
    				$count = 0;
    				
    				$description = array();
    				$j = $ypath->query('//description', $a);
    				foreach($j as $k=>$i){
    					$description[] = $i->nodeValue;
    				}
    				
    				$title = array();
    				$j = $ypath->query('//title', $a);
    				foreach($j as $i){
    					$title[] = $i->nodeValue;
    				}
    				
    				$link = array();
    				$j = $ypath->query('//link', $a);
    				foreach($j as $i){
    					$link[] = $i->nodeValue;
    				}
    				
    				foreach($link as $k=>$l){
    					if(isset($full_content)){
    				        $content = file_get_contents($link[$k]);
    				        $dom = new DOMDocument;
    				        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is','',$content);
    				        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is','',$content);
    	                    @$dom->loadHTML($content);
    	                    $xpath = new DOMXPath($dom);
    	                    
    	                    $x = explode('`',$feed_content);
    	                    $l = explode('|' ,$x[1]);
    	                    if(isset($l[1])){
    	                        foreach($xpath->query('//div[@'.$l[0].'="'.$l[1].'"]') as $d){
    	                            $content = $d->nodeValue;
    	                        }
    	                    }
    	                    $description[$k] = $content;
    				    }
    				    
    				    $query = 'SELECT * FROM '.$wpdb->posts.' WHERE '.$wpdb->posts.'.post_title = "'.$title[$k].'" AND '.$wpdb->posts.'.post_type="feeds" AND '.$wpdb->posts.'.post_status!="trash"';
    				    $p = $wpdb->get_results($query);
    				    if(sizeof($p) < 1){
    	    				$post = array(
    	    						'post_title'=>$title[$k],
    	    						'post_content'=>str_replace('"', "'", $description[$k].'<br/><br/>Content From: <a href="'.$link[$k].'">'.$ypath->query('//channel/title')->item(0)->nodeValue.'</a><br/>'),
    	    						'post_category'=>array($feed_category),
    	    						'post_author'=>1,
    	    						'post_status'=>'publish',
    	    						'post_type'=>'feeds'
    	    					);
    	    				$id = wp_insert_post($post);
    	    				if(!isset($full_content)){
    	    			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category);
    	    				} else {
    	    			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category.'|full-content|'.$feed_content);
    	    				}
    	    				++$count;
    				    }
    				}
    			}
    			echo 'Feeds Added: '.$count.'<br/>';
			} else {
			    $xml = simplexml_load_string($output);
			    
			    if(isset($xml->channel)){
			        $count = 0;
			        foreach($xml->channel->item as $x){
        			    $info = array();
        				$count = 0;
        				
        				$description  = $x->content;
        				$title = $x->title;
        				$title = str_replace("//<![CDATA[","",$title);
                        $title = str_replace("//]]>","",$title);
        				$link  = $x->id[0];
        				
    					if(isset($full_content)){
    				        $content = file_get_contents($link);
    				        $dom = new DOMDocument;
    				        $content = preg_replace("/<script\b[^>]*>(.*?)<\/script>/is",'',$content);
    				        $content = preg_replace("/<style\b[^>]*>(.*?)<\/style>/is",'',$content);
    	                    @$dom->loadHTML($content);
    	                    $xpath = new DOMXPath($dom);
    	                    
    	                    $xi = explode('`',$feed_content);
    	                    $l = explode('|' ,$xi[1]);
    	                    if(isset($l[1])){
    	                        foreach($xpath->query('//div[@'.$l[0].'="'.$l[1].'"]') as $d){
    	                            $content = $d->nodeValue;
    	                        }
    	                    }
    	                    $description[$k] = $content;
    				    }
    				    
    				    $query = 'SELECT * FROM '.$wpdb->posts.' WHERE '.$wpdb->posts.'.post_title = "'.$title.'" AND '.$wpdb->posts.'.post_type="feeds" AND '.$wpdb->posts.'.post_status!="trash"';
    				    $p = $wpdb->get_results($query);
    				    if(sizeof($p) < 1){
    	    				$post = array(
    	    						'post_title'=>$title,
    	    						'post_content'=>str_replace('"', "'", $description.'<br/><br/>Content From: <a href="'.$link.'">'.$x.'</a><br/>'),
    	    						'post_category'=>array($feed_category),
    	    						'post_author'=>1,
    	    						'post_status'=>'publish',
    	    						'post_type'=>'feeds'
    	    					);
    	    				$id = wp_insert_post($post);
    	    				++$count;
    	    				if(!isset($full_content)){
    	    			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category);
    	    				} else {
    	    			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category.'|full-content|'.$feed_content);
    	    				}
    				    }
        			}
        			echo 'Feeds Added: '.$count.'<br/>';
			    } else {
			        $count = 0;
        			foreach($xml->entry as $x){
        			    $info = array();
        				$count = 0;
        				
        				$description  = $x->content;
        				$title = $x->title;
        				$title = str_replace("//<![CDATA[","",$title);
                        $title = str_replace("//]]>","",$title);
        				$link  = $x->id[0];
        				
    					if(isset($full_content)){
    				        $content = file_get_contents($link);
    				        $dom = new DOMDocument;
    				        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is','',$content);
    				        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is','',$content);
    	                    @$dom->loadHTML($content);
    	                    $xpath = new DOMXPath($dom);
    	                    
    	                    $xi = explode('`',$feed_content);
    	                    $l = explode('|' ,$xi[1]);
    	                    if(isset($l[1])){
    	                        foreach($xpath->query('//div[@'.$l[0].'="'.$l[1].'"]') as $d){
    	                            $content = $d->nodeValue;
    	                        }
    	                    }
    	                    $description[$k] = $content;
    				    }
    				    
    				    $query = 'SELECT * FROM '.$wpdb->posts.' WHERE '.$wpdb->posts.'.post_title = "'.$title.'" AND '.$wpdb->posts.'.post_type="feeds" AND '.$wpdb->posts.'.post_status!="trash"';
    				    $p = $wpdb->get_results($query);
    				    if(sizeof($p) < 1){
    	    				$post = array(
    	    						'post_title'=>$title,
    	    						'post_content'=>str_replace('"', "'", $description.'<br/><br/>Content From: <a href="'.$link.'">'.$x.'</a><br/>'),
    	    						'post_category'=>array($feed_category),
    	    						'post_author'=>1,
    	    						'post_status'=>'publish',
    	    						'post_type'=>'feeds'
    	    					);
    	    				$id = wp_insert_post($post);
    	    				++$count;
    	    				if(!isset($full_content)){
    	    			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category);
    	    				} else {
    	    			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category.'|full-content|'.$feed_content);
    	    				}
    				    }
        			}
        			echo 'Feeds Added: '.$count.'<br/>';
			    }
			}
		}
		}

       	
       	function save_feed_meta( $post_id ) { 
       		global $post; 
       		/*
            * In production code, $slug should be set only once in the plugin,
            * preferably as a class property, rather than in each function that needs it.
            */
            $slug = 'feeds';
            
            if ( $slug != $post->post_type ) {
                return;
            }
            
            foreach($_POST as $k=>$p){
            	delete_post_meta($post_id,$k);
            }
        }
        add_action( 'save_post', 'save_feed_meta');
        
        function feed_searches($args = array()){
            $query = 'post_type=feeds';
            $options = array();
            if(is_array($args)){
                extract($args);
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
        
        function twp_admin() {
            include_once('ttp-import-admin.php');
        }
    }
?>