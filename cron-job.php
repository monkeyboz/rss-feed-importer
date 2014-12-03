<?php
    $feeds = get_option('rss_feeds');
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
		$post = array(
				'post_title'=>$a['title'],
				'post_content'=>str_replace('"', "'", 'Content From: <a href="'.$a['link'].'">'.$xml['channel']['title'].'</a> '.$a['description']),
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
	
	if(strlen($feeds) > 0){
		if(isset($full_content) && isset($feed_content)){
			update_option('rss_feeds',$feeds.'&'.$feed_name.'|'.$feed_url.'|'.$feed_category.'|full-content|'.$feed_content);
		} else {
			update_option('rss_feeds',$feeds.'&'.$feed_name.'|'.$feed_url.'|'.$feed_category);
		}
	} else {
		update_option('rss_feeds',$feed_name.'|'.$feed_url.'|'.$feed_category);
	}
	$feeds = get_option('rss_feeds');
	$p = explode('&',$feeds);
	$p = array_unique($p);
	$p = array_filter($p);
	$feeds = implode('&',$p);