<?php
	global $wpdb;
    
	if(sizeof($_POST) > 0 && isset($_POST['feed_name'])){
		extract($_POST);
		
		$p = explode('&',$feed_info);
		$p = array_unique($p);
		
		$p = array_filter($p);
		
		$feed_info = implode('&',$p);
		update_option('rss_feeds', $feed_info);
		$feeds = get_option('rss_feeds');
		
		if(strlen($feed_delete) > 0){
			$p = explode('&', $feed_delete);
			foreach($p as $q){
				$args = array(
					'post_type'		=>	'feeds',
					'meta_query'	=>	array(
							array(
									'meta_key' => 'tw_rss_feed_options',
									'value' => $q,
								)
						)
				);
				$my_query = new WP_Query( $args );
				foreach($my_query->posts as $p){
					wp_delete_post($p->ID);
				}
			}
		}
		
		if(strlen($feed_name) > 0){
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $feed_url);
			curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$output = curl_exec($ch);
			curl_close($ch);
			
			$output = str_replace('"',"'", $output);
			
			if(strlen($feed_category) < 1){
				$feed_category = 'uncategorized';
			}
			
			$count = 0;
			
			$dom = new DOMDocument;
			@$dom->loadXML($output);
			$ypath = new DOMXPath($dom);
			
			$selectOption = array('*/content','*/item');
			$option = '';
			foreach($selectOption as $f){
				if($ypath->query($f)->length > 0){
				    $option = $f;
				}
			}
			
			if($option != ''){
    			foreach($ypath->query($option) as $a){
    				$info = array();
    				$count = 0;
    				
    				$description = array();
    				$j = $ypath->query('//description', $a);
    				foreach($j as $k=>$i){
    					$description[] = $i->nodeValue;
    				}
    				
    				$title = array();
    				$j = $ypath->query('//title', $a);
    				foreach($j as $i){
    					$title[] = $i->nodeValue;
    				}
    				
    				$link = array();
    				$j = $ypath->query('//link', $a);
    				foreach($j as $i){
    					$link[] = $i->nodeValue;
    				}
    				
    				foreach($link as $k=>$l){
    					if(isset($full_content)){
    				        $content = file_get_contents($link[$k]);
    				        $dom = new DOMDocument;
    				        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is','',$content);
    				        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is','',$content);
    	                    @$dom->loadHTML($content);
    	                    $xpath = new DOMXPath($dom);
    	                    
    	                    $x = explode('`',$feed_content);
    	                    $l = explode('|' ,$x[1]);
    	                    if(isset($l[1])){
    	                        foreach($xpath->query('//div[@'.$l[0].'="'.$l[1].'"]') as $d){
    	                            $content = $d->nodeValue;
    	                        }
    	                    }
    	                    $description[$k] = $content;
    				    }
    				    
    				    $query = 'SELECT * FROM '.$wpdb->posts.' WHERE '.$wpdb->posts.'.post_title = "'.$title[$k].'" AND '.$wpdb->posts.'.post_type="feeds" AND '.$wpdb->posts.'.post_status!="trash"';
    				    $p = $wpdb->get_results($query);
    				    if(sizeof($p) < 1){
    	    				$post = array(
    	    						'post_title'=>$title[$k],
    	    						'post_content'=>str_replace('"', "'", $description[$k].'<br/><br/>Content From: <a href="'.$link[$k].'">'.$ypath->query('//channel/title')->item(0)->nodeValue.'</a><br/>'),
    	    						'post_category'=>array($feed_category),
    	    						'post_author'=>1,
    	    						'post_status'=>'publish',
    	    						'post_type'=>'feeds'
    	    					);
    	    				$id = wp_insert_post($post);
    	    				if(!isset($full_content)){
    	    			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category);
    	    				} else {
    	    			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category.'|full-content|'.$feed_content);
    	    				}
    	    				++$count;
    				    }
    				}
    			}
			} else {
			    $xml = simplexml_load_string($output);
			    
    			foreach($xml->entry as $x){
    			    $info = array();
    				$count = 0;
    				
    				$description  = $x->content;
    				$title = $x->title;
    				$title = str_replace("//<![CDATA[","",$title);
                    $title = str_replace("//]]>","",$title);
    				$link  = $x->id[0];
    				
					if(isset($full_content)){
				        $content = file_get_contents($link);
				        $dom = new DOMDocument;
				        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is','',$content);
				        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is','',$content);
	                    @$dom->loadHTML($content);
	                    $xpath = new DOMXPath($dom);
	                    
	                    $xi = explode('`',$feed_content);
	                    $l = explode('|' ,$xi[1]);
	                    if(isset($l[1])){
	                        foreach($xpath->query('//div[@'.$l[0].'="'.$l[1].'"]') as $d){
	                            $content = $d->nodeValue;
	                        }
	                    }
	                    $description[$k] = $content;
				    }
				    
				    $query = 'SELECT * FROM '.$wpdb->posts.' WHERE '.$wpdb->posts.'.post_title = "'.$title.'" AND '.$wpdb->posts.'.post_type="feeds" AND '.$wpdb->posts.'.post_status!="trash"';
				    $p = $wpdb->get_results($query);
				    if(sizeof($p) < 1){
	    				$post = array(
	    						'post_title'=>$title,
	    						'post_content'=>str_replace('"', "'", $description.'<br/><br/>Content From: <a href="'.$link.'">'.$x.'</a><br/>'),
	    						'post_category'=>array($feed_category),
	    						'post_author'=>1,
	    						'post_status'=>'publish',
	    						'post_type'=>'feeds'
	    					);
	    				$id = wp_insert_post($post);
	    				if(!isset($full_content)){
	    			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category);
	    				} else {
	    			    	update_post_meta($id,'tw_rss_feed_options', $feed_name.'|'.$feed_url.'|'.$feed_category.'|full-content|'.$feed_content);
	    				}
				    }
    			}
			}
			
			echo '<div>All new feeds have been entered</div>';
			
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
		}
	} else {
		$feeds = get_option('rss_feeds');
	}
?>
<style>
	#tw-content-layout{
		overflow: scroll;
		height: 400px;
		width: 100%;
	}
	#content-div{
		display: none;
	}
	.donate{
	    padding: 90px;
	    clear: both;
	    text-align: center;
	}
	#rss-function{
	    float: left;
	    width: 95%;
	    background: #fff;
	    padding: 10px;
	    border-radius: 10px;
	}
	.feed-hint{
		font-size: 11px;
		padding: 3px;
		background: #545454;
		padding: 5px;
		color: #fff;
		margin-bottom: 10px;
		border-radius: 5px;
	}
	form > div > div{
	    font-size: 14px;
	    margin-bottom: 10px;
	}
</style>
<div>
	<h1>RSS Feed Importer</h1>
	<form action="" method="POST" id="rss-function">
		<div class="feed-hint">
			<b>Hint of the day:</b> To add in a list of feeds to a page, please feel free to use the following shortcode [feed_search category="category_name"][/feed_search]
		</div>
		<div>
			<div>Feed Name</div>
			<div>
				<input type="text" name="feed_name" value="<?php echo @$feed_name; ?>"/>
			</div>
		</div>
		<div>
			<div>Feed Category</div>
			<div>
				<?php wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'feed_category', 'hierarchical' => true)); ?>
			</div>
		</div>
		<div>
			<div>Feed URL</div>
			<div>
				<input type="text" name="feed_url" value="<?php echo @$feed_url; ?>"/>
			</di>
		</div>
		<div>
			<div>Full-Content (experimental)</div>
			<div>
				<input type="checkbox" name="full_content"/>
			</div>
			<div id="full-content-status"></div>
		</div>
		<div id="content-div">
			<div>Content Div</div>
			<div id="tw-content-layout">
			</div>
			<div>
				<input type="hidden" name="feed_content" id="feed-content" value="<?php echo @$feed_content; ?>"/>
			</div>
			<input type="submit" value="Get Content" name="get-content"/>
		</div>
		<input type="hidden" name="feed_info" value="<?php echo @$feeds; ?>"/>
		<input type="hidden" name="feed_delete" value=""/>
		<div>
			<div>
				<input type="submit" name="submit" value="Save Feed"/>
			</div>
		</div>
		<style>
		    input[name="submit"]{
		        background: #93F56F;
		        border-radius: 10px;
		        border: none;
		        text-transform: uppercase;
		        padding: 10px;
		        color: #545454;
		        margin-top: 10px;
		        box-shadow: 2px 2px 0px #545454;
		    }
		    input[name="get-content"]{
		        background: #CF5300;
		        color: #fff;
		    }
		    input{
		        padding: 10px;
		        font-size: 15px;
		        border: none;
		    }
		    select{
		        padding: 20px;
		        font-size: 15px;
		        border: none;
		    }
			.header{
				clear: both;
			}
			.header > div{
				float: left;
				width: 25%;
			}
			.layout{
				clear: both;
			}
			.row{
				clear: both;
			}
			.row > div{
				float: left;
				width: 25%;
			}
			.header{
			    background: #000;
			    color: #fff;
			    padding: 10px;
			    height: 20px;
			    margin-top: 15px;
			}
		</style>
		<div class="header">
			<div>Title</div>
			<div>URL</div>
			<div>Category</div>
			<div>Action</div>
			<div class="clear"></div>
		</div>
		<div class="layout">
			<?php $c = explode('&', $feeds); if(strlen($c[0]) > 0){ foreach($c as $a=>$b){ 
				$g = explode('|', $b);
			?>
			<div class="row" data-info="<?php echo $b; ?>">
				<div><?php echo $g[0]; ?></div>
				<div><?php echo $g[1]; ?></div>
				<div><?php echo get_cat_name($g[2]); ?></div>
				<div><a href="">Remove</a></div>
			</div>
			<?php } } ?>
		</div>
	</form>
	<div class="donate">
	    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="hosted_button_id" value="ESVXPD73ET662">
            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
        </form>
	</div>
</div>
<script>
	var fullcontent = document.getElementById('rss-function').getElementsByTagName('input')[2];
	document.getElementById('content-div').style.display = 'none';
	fullcontent.onclick = function(){
		if(document.getElementById('content-div').style.display == 'none'){
			document.getElementById('content-div').style.display = 'block';
		} else {
			document.getElementById('content-div').style.display = 'none';
		}
	}
	function removeElement(){
		document.getElementById('rss-function').getElementsByTagName('input')[5].value = document.getElementById('rss-function').getElementsByTagName('input')[5].value.replace(this.parentNode.parentNode.getAttribute('data-info'),'');
		document.getElementById('rss-function').getElementsByTagName('input')[5].value = document.getElementById('rss-function').getElementsByTagName('input')[5].value.replace('&'+this.parentNode.parentNode.getAttribute('data-info'),'');
		
		document.getElementById('rss-function').getElementsByTagName('input')[6].value += this.parentNode.parentNode.getAttribute('data-info')+'&';
		var a = this.parentNode.parentNode.getElementsByTagName('div');
		for(var i = 0; i < a.length; ++i){
			a[i].style.textDecoration = 'line-through';
		}
		return false;
	}
	
	var l = document.getElementById('rss-function').getElementsByTagName('input')[4];
	l.onclick = function(){ getLayout(document.getElementById('rss-function').getElementsByTagName('input')[1].value); return false; }
	
	function createCORSRequest(method, url) {
	  if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
	  	xhr=new XMLHttpRequest();
	  } else {// code for IE6, IE5
	  	xhr=new ActiveXObject("Microsoft.XMLHTTP");
	  }
	  xhr.open(method, '<?php echo plugins_url().'/rss-feeds/curl_functions.php?url='; ?>'+url,true);
	  return xhr;
	}
	
	function httpGet(theUrl,type){
	    var xmlhttp = createCORSRequest('GET',theUrl);
	    xmlhttp.onreadystatechange=function()
	    {
		        if (xmlhttp.readyState==4 && xmlhttp.status==200)
		        {
		            fetch_options(xmlhttp,type);
		        }
	    }
	    xmlhttp.send();
	}
	
	function StringToXML(oString) {
		//code for IE
		if (window.ActiveXObject) {
			var oXML = new ActiveXObject("Microsoft.XMLDOM"); oXML.loadXML(oString);
			return oXML;
		}
		// code for Chrome, Safari, Firefox, Opera, etc.
		else {
			return (new DOMParser()).parseFromString(oString, "text/xml");
		}
	}
	
	function fetch_options(xmlhttp,type){
		if(type == 'initial'){
			var lay = document.getElementById('tw-content-layout');
			xml = StringToXML(xmlhttp.responseText);
			lay.innerHTML = xml.getElementsByTagName('link')[2].innerHTML;
			httpGet(encodeURIComponent(xml.getElementsByTagName('link')[2].innerHTML),'finish');
		} else {
			var info = '';
			var lay = document.getElementById('tw-content-layout');
			lay.innerHTML = xmlhttp.responseText;
			if(xmlhttp.responseText.search('Moved Permanently') != -1){
				var resp = xmlhttp.responseText.match(/(http:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)\"/);
				httpGet(encodeURIComponent(resp[0].replace('"','')),'finish');
			} else {
				lay.onmouseover = function(e){ selectContent(e,this,'in'); }
				lay.onmouseout = function(e){ selectContent(e,this,'out'); }
				lay.onclick = function(e){ 
					info = getContentInfo(e,this);
					document.getElementById('feed-content').value = info;
					document.getElementById('content-div').style.display = 'none';
					var db = document.getElementById('full-content-status');
					db.innerHTML = 'Full Content Saved - click save feed to read in feed';
					db.style.fontWeight = 'bold';
					db.style.background = '#000';
					db.style.padding = '10px';
					db.style.color = '#93F56F';
				}
			}
		}
	}
	
	function parseFunction(element,nodeName){
		if(element.parentNode.getAttribute('id') == 'tw-content-layout'){
			return nodeName;
		} else {
			className = element.parentNode.getAttribute('class');
			if(className == null){ 
				className = 'id|'+element.parentNode.getAttribute('id');
			} else {
				className = 'class|'+className;
			}
			nodeName += "`"+parseFunction(element.parentNode,className);
		}
		return nodeName;
	}
	
	function getContentInfo(e,info){
		return parseFunction(e.target,e.target.getAttribute('class'));
	}
	
	function selectContent(e,info,type){
		var border = 'none';
		if(type == 'in') border = '1px solid #000';
		e.target.style.border = border;
	}
	
	function getLayout(url){
		httpGet(encodeURIComponent(url),'initial');
	}

	var info = document.getElementById('rss-function').getElementsByClassName('row');
	for(var i = 0; i < info.length; ++i){
		info[i].getElementsByTagName('a')[0].onclick = removeElement;
	}
</script>