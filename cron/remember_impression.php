<?php
    global $post, $wpdb;
    
    update_post_meta($post->ID,'tw_rss_feed_impression', '');
    $l = get_post_meta($post->ID, 'tw_rss_feed_impression');
    if($post->post_type != 'post'){
        $p = get_post_meta($post->ID,'tw_rss_feed_options');
    }
    
    function curPageURL() {
		$pageURL = 'http';
		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
			$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
    }
    
    $currPage = curPageURL();
    
    update_post_meta($post->ID, 'tw_rss_feed_impression','');
    if($p[0] != '' && $post->post_type != 'post'){
        if(!isset($l[0]) || $l[0] == ''){
            $f = explode('|',$p[0]);
            $a = array('feed_name'=>$f[0],'feed_url'=>urlencode($f[1]),'feed_category'=>$f[2],'current_page'=>urlencode($currPage),'impression'=>1);
            $a = json_encode($a);
            update_post_meta($post->ID,'tw_rss_feed_impression',$a);
        } else {
            $l = $l[0];
            $f = explode('|',$p[0]);
            if(preg_match('/{"feed_name":"'.$f[0].'","feed_url":"'.urlencode($f[1]).'","feed_category":"'.$f[2].'","current_page":"'.urlencode($currPage).'"/i',$l) == 1){
                $l = preg_replace_callback('/({"feed_name":"'.$f[0].'","feed_url":"'.urlencode($f[1]).'","feed_category":"'.$f[2].'","current_page":"'.urlencode($currPage).'","impression":)(.*?)}/i','tw_increment',$l);
            } else {
                $a = array('feed_name'=>$f[0],'feed_url'=>urlencode($f[1]),'feed_category'=>$f[2],'current_page'=>$currPage,'impression'=>1);
                $a = json_encode($a);
                $l = json_encode($l);
                $l = str_replace('}', '},{'.str_replace('}','',str_replace('{','',$a)).'}', $l);
            }
            update_post_meta($post->ID,'tw_rss_feed_impression',$l);
        }
    } else {
        if(!isset($l[0]) || $l[0] == ''){
            $a = array('feed_name'=>'tw_outside_network','feed_url'=>'tw_outside_network','feed_category'=>'tw_outside_network','current_page'=>urlencode($currPage),'impression'=>1);
            $a = json_encode($a);
            update_post_meta($post->ID,'tw_rss_feed_impression',$a);
        } else {
            if(preg_match('/{"feed_name":"tw_outside_network","feed_url":"tw_outside_network","feed_category":"tw_outside_network","current_page":"'.urlencode($currPage).'"/i',$l[0]) == 1){
                $l = preg_replace_callback('/({"feed_name":"tw_outside_network","feed_url":"tw_outside_network","feed_category":"tw_outside_network","current_page":"'.urlencode($currPage).'","impression":)(.*?)}/i','tw_increment',$l[0]);
            } else {
                $a = array('feed_name'=>'tw_outside_network','feed_url'=>'tw_outside_network','feed_category'=>'tw_outside_network','current_page'=>$currPage,'impression'=>1);
                $a = json_encode($a);
                $l = json_encode($l);
                $l = str_replace('}', '},{'.str_replace('}','',str_replace('{','',$a)).'}', $l);
            }
            update_post_meta($post->ID,'tw_rss_feed_impression',$l);
        }
    }
    
    function tw_increment($matches){
        ++$matches[2];
        return $matches[1].$matches[2].'}';
    }
?>