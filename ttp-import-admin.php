<?php
	global $wpdb;
	
	$status_str = '';
	
	if(isset($_POST['action']) && $_POST['action'] == 'trash' && isset($_POST['feed']) && sizeof($_POST['feed']) > 0){
		$feeds = get_option('rss_feeds');
		$feeds = sanatize($feeds);
		
		$feeds = json_decode($feeds,true);
		
		if(sizeof($_POST['feed']) > 0){
			foreach($_POST['feed'] as $q){
			    $count_delete = 0;
				$args = array(
					'post_type'		=>	'feeds',
					'meta_query'	=>	array(
							array(
									'meta_key'  => 'tw_rss_feed_options',
									'value'     => $q,
								)
						),
					'posts_per_page' => -1,
				);
				$my_query = new WP_Query( $args );
				
                
				
				foreach($my_query->posts as $p){
				    ++$count_delete;
					wp_delete_post($p->ID);
					wp_delete_attachment( get_post_thumbnail_id($p->ID));
				}
				
				$status_str .= '<div class="total-post-deleted">'.substr($q,0,strpos($q,'|')).' Total Posts Deleted: '.$count_delete.'</div>';
				$p = explode('|',$q);
				$f = array('feed_name','feed_url','feed_category','full_content','feed_content','feed_image_enabler');
				
				$test = array();
				foreach($p as $k=>$v){
					if($v == 'full-content'){
						$test[$f[$k]] = 'true';
					} else {
						$test[$f[$k]] = $v;
					}
				}
				
				foreach($feeds as $k=>$f){
                    if($f['feed_name'] == $test['feed_name'] && $f['feed_url'] == $test['feed_url'] && $f['feed_category'] == $test['feed_category']){
                        unset($feeds[$k]);
                    }
				}
			}
		}
		
		$feeds = json_encode($feeds);
		$j = json_decode($feeds,true);
		if($j[0]['feed_name'] != ''){
	    	update_option('rss_feeds',$feeds);
		} else {
		    update_option('rss_feeds','');
		}
	}
    
    $validate = array();
    
    foreach($_POST as $k=>$p){
        if($k == 'feed_name'){
            $_POST['feed_name'] = trim($_POST['feed_name']);
            if($_POST['feed_name'] == ''){
                $validate[$k]['error'] = 'Cannot be left blank';
            }
        }
        if($k == 'feed_url'){
            $_POST['feed_url'] = trim($_POST['feed_url']);
            if($_POST['feed_url'] == ''){
                $validate[$k]['error'] = 'Cannot be left blank';
            } else if(preg_match("/http(s)?:\/\/(\w+).(\w+)\.?(\w+)?/i",$_POST['feed_url']) == 0) {
                $validate[$k]['error'] = 'Invalid URL';
            }
        }
    }
    
	if(sizeof($validate) < 1 && $_POST['feed_name'] != ''){
		extract($_POST);
		
        $feed = get_option('rss_feeds');
        $feed = sanatize($feed);
        
        if(strpos($feed,'"feed_name":"'.$feed_name.'"') === false && strpos($feed,'"feed_url":"'.str_replace('/','\/',$feed_url).'"') === false){
    		if(!isset($full_content) && !isset($feed_content) && $feed_content != ''){
    		    $feed_layout = '{"feed_name":"'.$feed_name.'","feed_url":"'.$feed_url.'","feed_category":"'.$feed_category.'","full-content":"'.$feed_content.'"}';
            } else {
                $feed_layout = '{"feed_name":"'.$feed_name.'","feed_url":"'.$feed_url.'","feed_category":"'.$feed_category.'"}';
            }
            
            if(isset($feed_image_enabler) && $feed_image_enabler == 'on'){
                $feed_layout = substr($feed_layout,0,-1).',"feed_image_enabler":"true"}';
            }
            
            $feed_layout = json_decode($feed_layout,true);
    		$feed = json_decode($feed,true);
    		
    		$feed_holder = array();
    		foreach($feed as $f){
    			if($f['feed_name'] != '') $feed_holder[] = $f;
    		}
    		
    		$feed = $feed_holder;
    		array_push($feed,$feed_layout);
    		$feed = json_encode($feed);
    		
    		$feed_layout = json_encode($feed_layout);
    		
    		update_option('rss_feeds',$feed);
    		$feeds = get_option('rss_feeds');
    		
    		extract_info($feed_layout,$_POST);
            
    		$status_str .= '<div style="padding: 10px; background: #fff;">All new feeds have been entered</div>';
        } else {
            $validate['feed_name']['error'] = 'Duplicate Feed';
            $validate['feed_url']['error'] = 'Duplicate Feed';
            $feeds = get_option('rss_feeds');
        }
	} else {
		$feeds = get_option('rss_feeds');
		update_option('rss_feeds',sanatize($feeds));
		$feeds = get_option('rss_feeds');
	}
?>
<style>
    #tw-content-layout {
    	overflow: scroll;
    	height: 400px;
    	width: 100%;
    }
    
    .total-post-deleted {
    	margin-top: 5px;
    	background: #fff;
    	color: #545454;
    	border-left: #C70000 solid 5px;
    	padding: 5px;
    }
    
    #content-div {
    	display: none;
    }
    
    .donate {
    	padding: 90px;
    	clear: both;
    	text-align: center;
    }
    
    #rss-function>div {
    	float: left;
    }
    
    .feed-hint {
    	font-size: 11px;
    	padding: 3px;
    	background: #545454;
    	padding: 5px;
    	color: #fff;
    	margin-bottom: 10px;
    	border-radius: 5px;
    	clear: both;
    	width: 90%;
    }
    
    form>div>div {
    	font-size: 14px;
    	margin-bottom: 10px;
    }
    
    .feed-option-holder {
    	clear: both;
    }
    
    .feed-option input,.feed-option select {
    	width: 90%;
    }
    
    .feed-option {
    	float: left;
    	width: 30%;
    }
    
    #feed_category {
    	font-size: 20px;
    	height: 45px;
    }
    
    .feed-option-holder {
    	background: #fff;
    	padding: 10px;
    	border-radius: 10px;
    	width: 90%;
    }
    .error{
        color: #ff0000;
        font-size: 11px;
    }
</style>
<div style="padding: 10px;">
	<div>
		<?php echo $status_str; ?>
	</div>
	<h1>RSS Feed Importer</h1>
	<?php advertisements(); ?>

	<form action="" method="POST" id="rss-function">
		<div class="feed-hint">
			<b>Hint of the day:</b> To add in a list of feeds to a page, please
			feel free to use the following shortcode [feed_search
			category="category_name"][/feed_search]
		</div>
		<div class="feed-option-holder">
			<div class="feed-option">
				<div>Feed Name <span class="error"><?php echo @$validate['feed_name']['error']; ?></span></div>
				<div>
					<input type="text" name="feed_name"
						value="<?php echo @$feed_name; ?>" />
				</div>
			</div>
			<div class="feed-option">
				<div>Feed Category</div>
				<div>
					<?php wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'feed_category', 'hierarchical' => true)); ?>
				</div>
			</div>
			<div class="feed-option">
				<div>Feed URL <span class="error"><?php echo @$validate['feed_url']['error']; ?></span></div>
				<div>
					<input type="text" name="feed_url"
						value="<?php echo @$feed_url; ?>" />
				</div>
			</div>
			<div class="clear"></div>
		</div>
		<div>
			<div style="background: #545454; margin-top: 20px; border-radius: 10px; color: #fff; padding: 10px;">Feed Image Enabler (download up to 2 images from feed | may be slow on some servers) <input type="checkbox" name="feed_image_enabler" <?php if(isset($feed_image_enabler) && sizeof($feed_image_enabler) > 0) echo 'checked'; ?>/></div>
		</div>
</div>
<style>
#content-div {
	clear: both;
	width: 90%;
	margin-bottom: 10px;
}
</style>
<div id="content-div">
	<div>Content Div</div>
	<div id="tw-content-layout"></div>
	<div>
		<input type="hidden" name="feed_content" id="feed-content"
			value="<?php echo @$feed_content; ?>" />
	</div>
	<input type="submit" value="Get Content" name="get-content" />
</div>
<div style="clear: both;">
	<div>
		<input type="submit" name="submit" value="Save Feed" />
	</div>
</div>
<style>
input[name="submit"] {
	background: #93F56F;
	border-radius: 10px;
	border: none;
	text-transform: uppercase;
	padding: 10px;
	color: #545454;
	margin-top: 10px;
	box-shadow: 2px 2px 0px #545454;
}

input[name="get-content"] {
	background: #CF5300;
	color: #fff;
}

input {
	padding: 10px;
	font-size: 15px;
	border: none;
}

select {
	padding: 20px;
	font-size: 15px;
	border: none;
}

.header {
	clear: both;
}

.header>div {
	float: left;
	width: 25%;
}

.layout {
	clear: both;
}

.row {
	clear: both;
}

.row>div {
	float: left;
	width: 25%;
}

.header {
	background: #000;
	color: #fff;
	padding: 10px;
	height: 20px;
	margin-top: 15px;
}
</style>
</form>
<?php
    if(!class_exists('WP_List_Table')){
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }
    include_once('table_creator.php');
    $table = new Table_Creator();
    $feed_info = new stdClass();
    $feed_array = array();
    if($feeds != ''){
        foreach(json_decode($feeds) as $f){
        	$query_string = $f->feed_name.'|'.$f->feed_url.'|'.$f->feed_category;
        	
            $args = array(
				'post_type'		=>	'feeds',
				'meta_query'	=>	array(
						array(
								'meta_key'  => 'tw_rss_feed_options',
								'value'     => $query_string,
							)
					),
				'posts_per_page' => -1,
			);
			$my_query = new WP_Query( $args );
            
            $info_holder = new stdClass();
            $info_holder->ID = $query_string;
            $info_holder->title = $f->feed_name;
            $info_holder->category = get_cat_name($f->feed_category);
            $info_holder->total_feeds = $my_query->found_posts;
            $feed_array[] = $info_holder;
        }
    }
    $feed_info->posts = array();
    if($feed_array[0]->title != ''){
        $feed_info->posts = $feed_array;
    }
    $table->setTemplate(array('ID','title','category','total_feeds'));
    $table->setActions(array(array('title'=>'edit','type'=>'feed','page'=>'','action'=>'trash')));
    $table->getFeeds($feed_info);
    $table->prepare_items();
?>
<form action="" method="post">
	<?php $table->display(); ?>
</form>
<?php advertisements(); ?>
<script>
	var fullcontent = document.getElementById('full_content');
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