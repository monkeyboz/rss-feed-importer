<?php
function curl_load($url){
curl_setopt($ch=curl_init(), CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);
return $response;
}
echo curl_load(urldecode($_GET['url']));
?>