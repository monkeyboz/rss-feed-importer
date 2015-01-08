<?php
    $feeds = get_option('rss_feeds');
    $feeds = sanatize($feeds);
    $feeds = json_decode($feeds);
    $f = array();
    
    foreach($feeds as $k=>$v){
        $info['name'] = $v->feed_name;
        $info['find'] = $v->feed_name;
        $info['val'] = json_encode($feeds[$k]);
        $f[] = $info;
    }
    
    $feeds = $f;
    $f = array();
    
    if($_GET['selected_feed']){
    	$selected_feed = json_decode(urldecode(str_replace("\\","",$_GET['selected_feed'])),true);
    	
    	$query_template = array("feed_name","feed_url","feed_category","full_content","feed_content");
    	$select_query = '';
    	if(sizeof($selected_feed) > 0){
    		foreach($query_template as $f){
    		    if(isset($selected_feed[$f])){
    			    $selected_query .= $selected_feed[$f].'|';
    		    }
    		}
    	}
    	
    	$selected_query = substr($selected_query,0,-1);
    }
    
    function current_page_url() {
    	$pageURL = 'http';
    	if( isset($_SERVER["HTTPS"]) ) {
    		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
    	}
    	$pageURL .= "://";
    	if ($_SERVER["SERVER_PORT"] != "80") {
    		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    	} else {
    		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    	}
    	return $pageURL;
    }
?>
<h1>Feed List <?php if(isset($_GET['selected_feed'])){ echo '- '.$selected_feed['feed_name']; } ?></h1>
<?php advertisements(); ?>
<form action="" method="GET">
    <select id="selected_feeds" name="selected_feed">
        <option>Select A Feed From The List Below</option>
        <?php
        foreach($feeds as $f){
        	$selected = '';
        	if($f['name'] == $selected_feed['feed_name']){
        		$selected = 'selected';
        	}
            echo "<option value='".$f['val']."' ".$selected.">".$f['name']."</option>";
        }
        ?>
    </select>
    <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>"/>
    <input type="submit" name="submit" value="submit"/>
</form>
<?php
    if(!class_exists('WP_List_Table')){
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }
    include_once('table_creator.php');
    
    $layout = new Table_Creator();
    $layout->process_bulk_action();
    
    $query = query_feed_group($selected_query);
    
    $layout->getFeeds($query);
    $layout->prepare_items();
?>
<form id="feeds-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
<?php
        $layout->display();
?>
</form>
<?php advertisements(); ?>
