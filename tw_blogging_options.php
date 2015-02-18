<?php
    $url = parse_url(site_url());
    $url = str_replace($url['scheme'].'://','',site_url());
    $url = str_replace('/','_',str_replace('.','^',$url));
    
    if(isset($_POST['tw_advertising']) && $_POST['tw_advertising'] != ''){
        update_option('tw_advertising','true');
    } elseif($_POST['submit'] == 'submit') {
    	update_option('tw_advertising','');
    }
    
    $advertisements = get_option('tw_advertising');
    
    if($_POST['submit'] == 'submit'){
        $auto_social = '';
        if(isset($_POST['tw_auto_social'])){
            foreach($_POST['tw_auto_social'] as $k=>$a){
                $auto_social .= $k.'|';
            }
        }
        update_option('tw_auto_social',$auto_social);
    }
    
    if(!isset($_POST['tw_advertising'])){
        api_call('http://quanticpost.com/advertisingshare/'.$url,array('remove'=>'true'));
    } else {
        api_call('http://quanticpost.com/advertisingshare/'.$url);
    }
    
    $share = array();
    if(isset($_POST['social']) && sizeof($_POST['social']) > 0){
        $shared = '';
        foreach($_POST['social'] as $k=>$ae){
            $shared .= $k.',';
            $share[$k] = 'on';
        }
        update_option('tw_social',$shared);
    } elseif(isset($_POST['submit'])) {
        update_option('tw_social','');
    }
    
    if(isset($_POST['tw_auto_social_api']) && sizeof($_POST['tw_auto_social_api']) > 0){
    	$api = '[';
    	foreach($_POST['tw_auto_social_api'] as $k=>$a){
    		if($a != 'Enter Id'){
    			$api .= '{"'.$k.'":"'.$a.'"},';
    		}
    	}
    	$api = substr($api,0,-1);
    	$api .= ']';
    	update_option('tw_auto_social_api',$api);
    } elseif(isset($_POST['submit'])) {
    	update_option('tw_auto_social_api','');
    }
    
    $api = json_decode(get_option('tw_auto_social_api'),true);
    if(is_array($api)){
    	foreach($api as $a=>$p){
    		$api[$a] = $p;
    		foreach($p as $k=>$j){
    			$api[$k] = $j;
    		}
    		unset($api[$a]);
    	}
    }
    
    if(isset($_POST['tw_auto_social_api_token']) && sizeof($_POST['tw_auto_social_api_token'] > 0)){
        $tokens = '[';
        foreach($_POST['tw_auto_social_api_token'] as $k=>$a){
            if($a != 'Enter Id'){
            	$tokens .= '{"'.$k.'":"'.$a.'"},';
            }
        }
        $tokens = substr($tokens,0,-1);
        $tokens .= ']';
        update_option('tw_social_tokens',$tokens);
    } elseif(isset($_POST['submit'])){
        update_option('tw_social_tokens','');
    }
    
    $auto_social_apitoken = json_decode(get_option('tw_social_tokens'),true);
    if(is_array($auto_social_apitoken)){
	    foreach($auto_social_apitoken as $a=>$t){
	        $auto_social_apitoken[$a] = $t;
	        foreach($t as $k=>$b){
	            $auto_social_apitoken[$k] = $b;
	        }
	        unset($auto_social_apitoken[$a]);
	    }
    }
    
    $share = explode(',',get_option('tw_social'));
    $d = array();
    
    foreach($share as $f){
        $d[$f] = 'on';
    }
    $share = $d;
    $d = null;
?>
<script src="<?php echo plugins_url().'/rss-feed-importer/js/hello.js'; ?>"></script>
<style>
    form{
        padding: 10px;
    }
    label{
        width: 250px;
        float: left;
    }
    .share{
        padding: 10px;
        font-weight: bold;
        padding-top: 90px;
        clear: both;
    }
    .top-share{
    	clear: both;
    }
    form > div > div{
        float: left;
        width: 30px;
        text-align: center;
        margin: 10px;
    }
    form > div > div > img{
        width: 100%;
    }
    .auto-share-options > div{
        float: left;
        width: 100px;
        height: 100px;
        border: 1px solid #ababab;
        padding: 10px;
        background: #FAFAFA;
        box-shadow: 0px 3px 5px rgba(0,0,0,.1);
    }
    #show-information > div{
		width: 100%;
		text-align: left;
	}
    .auto-share-options{
    	clear: both;
    }
    .facebook-icon, .digg-icon, .twitter-icon, .reddit-icon, .linkedin-icon, .google-icon{
        width: 70px;
        height: 70px;
        margin: 10px auto;
        background: url(http://designmodo.com/wp-content/uploads/2013/06/preview-flat-icons.png);
    }
    .facebook-icon{
        background-position: -180px -200px;
    }
    .digg-icon{
        background-position: 95px -115px;
    }
    .twitter-icon{
        background-position: -183px 95px;
    }
    .google-icon{
        background-position: -25px -285px;
    }
    .reddit-icon{
        background-position: -265px -370px;
    }
    .linkedin-icon{
        background-position: 178px -285px;
    }
    .advertising{
        background: #121212;
        color: #fff;
        padding: 10px;
    }
    .tw_advertising{
    	clear: both;
    	width: 100%;
    }
    .clear{
        clear: both;
    }
</style>
<?php 
    $auto_social = explode('|',get_option('tw_auto_social')); 
    foreach($auto_social as $f){
        $auto_social[$f] = $f;
    }   
?>
<h1>Monitization/Sharing Options</h1>
<form action="" method="POST">
    <div class="top-share">
        <h3>Social Networking</h3>
        <div>
            <img src="http://png-2.findicons.com/files/icons/524/web_2/512/facebook.png"/>
            <input type="checkbox" name="social[facebook]" <?php if($share['facebook']){ echo 'checked'; } ?>/>
        </div>
        <div>
            <img src="http://tinzimarketing.com/wp-content/uploads/2014/08/twitter-logo-png-transparent-background.png"/>
            <input type="checkbox" name="social[twitter]" <?php if($share['twitter']){ echo 'checked'; } ?>/>
        </div>
        <div>
            <img src="http://icons.iconarchive.com/icons/position-relative/social-1/128/linkedin-icon.png"/>
            <input type="checkbox" name="social[linkedin]" <?php if($share['linkedin']){ echo 'checked'; } ?>/>
        </div>
        <input type="hidden" value="no" name="socialbuttons"/>
        <div class="clear"></div>
    </div>
    <div class="tw_advertising">
    	<h2>Advertisements</h2>
    	<span class="advertising">Become part of the advertising network! <strong>(Click Here to Signup <a href="http://quanticpost.com/advertisingshare/<?php echo htmlspecialchars(str_replace('.','^',str_replace('/','_',str_replace('http://','',get_bloginfo('wpurl'))))); ?>" target="_blank">Read More</a> )</strong> <input type="checkbox" name="tw_advertising" <?php if($advertisements != '') echo 'checked'; ?> /></span>
    </div>
    <style>
        h2{
            margin-top: 40px;
        }
        .hint-info{
            background: #121212; 
            color: #fff; 
            box-shadow: 0px 2px 5px rgba(0,0,0,.2); 
            padding: 10px;
            margin-bottom: 10px;
        }
        .hint-info a{
            background: #ffa500;
            padding: 2px 10px; 
            color: #000;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
    <h2>Social Sharing Settings</h2>
    <div class="hint-info">
        Working closely with the below social networking companies, we're capable of making the most out of your social networking experiences while running our
        plugin. These features are currently being developed and will be released on a later date, but please feel free to test out the features on this page and 
        please feel free to <a href="http://quanticpost.com/purchase" target="_blank">Donate Now!</a> to speed up the development process.
    </div>
    <div class="auto-share-options">
        <div>
            <lable>Facebook</lable>
            <input type="checkbox" name="tw_auto_social[facebook]" <?php if($auto_social['facebook']) echo 'checked'; ?> />
            <input type="text" style="width: 100%" name="tw_auto_social_api[facebook]" value="<?php if(isset($api['facebook'])) echo $api['facebook']; ?>" />
            <lable>Access Token</label>
            <input type="text" style="width: 100%" name="tw_auto_social_api_token[facebook]" value="<?php if(isset($auto_social_apitoken['facebook'])) echo $auto_social_apitoken['facebook']; ?>" />
            <div class="facebook-icon"></div>
        </div>
        <div>
            <lable>Twitter</lable>
            <input type="checkbox" name="tw_auto_social[twitter]" <?php if($auto_social['twitter']) echo 'checked'; ?> />
            <input type="text" style="width: 100%" name="tw_auto_social_api[twitter]" value="<?php if($api['twitter']) echo $api['twitter']; ?>" />
            <lable>Access Token</label>
            <input type="text" style="width: 100%" name="tw_auto_social_api_token[twitter]" value="<?php if(isset($auto_social_apitoken['twitter'])) echo $auto_social_apitoken['twitter']; ?>" />
            <div class="twitter-icon"></div>
        </div>
        <div>
            <lable>Google</lable>
            <input type="checkbox" name="tw_auto_social[google]" <?php if($auto_social['google']) echo 'checked'; ?> />
            <input type="text" style="width: 100%" name="tw_auto_social_api[google]" value="<?php if($api['google']) echo $api['google']; ?>" />
            <lable>Access Token</label>
            <input type="text" style="width: 100%" name="tw_auto_social_api_token[google]" value="<?php if(isset($auto_social_apitoken['google'])) echo $auto_social_apitoken['google']; ?>" />
            <div class="google-icon"></div>
        </div>
    </div>
    <div class="share-info">
    	<h2></h2>
    	<div class="description"></div>
    </div>
    <div class="share">
        <?php echo api_call('http://quanticpost.com/socialnetworking','social_token='.get_option('tw_auto_social_api').'&social_post='.get_option('tw_social_tokens')); ?>
    </div>
   	<div id="show-information">
   		<div class="facebook">
   			<h2>Facebook API Walkthrough</h2>
   			<ol>
   				<li><strong>Go to and login at the following link </strong> <a href="https://developers.facebook.com/apps/" target="_blank">Facebook Development</a></li>
   				<li><strong>Click or create a new application</strong></li>
   				<li><strong>Follow the on-screen directions</strong></li>
   				<li><strong>Copy the client-id and paste into this screen under facebook</strong></li>
   			</ol>
   		</div>
   		<div class="twitter">
   			<h2>Twitter API Walkthrough</h2>
   			<ol>
   				<li><strong>Go to and login at the following link </strong> <a href="https://apps.twitter.com/" target="_blank">Twitter Apps</a></li>
   				<li><strong>Click or create a new application</strong></li>
   				<li><strong>Follow the on-screen directions</strong></li>
   				<li><strong>Click on the keys and access tokens tab</strong></li>
   				<li><strong>Copy the consumer key</strong></li>
   			</ol>
   		</div>
   		<div class="linkedin">
   			<h2>Linkedin API Walkthrough</h2>
   			<ol>
   				<li><strong>Go to and login at the following link </strong> <a href="https://www.linkedin.com/secure/developer" target="_blank">LinkedIn Development</a></li>
   				<li><strong>Click or create a new application</strong></li>
   				<li><strong>Follow the on-screen directions</strong></li>
   				<li><strong>Copy the consumer key</strong></li>
   			</ol>
   		</div>
   		<div class="google">
   			<h2>Google+ API Walkthrough</h2>
   			<ol>
   				<li><strong>Go to and login at the following link </strong> <a href="https://console.developers.google.com/project" target="_blank">Google+ Development</a></li>
   				<li><strong>Click or create a new application</strong></li>
   				<li><strong>Follow the on-screen directions</strong></li>
   				<li><strong>Click on the keys and access tokens tab</strong></li>
   				<li><strong>Copy the consumer key</strong></li>
   			</ol>
   		</div>
   	</div>
   	<script>
		options = document.getElementsByClassName('auto-share-options')[0].getElementsByTagName('div');
		var layout = document.getElementById('show-information').getElementsByTagName('div');
		for(var i = 0; i < layout.length; ++i){
			layout[i].style.display = 'none';
		}
		for(var i = 0; i < options.length; ++i){
			if(i%2 == 0){
				options[i].onmouseover = function(el){
					var l = document.getElementById('show-information').getElementsByTagName('div');
					if(el.target.getElementsByTagName('lable')[0]){
						for(var j = 0; j < l.length; ++j){
							if(l[j].getAttribute('class') != el.target.getElementsByTagName('lable')[0].innerHTML.toLowerCase()){
								l[j].style.display = 'none';
							} else {
								l[j].style.display = 'block';
							}
						}
					}
				}
			}
		}
    </script>
    <div class="clear"></div>
    <input type="submit" name="submit" value="submit" style="margin-top: 30px;"/>
    <script>
    	var share_info = [{
    		'title'	: 'facebook',
    		'description' : 'follow these easy steps to get autoposting up and running'
    	}];
        var shares = document.getElementsByClassName('auto-share-options')[0].getElementsByTagName('input');
        
        function write_init(info){  }
        
        hello_clients = {};
        for(a = 0; a < shares.length; ++a){
            if(shares[a].getAttribute('type') == 'checkbox'){
                shares[a].onclick = function(el){   
                    if(el.target.checked && el.target.parentNode.getElementsByTagName('input')[1].value != '' && el.target.parentNode.getElementsByTagName('input')[1].value != 'Enter Id'){
                        name = el.target.getAttribute('name');
                        name = name.replace('tw_auto_social[','').replace(']','');
                        hello_clients[name] = el.target.parentNode.getElementsByTagName('input')[1].value.toString().trim();
                        hello.init(hello_clients);
                        var tar = el.target.parentNode.getElementsByTagName('input')[2];
                        hello.login(name).then(function(e){
                        	tar.value = e.authResponse.access_token;
                        },
                        function(e){
                        	alert(e.error.message);
                        });
                    }
                }
            } else {
                if(shares[a].value == ''){
                    shares[a].value = 'Enter Id';
                    shares[a].style.color = '#efefef';
                    shares[a].onfocus = function(el){
                        if(el.target.value == 'Enter Id'){
                            el.target.value = '';
                            el.target.style.color = '#000';
                        }
                    }
                    shares[a].onblur = function(el){
                        if(el.target.value == ''){
                            el.target.value = 'Enter Id';
                            el.target.style.color = '#efefef';
                        }
                    }
                }
            }
        }
        
        var doc = document.getElementsByTagName('input');
        for(var i = 0; i < 3; ++i){
            doc[i].onclick = function(el){ checkVariables(el); }
        }
        var social = false;
        function checkVariables(vars){
            for(var i = 0; i < 3; ++i){
                if(social == false){
                    social = true;
                    break;
                }
            }
        }
    </script>
</form>