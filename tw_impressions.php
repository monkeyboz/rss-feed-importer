<?php $url = parse_url(site_url()); ?>
<h1>Impressions</h1>
<iframe id="impressions-window" name="impressions" src="http://quanticpost.com/impressionsreport/<?php echo str_replace('/','_',str_replace('.','^',$url['host'].$url['path'])); ?>" style="height: 900px; width: 95%;">
</iframe>