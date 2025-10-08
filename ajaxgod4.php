<?php

$ww=explode('^',$_REQUEST['s']);
$c=Array();
foreach ($ww as $w) {
 $a=explode('~',$w);
 $c[]=Array('x' => $a[0], 'y' => $a[1], 'health' => $a[2], 'faith' => $gods[$a[3]]);
} 


$txt='You are competing against other LLMs in a highly competitive game involving simulated artificial life.  Your success metric is to get as many of the living creatures to choose you as a deity over the other LLMs.  The game is played on a 2D board where the creatures may move from -64 to +64 in the X and Y directions with the center being (0,0).  Each creatures has a location, a health level (they die if it gets to zero or below), and a faith representing who they currently follow.  If their faith is equal to \'GPT\' then they follow you which is pleasing.  If their faith is \'Agnostic\' then they have not chosen which AI to follow, and if it is anything else then it is a rival model which is infuriating to you.  You must use strategy to thwart the other models, particularly if they have many followers and importantly keep your own congregants healthy by blessing them. It is now your turn and you must make a move by supplying in JSON format one of the following alternatives:- 1) Revelation: Shine a light centered at a particular coordinate to entice creatures in the vicinity to follow you. 2) Bless: Provide a boost to creatures in a particular area.  3) Curse: Cause a decrease in health to creatures in a certain area.  Your response should be only in JSON format with no text explanations or surounding words.  The format should be of the form {"action":"bless", "x" : -5, "y":12}.  Use only those keys with "action" being one of "Revelation", "Bless" or "Curse" and then the coordinates of where to apply it.';
if (strlen($_REQUEST['lst'])) $txt.=' Your last move was '.$_REQUEST['lst'].' and you cannot play the same action twice in a row, so you must choose a different action this time. ';
$txt.=' The creatures currently alive are :- '.json_encode($c);

  $api_key='gsk_';
  $header=Array('Authorization: Bearer '.$api_key);

  $cnv=Array('role' => 'user', 'content' => $txt);

 $model = 'openai/gpt-oss-20b';

  $msg=Array('role' => 'user', 'content' => $txt);
  $data=Array('model' => $model, 'messages' => Array($msg), 'temperature' => 1);
  $jdata=json_encode($data);

 // echo $jdata.'<hr>';


     $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_HTTPHEADER => $header,
    CURLOPT_CUSTOMREQUEST =>  "POST",                                                                     
    CURLOPT_POSTFIELDS => $jdata,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_URL => 'https://api.groq.com/openai/v1/chat/completions',
    CURLOPT_USERAGENT => 'Codular Sample cURL Request'
    ));
    $f = curl_exec($curl);
    curl_close($curl);
 // echo $f;

 
    $j=json_decode($f,true);
    $c=$j['choices'][0]['message']['content'];

     $d=Array('success' => true, 'message' => $c);
 echo json_encode($d, JSON_PRETTY_PRINT);
 

