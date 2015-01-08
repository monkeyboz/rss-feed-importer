<?php
    global $wpdb;
    
    $info = $wpdb->get_results('SELECT meta_value FROM '.$wpdb->postmeta.' WHERE meta_key LIKE "tw_rss_feed_impression"');
    
    $variables = '[';
    foreach($info as $f){
        $variables .= $f->meta_value.',';
    }
    $variables = (strlen($variables) > 1)?substr($variables,0,-1).']':'[]';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://quanticpost.com/info_pull/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HEADER, false); 
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'info='.$variables.'&host='.site_url());
    $data = curl_exec($ch);
    
    $wpdb->get_results('DELETE FROM '.$wpdb->postmeta.' WHERE meta_key LIKE "tw_rss_feed_impression"');
?>