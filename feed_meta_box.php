<?php 
    $json = get_post_meta($post->ID);
?>
<style>
    .image-holder {
        width: 100px;
        float: left;
        margin: 10px;
    }
    .image-holder img {
        width: 100%;
    }
    .img-tag-holder{
        border: 5px solid #ababab;
        overflow: hidden;
        width: 100%;
        height: 100px;
    }
    .clear {
        clear: both;
    }
</style>
<div>
    <?php 
    foreach($json as $k=>$j){
        preg_match("/rss-feed-image/i",$k,$b);
        if(sizeof($b) > 0){
    ?>
    <div class="image-holder">
        <div class="img-tag-holder"><img src="<?php echo wp_get_attachment_image_src($j[0])[0]; ?>"/></div>
        <div>Delete <input type="checkbox" name="<?php echo $k; ?>" value="<?php echo $k; ?>"/></div>
    </div>
    <?php 
        }
    } ?>
    <div class="clear"></div>
</div>