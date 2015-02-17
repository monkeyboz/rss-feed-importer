<?php
    class Social{
        var $info;
        
        public function __construct($tokens,$api){
            $this->info['tokens'] = json_decode($tokens,true);
            $this->info['api'] = json_decode($api,true);
            
            foreach($this->info['api'] as $k=>$a){
                foreach($a as $b=>$g){
                    $this->info['api'][$b] = $g;
                    $this->info['tokens'][$b] = $this->info['tokens'][$k][$b];
                }
                unset($this->info['api'][$k]);
                unset($this->info['tokens'][$k]);
            }
        }
        
        public function curl_load($url,$post=false,$post_fields=''){
            curl_setopt($ch=curl_init(), CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            if($post){
            	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            	curl_setopt($ch, CURLOPT_POST, true);
            }
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        }
        
        public function facebook_post(){
            if(isset($this->info['api']['facebook']) && isset($this->info['tokens']['facebook'])){
                $content = $this->curl_load('https://graph.facebook.com/v2.2/me/?access_token='.$this->info['tokens']['facebook']);
                //print '<pre>'.print_r(json_decode($content),true).'</pre>';
                return true;
            } else {
                return false;
            }
        }
        
        public function twitter_post(){
            if(isset($this->info['api']['twitter']) && isset($this->info['tokens']['twitter'])){
            	//echo $this->curl_load('https://api.twitter.com/1.1/statuses/home_timeline.json&access_token='.$this->info['tokens']['twitter']);
                //print '<pre>'.print_r(json_decode($this->curl_load('https://graph.facebook.com/v2.2/me/?access_token='.$this->info['tokens']['twitter'])),true).'</pre>';
                return true;
            } else {
                return false;
            }
        }
        
        public function linkedin_post(){
            if(isset($this->info['api']['twitter']) && isset($this->info['tokens']['twitter'])){
                //print '<pre>'.print_r(json_decode($this->curl_load('https://graph.facebook.com/v2.2/me/?access_token='.$this->info['tokens']['twitter'])),true).'</pre>';
                return true;
            } else {
                return false;
            }
        }
    }
?>