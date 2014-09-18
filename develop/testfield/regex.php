<?php
$string = "'current_rsa_timestamp' => 1410948202,";

$regexp='/current_rsa_timestamp.{1}\s*=>\s*([0-9]+)/';
$temp = array();
preg_match($regexp, $string, $temp);
$cur_ts = ($temp[1]);

echo "<br />cur_ts is: ".$cur_ts;
if (is_int($cur_ts)){
    echo "<br />Is an int!";
}




?>