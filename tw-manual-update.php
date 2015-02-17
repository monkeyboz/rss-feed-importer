<?php include_once("table_creator.php"); ?>
<?php
    $table = new Table_Creator(); 
    $table->setActions(array('ID','title','updated','total_feeds'));
    $table->setTemplate(array('ID','title','updated','total_feeds'));
    
    $table->getFeeds($string_holder);
    $table->prepare_items();
    
    include_once('social_autopost.php');
    
    if(get_option('tw_social_tokens')){
    	$social = new Social(get_option('tw_social_tokens'),get_option('tw_auto_social_api'));
    	
    	$api['tokens'] = json_decode(get_option('tw_social_tokens'),true);
	    $api['api'] = json_decode(get_option('tw_auto_social_api'),true);
	    
	    foreach($api['tokens'] as $k=>$p){
	        foreach($p as $a=>$b){
	            $api[$a] = array('tokens'=>$b,'api'=>$api['api'][$k][$a]);
	        }
	    }
    }
   	
    unset($api['tokens']);
    unset($api['api']);
?>
<h1>Manual Update</h1>
<?php advertisements(); ?>
<form action="" method="post">
<?php
    $table->display();
?>
</form>
<?php advertisements(); ?>