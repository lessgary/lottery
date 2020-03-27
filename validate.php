<?php
//生成一个验证码，并把验证码信息存到session里面
$validate = new validatecode();
$validate->setImage(array('width' => 105, 'height' => 30));
$validate->setCode(array('characters' => '0-9', 'length' => 4, 'deflect' => TRUE, 'multicolor' => FALSE));
$validate->setFont(array("space" => 3, "size" => 16, "left" => 9, "top" => 24, "file" => ''));
$validate->setMolestation(array("type" => "both", "density" => 'fewness'));
$validate->setBgColor(array('r' => 255, 'g' => 255, 'b' => 255));
$validate->setFgColor(array('r' => 20, 'g' => 20, 'b' => 20));

/*
 * 将验证码信息保存到session
 */
// 输出到浏览器 
$validate->paint();
$_SESSION['validateCode'] = $validate->getcode();
sysEcho($_SESSION['validateCode']);
?>