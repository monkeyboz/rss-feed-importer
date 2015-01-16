<?php
class Layout{
    var $query;
    var $layout;
    var $info;
    var $layout_dir = 'layout/';
    var $json = '';
    var $xml = '';
    var $post_type = 'post';
    
    //constructor for the layout function, used for various information gathering and setting up
    //numerous other information used for various other html layout compositions
    public function __construct($args){
        extract($args);
        $queryString = "post_type=".$post;
        if(isset($num)){
            $queryString .= '&post_per_page='.$num;
        }
        $this->info = new WP_Query($queryString);
        $this->post_type = $post;
    }
    //get public function that allows for information to be passed through to various other modules when needed
    //there are numerous options in this function to allow returning information to numerous modules and other
    //string types which include xml as well as json
    public function getInfo(){
        $this->info = $this->query->posts;
    }
    //used to return xml information
  	public function getJSON(){
  		return json_encode($this->info->posts);
  	}
    //used to return json information
  	public function getXML(){
  	    $json = $this->getJSON();
  	    $json = json_decode($json,true);
        $this->createXML($json);
  	}
  	public function createXML($json,$string=""){
  	    if(is_array($json)){
  	        $string .= '<post>';
      	    foreach($json as $v=>$c){
                if(!is_array($c)){
                  $string .= '<'.$v.'>'.$c.'</'.$v.'>';
                } else {
                  $string .= '<'.$v.'>'.$this->createXML($c,$string).'</'.$v.'>';
                }
      	    }
      	    $string .= '</post>';
  	    }
  	    return $string;
  	}
    //search function used for various searching queries used for various populating information with
    //the WP_Query Object
    public function search($query){
        $queryString = '';
        foreach($query as $k=>$q){
            $queryString .= $k.'='.$q.'&';
        }
        $queryString = substr($queryString,0,-1);
        $this->query = new WP_Query($queryString);
        $this->info = $this->query->posts;
    }
    //get layout is used to create a layout that will allow the population of the template file
    //if there are issues with the template, it will be fixed through the populate_layout function
    public function get_layout($layout){
        $this->layout = file_get_contents($layout);
    }
    
    public function get_css(){
        return '<link href="'.plugins_url('css/'.$this->post_type.'/style.css',__FILE__).'" rel="stylesheet" type="text/css" media="all" />';
    }
    
    public function get_js(){
        return '<script src="'.plugins_url('js/'.$this->post_type.'/isotope.js',__FILE__).'"></script>';
    }
    //populates the template to make sure that there are variables need to complete the layout
    //this will allow for various layouts to be created dynamically if needed.
    /*the options being passed are 
   		$template which holds the template string
    	$info which holds the info to be pulled into the template
    */
    //if there is information in the template but not in the information string, then there will be an ommiting of that information
    //until it is otherwise validated through the plugin
    public function populate_layout($info,$options=array(),$advertisements){
        extract($options);
        
	    $advert = file_get_contents(plugin_dir_path( __FILE__ ).'/layout/advertisements.php');
        
        $templateHolder = "";
        $template = $this->layout;
        if($this->layout == ''){
        	foreach($info as $k=>$i){
        		$templateHolder .= '<div class="'.$k.'">'.$i.'</div>';
        	}
        } else {
        	foreach($info as $k=>$p){
        	    $template_double = $template;
        	    preg_match_all("/\[(\w+(\:\d+)?)\]/is", $template_double, $str_result);
        	    foreach($str_result[0] as $s){
        	        preg_match("/(\w+)(\:(\d+))?/is", $s, $strHolder);
            	    if(sizeof($strHolder) < 4){
            	        if($strHolder[0] == 'link'){
            	            if(strlen(strip_tags($p->post_content)) < 100){
            	                preg_match_all("/Read More:(.*)<a href='(.*)'>(.*)<\/a>/is", $p->post_content, $pa);
            	                $template_double = str_replace('['.$strHolder[0].']', str_replace(' ','',$pa[2][0]), $template_double);
            	            } else {
            	                $template_double = str_replace('['.$strHolder[0].']', get_permalink($p->ID), $template_double);
            	            }
            	        } elseif($strHolder[0] == 'images'){
            	            $images = wp_get_attachment_url(get_post_thumbnail_id($p->ID));
            	            if($images != ''){
            	                $images = '<a href="[link]"><div style="height: 150px; overflow: hidden; margin-bottom: 10px; border-bottom: 5px solid #ababab;"><img src="'.$images.'" style="width: 100%;"/></div></a>';
            	            } else {
            	                $images = '';
            	            }
            	            $template_double = str_replace('['.$strHolder[0].']', $images,$template_double);
            	        } else {
            		        $template_double = str_replace('['.$strHolder[0].']', $p->{$strHolder[0]}, $template_double);
            	        }
            	    } else {
            	        if(!isset($title_only) && sizeof($title_only) < 1){
            	            $template_double = str_replace('['.$strHolder[0].']', substr(strip_tags($p->{$strHolder[1]}),0,$strHolder[3]).'...', $template_double);
            	        } else {
            	            $template_double = str_replace('['.$strHolder[0].']', '', $template_double);
            	        }
            	    }
        	    }
        	    $templateHolder .= $template_double;
        	}
        }
        return $templateHolder;
    }
}
?>