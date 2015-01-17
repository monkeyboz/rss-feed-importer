<?php global $wpdb; ?>
<style>
    #new_features{
        background: #fff;
        padding: 10px;
    }
    form{
        margin-top: 20px;
    }
</style>
<h1>Settings</h1>
<?php
    $event = get_option('tw_schedule_event');
    if($event == false){
        update_option('tw_schedule_event',$_POST['schedule']);
        $event = $_POST['schedule'];
    }
?>
<?php advertisements(); ?>
<form action="" method='POST' style="margin-bottom: 10px; border-bottom: 2px solid #000; padding: 10px;">
    Schedule Feed Pulls: 
    <select name="schedule">
        <option value="daily-off">Off (will still pull info daily for our servers)</option>
        <option value="hourly" <?php if($event == 'hourly'){ echo 'selected'; }?>>Hourly</option>
        <option value="twicedaily" <?php if($event == 'twicedaily'){ echo 'selected'; }?>>Twice Daily</option>
        <option value="daily" <?php if($event == 'daily'){ echo 'selected'; }?>>Daily</option>
    </select>
    <input type="submit" value="submit" name="submit">
</form>

<h2>Impressions Report (temporary cleared <?php echo get_option('tw_schedule_event'); ?>)</h2>
<div><a href="admin.php?page=tw_impressions">Click here</a> to view full report</div>
<?php
    $f = $wpdb->get_results('SELECT meta_value FROM '.$wpdb->postmeta.' WHERE meta_key LIKE "tw_rss_feed_impression"');
    //print '<pre>'.print_r($f,true).'</pre>';
    if(!class_exists('WP_List_Table')){
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }
    include_once('table_creator.php');
    $table = new Table_Creator();
    $feed_info = new stdClass();
    $array = array();
    
    foreach($f as $a){
            $info_holder = new stdClass();
            $p = json_decode($a->meta_value,true);
        if($p['feed_name'] != ''){
            $info_holder->feed_name = $p['feed_name'];
            $info_holder->feed_url = urldecode('<a href="'.$p['feed_url'].'">'.$p['feed_url'].'</a>');
            $info_holder->current_page = urldecode('<a href="'.$p['current_page'].'" target="_blank">'.$p['current_page'].'</a>');
            $info_holder->impression = $p['impression'];
            
            $array[] = $info_holder;
        }
    }
    $feed_array = array();
    $feed_info->posts = $array;
    $table->setTemplate(array('feed_name','feed_url','current_page','impression'));
    $table->setActions(array(array('title'=>'edit','type'=>'feed','page'=>'','action'=>'trash')));
    $table->getFeeds($feed_info);
    $table->prepare_items();
?>
    <?php $table->display(); ?>
    <?php advertisements(); ?>