<?php
    $url = parse_url(site_url());
    $url = str_replace($url['scheme'].'://','',site_url());
    $url = str_replace('/','_',str_replace('.','^',$url));
    
    if(isset($_POST['tw_advertising']) && $_POST['tw_advertising'] != ''){
        update_option('tw_advertising','true');
    } elseif($_POST['submit'] == 'submit') {
    	update_option('tw_advertising','');
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
    
    $share = explode(',',get_option('tw_social'));
    $d = array();
    
    foreach($share as $f){
        $d[$f] = 'on';
    }
    $share = $d;
    $d = null;
?>
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
    .clear{
        clear: both;
    }
</style>
<h1>Monitization/Sharing Options</h1>
<form action="" method="POST">
    <div class="share">
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
    <div class="share">
        <label>Advertisement Share (<a href="http://quanticpost.com/advertisingshare/<?php echo $url; ?>">read more</a>)</label><input type="checkbox" name="tw_advertising" <?php if(get_option('tw_advertising') != ''){ echo 'checked'; } ?>/>
    </div>
    <div class="share">
        Auto Social Posting (coming soon <a href="http://quanticpost.com/purchase">Donate!</a>)
    </div>
    <div class="clear"></div>
    <input type="submit" name="submit" value="submit" style="margin-top: 30px;"/>
    <script>
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