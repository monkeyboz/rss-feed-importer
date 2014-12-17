<?php
    $feeds = get_option('rss_feeds');
    $feeds = explode('&',$feeds);
    $f = array();
    foreach($feeds as $v){
        $holder = explode('|',$v);
        
        $info['name'] = $holder[0];
        $info['find'] = $v;
        $f[] = $info;
    }
    $feeds = $f;
    $f = array();
?>
<h1>Feed List <?php if(isset($_GET['selected_feed'])){ echo '- '.$_GET['selected_feed']; } ?></h1>
<form action="" method="GET">
    <select id="selected_feeds" name="selected_feed">
        <option>Select A Feed From The List Below</option>
        <?php
        foreach($feeds as $f){
            echo "<option value='".$f['name']."'>".$f['name']."</option>";
        }
        ?>
    </select>
    <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>"/>
    <input type="submit" name="submit" value="submit"/>
</form>
<div>
<?php
    if(!class_exists('WP_List_Table')){
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }
    include_once('table_creator.php');
    //$query = query_feed_group($_POST['selected_feed']);
    //$layout = new Example_List();
    //$layout->prepare_items();
    
    //$layout->display();
        $layout = new Table_Creator();
        $layout->process_bulk_action();
        
        $query = query_feed_group($_GET['selected_feed']);
        
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
</div>
<script>
    document.getElementById('');
</script>

