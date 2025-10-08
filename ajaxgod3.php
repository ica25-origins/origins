<?php

 $actions=Array('Revelation','Bless','Curse');

 $x=mt_rand(0,80)-40;
 $y=mt_rand(0,80)-40;
 $a=$actions[mt_rand(0,2)];

 $d=Array('success' => true, 'message' => json_encode(Array('action' => $a, 'x' => $x, 'y' => $y)));

 echo json_encode($d, JSON_PRETTY_PRINT);
