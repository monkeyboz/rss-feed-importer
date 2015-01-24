<?php include_once("table_creator.php"); ?>
<?php
    $table = new Table_Creator(); 
    $table->setActions(array('ID','title','updated','total_feeds'));
    $table->setTemplate(array('ID','title','updated','total_feeds'));
    
    $table->getFeeds($string_holder);
    $table->prepare_items();
?>
<h1>Manual Update</h1>
<?php advertisements(); ?>
<form action="" method="post">
<?php
    $table->display();
?>
</form>
<?php advertisements(); ?>