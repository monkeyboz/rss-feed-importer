<?php
    /*
    Plugin Name: RSS Feed System
    Plugin URI: http://www.taureanwooley.com
    Description: Plugin that allows for uploading rss-feeds into wordpress along with uploading full content from certain rss feeds when available (still in development)
    Author: Taurean Wooley
    Version: 2.0.6
    Author URI: http://www.taureanwooley.com
    */
    include_once('tw_tp-admin.php');
    include_once('tw_widget.php');
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
				'Impressions',
				'Impressions',
				'manage_options',
				'tw_impressions',
				'tw_impressions'
			);
			add_submenu_page(
				'twp_admin',
				'Monitizing and Sharing',
				'Monitizing and Sharing',
				'manage_options',
				'tw_monitization',
				'tw_monitization'
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
        
        // Register and load the widget
        function tw_load_widget() {
        	register_widget( 'tw_widget' );
        }
        add_action( 'widgets_init', 'tw_load_widget' );
        
        function tw_impressions(){
            include_once(plugin_dir_path( __FILE__ ).'tw_impressions.php');
        }
        
        function tw_monitization(){
            include_once(plugin_dir_path( __FILE__ ).'tw_blogging_options.php');
        }
        
        function tw_feed_update(){
            update_option('tw_rss_feed_update_'.date('Y-m-d_h:i:s'),$data);
            $data = json_decode($data,true);
            include_once(plugin_dir_path( __FILE__  ).'cron/blog_updated.php');
        }
        
        function tw_rss_feed_impression() {
            if(!is_admin()){
                include_once(plugin_dir_path( __FILE__  ).'cron/remember_impression.php');
            }
        }
        add_action('wp_head', 'tw_rss_feed_impression');
        
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
		    if(isset($_POST) && sizeof($_POST) > 0){
	            wp_clear_scheduled_hook( 'tw_hourly_event' );
	            update_option('tw_schedule_event', $_POST['schedule']);
	            tw_setup_schedule();
		    }
		    
		    $ch = curl_init();
	        $server = '{"server":"'.site_url().'"}';
	        curl_setopt($ch, CURLOPT_URL, "http://quanticpost.com/info_pull/blog_update/?info=".$server);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	        $data = curl_exec($ch);
		    
		    include_once('ttp-import-settings.php');
		}
		
		function get_post_image($id,$description,$domain){
			$domain = parse_url($domain);
			set_time_limit(100);
		    preg_match_all("/img(.*?)src=['\"](.*?)['\"](.*?)\/\>/i", $description, $img);
		    $count = 0;
		    
			if(is_array($img)){
				foreach($img[2] as $k=>$i){
					$domain_check = parse_url($i);
					if(!isset($domain_check['host'])){
						$i = @(strpos($i)!=0)?'/'.$i:$i;
						$i = $domain['scheme'].'://'.$domain['host'].$i;
					}
					$img_id = upload_files($i,$id);
					
					if(isset($img_id)){
						if($k == 0){
							set_post_thumbnail($id,$img_id);
						} else {
							if(is_numeric($img_id)){
								add_post_meta($id,'rss-feed-image-'.$k,$img_id);
							}
						}
						if($count > 2){
						    return true;
						}
					}
					++$count;
				}
			}
		}
		
		function api_call($url,$post_fields = array()){
			$ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		    curl_setopt($ch, CURLOPT_HEADER, false); 
		    if(sizeof($post_fields) > 0){
		    	curl_setopt($ch, CURLOPT_POST, 1);
		    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		    }
		    $data = curl_exec($ch);
		    
		    return $data;
		}
		
		function hints(){
			return api_call('http://quanticpost.com/info_pull/hints');
		}
		
		function get_feed_hints(){
			return api_call('http://quanticpost.com/info_pull/feed_hints');
		}
		
		function get_category_hints(){
		    return api_call('http://quanticpost.com/info_pull/category_hints');
		}
		
		function upload_files($url,$id,$alt="TW RSS Feed Importer"){
			$tmp = download_url( $url );
			$desc = $alt;
			$file_array = array();
			
			if(is_array($tmp) || @filesize($tmp) < 100){
				return false;
			}
			
			// Set variables for storage
			// fix file filename for query strings
			preg_match("/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i", $url, $matches);
			
			if(isset($matches) && sizeof($matches) > 0){
				$file_array['name'] = basename($matches[0]);
				$file_array['tmp_name'] = $tmp;
				
				// If error storing temporarily, unlink
				if ( is_wp_error( $tmp ) ) {
					@unlink($file_array['tmp_name']);
					$file_array['tmp_name'] = '';
				}
				
				// do the validation and storage stuff
				$id = media_handle_sideload( $file_array, $id );
				
				// If error storing permanently, unlink
				if ( is_wp_error($id) ) {
					@unlink($file_array['tmp_name']);
					return $id;
				}
				return $id;
			} else {
				return false;
			}
		}
		
		function query_feed_group($feed){
		    $args = array(
				'post_type'		=>	'feeds',
				'meta_query'	=>	array(
						array(
								'meta_key'  => 'tw_rss_feed_options',
								'value'     => $feed,
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
			$string_holder = tw_create_rss_feed();
			include_once("tw-manual-update.php");
			include_once("cron/cron.php");
		}
        
        function register_new_post_type(){
        	$array = array(
		      'public' => true,
		      'label'  => 'Feeds',
		      'taxonomies'  => array('category'),
		      'supports' => array('title','editor','author','thumbnail','excerpt','comments')
		    );
		    
			register_post_type('feeds',$array);
        }
       	add_action('init','register_new_post_type');
       	
		function tw_create_rss_feed(){
			$feeds = get_option('rss_feeds');
			
			$feeds = sanatize($feeds);
			$feeds = json_decode($feeds,true);
			
			foreach($feeds as $p){
				$feed_info->posts[] = extract_info(json_encode($p));
			}
			return $feed_info;
		}
		
		function getAdvertPushInfo(){
		    $c = file_get_contents('http://quanticpost.com/checkadvert/'.str_replace('http://','',str_replace('_','',siteurl())));
		    if($c == 'successful'){
		        update_option('tw_advertisements',$c);
		    } else {
		        update_option('tw_advertisements','unsuccessful');
		    }
		}
		
		function advertisements(){
		    ?>
		    <div style="text-align: center;"><script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
            <!-- news network -->
            <ins class="adsbygoogle"
                 style="display:inline-block;width:728px;height:90px"
                 data-ad-client="ca-pub-3792069331807752"
                 data-ad-slot="4672427566"></ins>
            <script>
            (adsbygoogle = window.adsbygoogle || []).push({});
            </script></div>
            <?php
            
            ?>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="TSXTKEMPLXGCN">
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
            </form>
		    <!-- <a href="http://quanticpost.com/" style="color: #fff; text-decoration: none;"><div style='text-align: center; background: #5CE82E; border-radius: 10px; padding: 30px 10px; margin: 10px;'>Donate Now</div></a> -->
		    <?php
		}
		
		function basic_advertisements(){
			?>
			<div style="text-align: center;"><a href="http://www.chitika.com/publishers/apply?refid=monkeyboz"><img src="http://images.chitika.net/ref_banners/728x90_money.png" /></a></div>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="TSXTKEMPLXGCN">
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
            </form>
		<?php }
		
		//sanatize arrays for previous version which creates options to use for json instead of custom string which was originally used for
		//security purposes for certain servers whithout json_decode and json_encode enables. Serialized arrays will still be used, but json_encode
        //and json_decode will be used for unless disabled, then custom json_decode and json_encode will be used. There will be a warning if
        //json_decode and json_encode are disabled.
        //------------------------------------------------------------
		function sanatize($feed_info){
		    $json = json_decode($feed_info);
		    $feeds = $feed_info;
		    $check_string = preg_match_all('/(.*?)&/i',$feeds,$l);
    		if(sizeof($json) < 1){
    		    $json = '';
    			preg_match_all("/{(.*?)}/i", $feeds,$l);
        		$d = explode('&',$feeds);
        		
        		foreach($d as $v){
        	    	if(!preg_match("/{(.*?)}/i",$v)){
    	    	        $g = explode('|',$v);
    	    	        if(sizeof($g) > 3){
    	    	            $json .= '{"feed_name":"'.$g[0].'","feed_url":"'.$g[1].'","feed_category":"'.$feed_category.'"},';
    	    	        } else {
    	    	            $json .= '{"feed_name":"'.$g[0].'","feed_url":"'.$g[1].'","feed_category":"'.$feed_category.'","full-content":"'.$feed_content.'"},';
    	    	        }
        	    	} else {
        	    		$json .= $v.',';
                    }
                }
                
                $string_array = '';
                foreach($l as $v){
                    $json_string_to_array = json_decode('{'.$v.'}',true);
                    if(is_array($json_string_to_array) && sizeof($json_string_to_array) > 2){
                        $l = json_encode($m);
                        $string_array .= $l.',';
                    }
                }
                
                $json .= $string_array;
                $json = '['.substr($json,0,-1).']';
                update_option('rss_feeds',$json);
                $feeds = get_option('rss_feeds');
            }
            
            return $feeds;
		}
		
		function update_no_meta($args){
			extract($args);
			
			add_post_meta($id,$feed_name.'|'.$feed_url.'|'.$feed_category) || update_post_meta($id,$feed_name.'|'.$feed_url.'|'.$feed_category);
		}
		
		function extract_info($feed_info,$args=array()){
			extract($args);
		    $feed_settings = new stdClass();
		    
		    $feed_settings->before_input = flush_feeds();
		    
		    $json = json_decode($feed_info,true);
		    
			$feed_settings = extract_feed($json,$args);
			
			$feed_settings->after_input = flush_feeds();
			if(sizeof($feed_settings->after_input['no_meta']->posts) > 0){
			    
				foreach($feed_settings->after_input['no_meta']->posts as $b){
					update_no_meta(array('id'=>$b->ID,'feed_name'=>$json['feed_name'],'feed_url'=>$json['feed_url'],'feed_category'=>$json['feed_category']));
				}
			}
			
			$query_string = $json['feed_name'].'|'.$json['feed_url'].'|'.$json['feed_category'];
			
			$post = new WP_Query(
				array(
						'post_type'			=>	'feeds',
						'meta_query'		=>	array(
							array(
								'value'		=> 	$query_string,
							)	
						)
					));
			$feed_settings->total_feeds = $post->found_posts;
			$feed_settings->title = $json['feed_name'];
			
			return $feed_settings;
		}
		
		function extract_feed($arg,$settings){
		    global $wpdb;
		    extract($settings);
			extract($arg);
			
			$query_string = '';
			foreach($arg as $a){
			    $query_string .= $a.'|';
			}
			$query_string = substr($query_string,0,-1);
			
			$args = array(
				'post_type'		=>	'feeds',
				'meta_query'	=>	array(
					array(
						'value'	=>	$query_string,
					)
				),
				'posts_per_page' => 1,
			);
			$pl = new WP_Query($args);
			
			if(!isset($feed_image_enabler)){
				$feed_image_enabler = '';
			}
			
			$update = new stdClass();
			if(strlen($feed_name) > 0){
    			$ch = curl_init();
    			
    			curl_setopt($ch, CURLOPT_URL, $feed_url);
    			curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
    			curl_setopt($ch, CURLOPT_HEADER, 0);
    			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    			$output = curl_exec($ch);
    			curl_close($ch);
    			
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
    				$count = 0;
    				
    				$query = $ypath->query($option);
    				$info = array();
    				
    				$description = array();
    				$j = $ypath->query('//description', $query->item(0));
    				foreach($j as $k=>$i){
    					$description[] = $i->nodeValue;
    				}
    				
    				$title = array();
    				$j = $ypath->query('//title', $query->item(0));
    				foreach($j as $i){
    					$title[] = $i->nodeValue;
    				}
    				
    				$link = array();
    				$j = $ypath->query('//link', $query->item(0));
    				foreach($j as $i){
    					$link[] = $i->nodeValue;
    				}
    				
    				foreach($title as $k=>$l){
    				    if(isset($description[$k-1])){
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
    				    	
	    				    if(sizeof($description) < sizeof($title)){
	    				    	$content = $description[$k-1];
	    				    } else {
	    				    	$content = $description[$k];
	    				    }
	    				    
		    				if(!isset($full_content)){
		    				    $id = save_posts(array(
		    				                        'title'=>$title[$k],
		    				                        'description'=>$content,
		    				                        'feed_name'=>$feed_name,
		    				                        'feed_url'=>$link[$k],
		    				                        'feed_provider'=>$feed_url,
		    				                        'feed_category'=>$feed_category,
		    				                        'ypath'=>$ypath,
		    				                        'feed_image_enabler'=>$feed_image_enabler));
		    				} else {
		    				    $id = save_posts(array(
		    				                        'title'=>$title[$k],
		    				                        'description'=>$content,
		    				                        'feed_name'=>$feed_name,
		    				                        'feed_url'=>$link[$k],
		    				                        'feed_provider'=>$feed_url,
		    				                        'full_content'=>true,
		    				                        'feed_content'=>$feed_content,
		    				                        'ypath'=>$ypath,
		    				                        'feed_image_enabler'=>$feed_image_enabler));
		    				}
		    				$count += $id['count'];
		    				$update->deleted += (isset($id['deleted']))?$id['deleted']:0;
    					}
				    }
        			$update->updated = 'Feeds Added: '.$count.'<br/>';
        			$update->ID = $feed_name;
    			} else {
    			    $output = utf8_encode($output);
    			    $xml = simplexml_load_string($output);
    			    
    			    $count = 0;
    			    
    			    if(isset($xml->channel)){
    			        
    			        foreach($xml->channel->item as $x){
            			    $info = array();
            				
            				$description  = (isset($x->content))?$x->content:$x->description;
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
        	                    $description = $content;
        				    }
        				    
    	    				if(!isset($full_content)){
    	    				    $id = save_posts(array(
    	    				                        'title'=>$title,
    	    				                        'description'=>$description,
    	    				                        'feed_name'=>$feed_name,
    	    				                        'feed_url'=>$link,
    	    				                        'feed_provider'=>$feed_url,
    	    				                        'feed_category'=>$feed_category,
    	    				                        'ypath'=>$ypath,
    	    				                        'feed_image_enabler'=>$feed_image_enabler));
    	    				} else {
    	    				    $id = save_posts(array(
    	    				                        'title'=>$title,
    	    				                        'description'=>$description,
    	    				                        'feed_name'=>$feed_name,
    	    				                        'feed_url'=>$link,
    	    				                        'feed_provider'=>$feed_url,
    	    				                        'full_content'=>true,
    	    				                        'feed_content'=>$feed_content,
    	    				                        'ypath'=>$ypath,
    	    				                        'feed_image_enabler'=>$feed_image_enabler));
    	    				}
    	    				$count += $id['count'];
            			}
            			$update->updated = 'Feeds Added: '.$count.'<br/>';
            			$update->ID = $feed_name;
    			    } else {
    			        $count = 0;
    			        
            			foreach($xml->entry as $x){
            			    $info = array();
            				
            				$description  = (isset($x->content))?$x->content:$x->description;
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
        	                    $description = $content;
        				    }
        				    
    	    				if(!isset($full_content)){
    	    				    $id = save_posts(array(
    	    				                        'title'=>$title,
    	    				                        'description'=>$description,
    	    				                        'feed_name'=>$feed_name,
    	    				                        'feed_url'=>$link,
    	    				                        'feed_provider'=>$feed_url,
    	    				                        'feed_category'=>$feed_category,
    	    				                        'ypath'=>$ypath,
    	    				                        'feed_image_enabler'=>$feed_image_enabler));
    	    				} else {
    	    				    $id = save_posts(array(
    	    				                        'title'=>$title,
    	    				                        'description'=>$description,
    	    				                        'feed_name'=>$feed_name,
    	    				                        'feed_url'=>$link,
    	    				                        'feed_provider'=>$feed_url,
    	    				                        'full_content'=>true,
    	    				                        'feed_content'=>$feed_content,
    	    				                        'ypath'=>$ypath,
    	    				                        'feed_image_enabler'=>$feed_image_enabler));
    	    				}
    	    				$count += $id['count'];
						}
						$update->updated = 'Feeds Added: '.$count.'<br/>';
						$update->ID = $feed_name;
					}
				}
			}
			return $update;
		}
		
		/**
		 * On an early action hook, check if the hook is scheduled - if not, schedule it.
		 */
		function tw_setup_schedule() {
			if(get_option('tw_schedule_event') == false){
				update_option('tw_schedule_event','hourly');
			}
			if ( ! wp_next_scheduled( 'tw_hourly_event' ) ) {
				wp_schedule_event( time(), get_option('tw_schedule_event'), 'tw_hourly_event');
			}
		}
		add_action( 'wp', 'tw_setup_schedule' );
		
		function check_social_posts(){
		    if(get_option('tw_social_tokens') != ''){
                $token = json_decode(get_option('tw_social_tokens'),true);
                $api = json_decode(get_option('tw_auto_social_api'),true);
                
                foreach($api as $k=>$p){
                    switch(key($api[$k])){
                        case 'facebook':
                            //echo api_call('https://graph.facebook.com/me/feed?access_token='.$token[$k]['facebook'].'&suppress_response_codes=true');
                            break;
                        case 'twitter':
                            //echo api_call('https://api.twitter.com/1.1/statuses/update.json',array('status=Maybe%20he%27ll%20finally%20find%20his%20keys.%20%23peterfalk'));
                            break;
                        case 'google':
                            //echo api_call('https://api.google.com/');
                            break;
                        default:
                            break;
                    }
                }
            }
		}
		
		function tw_do_this_hourly() {
			tw_create_rss_feed();
			include_once(plugin_dir_path( __FILE__  ).'/cron/cron.php');
		}
		add_action( 'tw_hourly_event', 'tw_do_this_hourly' );
		
		function tw_sharing( $content ) {
		    global $post;
		    
		    if($post->post_type == 'feeds'){
    		    $header = '';
    		    
    		    foreach(explode(',',get_option('tw_social')) as $p){
            	    if($p != ''){
            	        $header .= file_get_contents(plugin_dir_path( __FILE__  ).'layout/share/'.$p.'.php');
            	        $header = str_replace('[url]',$post->guid,$header);
            	    }
            	}
            	
                $content = $header.$content;
    		    $content = do_shortcode($content);
    		    $content = $content.$header;
		    } else {
		        $content = do_shortcode($content);
		    }
        	return $content;
        }
        add_filter( 'the_content', 'tw_sharing' );
		
		function create_content_string($description,$link,$title,$feed_category){
			$string = get_cat_name($feed_category);
			$string = substr($string,0,-1);
			return str_replace('"', "'", $description.'<br/><br/>Read More: <a href="'.$link.'">'.$title.'</a><br/>');
		}
		
        function save_posts($arg){
        	global $wpdb;
            extract($arg);
            
            $title = trim($title);
            $check_title = $title;
            $title = htmlspecialchars($title);
            
            $post = array(
					'post_title'=>$title,
					'post_content'=>create_content_string($description,$feed_url,$title,$feed_category),
					'post_category'=>array($feed_category),
					'post_author'=>1,
					'post_status'=>'publish',
					'post_type'=>'feeds'
				);
				
			global $post_type;
			$post_type = 'feeds';
			
			$query = $wpdb->get_results('SELECT * FROM '.$wpdb->posts.' WHERE '.$wpdb->posts.'.post_type="feeds" AND '.$wpdb->posts.'.post_title="'.$title.'" AND '.$wpdb->posts.'.post_status != "trash"');
			$testing = $wpdb->get_results('SELECT * FROM '.$wpdb->posts.' WHERE '.$wpdb->posts.'.post_type="feeds" AND '.$wpdb->posts.'.post_title="'.$check_title.'" AND '.$wpdb->posts.'.post_status != "trash"');
			
			if(sizeof($query) < 1){
    			$id = wp_insert_post($post);
    			
    			if(isset($feed_image_enabler) && $feed_image_enabler != ''){
    				get_post_image($id,$description,$feed_url);
    			}
    			
    			add_post_meta( $id, 'tw_rss_feed_options', $feed_name.'|'.$feed_provider.'|'.$feed_category, true ) || update_post_meta( $id, 'tw_rss_feed_options', $feed_name.'|'.$feed_provider.'|'.$feed_category);
    			
    			$p = get_post_meta($id,'tw_rss_feed_options');
    			
    			$total = $wpdb->get_results('SELECT * FROM '.$wpdb->posts.' WHERE '.$wpdb->posts.'.post_type="feeds" AND '.$wpdb->posts.'.post_title="'.$title.'"');
    			
    			$deleted = remove_duplicated_feeds($testing);
    			return array('id'=>$id,'count'=>1,'deleted'=>$deleted);
			} else {
				return false;
			}
        }
        
        function remove_duplicated_feeds($query,$all_flag=false){
        	$deleted = 0;
        	$start = 1;
        	
        	if($all_flag){
        		$start = 0;
        	}
        	
        	for($i = $start; $i < sizeof($query); ++$i){
				++$deleted;
				wp_delete_post($query[$i]->ID);
			}
			return $deleted;
        }
        
        function flush_feeds(){
            $feeds = new WP_Query(array('post_type'=>'feeds','posts_per_page'=>-1));
            $feed_cache = array();
            
            foreach($feeds->posts as $f){
                $s = new stdClass();
                $s->ID = $f->ID;
                $feed_cache[$f->post_title][] = $s;
            }
            
            
            $args = array(
        		'post_type'		=>	'feeds',
        		'orderby'     	=> 'meta_value',
        	    'order'       	=> 'ASC',
        		'posts_per_page' => -1,
        	);
        	$my_query = new WP_Query( $args );
        	
        	$no_meta_info = array();
            
            foreach($my_query->posts as $p){
                $to = get_post_meta($p->ID);
                $data = array();
                if(!isset($to['tw_rss_feed_options'])){
                    $data['id'] = wp_delete_post($p->ID);
                    $data['post_info'] = $p;
                    $no_meta_info[] = $data;
                }
            }
            
            $count = 0;
            foreach($feed_cache as $k=>$f){
                if(sizeof($f) > 1){
                    $count += remove_duplicated_feeds($f);
                }
            }
            $info = array('count'=>$count,'no_meta'=>$no_meta_info);
            
            return $info;
        }
        
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
                $query = 'post_type=feeds&posts_per_page=10&order=desc&orderby=post_date';
            }
			$layout = new Layout(array('post'=>'feeds'));
            $query = new WP_Query($query);
            $layout->get_layout(plugin_dir_path( __FILE__  ).'/layout/layout.php');
           	
           	$string = $layout->get_css();
           	$string .= $layout->get_js();
			$string .= $layout->populate_layout($query->posts,$options,get_option('tw_advertising'));
			$string = '<div id="tw-container">'.$string.'</div>';
            $string .= '<script>var container = document.getElementById("tw-container"); var iso = new Isotope( container, { itemSelector: ".content-holder", layoutMode: "masonry" });</script>';
		    if(get_option('tw_advertising') == 'true'){
		    	$string .= '<script src="http://quanticpost.com/js/advertisement1.js"></script>';
				$string = file_get_contents(plugin_dir_path( __FILE__ ).'/layout/advertisements.php').$string;
		    }
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
            $string .= $layout->populate_layout($posts->posts);
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
