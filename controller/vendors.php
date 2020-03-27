<?php
include_once PRJ_MODEL_PATH.'vendor'.DS."proxyvendorgame.php";

/**
 * Created by PhpStorm.
 * User: mark
 * Date: 2018/6/12
 * Time: 15:44
 */
class controller_vendors extends basecontroller{
    function  actiongetbalance(){
        if(!array_key_exists("from",$_GET) || !array_key_exists("userid",$_GET)){
            die(json_encode(array("code"=>-1,"msg"=>"参数有误")));
        }
        if($_GET["userid"] < 0 || !is_numeric($_GET["userid"])){
            die(json_encode(array("code"=>-1,"msg"=>"会员有误")));
        }
        $arr=array("from"=>daddslashes($_GET["from"]),"userid"=>$_GET["userid"]);
        $getbalance = new proxyvendorgame();
        $getbalance->getuser($arr);
        die(json_encode($getbalance->getbalance()));

    }
    function  actionretry(){
        if(!array_key_exists("torder",$_GET) || !array_key_exists("userid",$_GET)){
            die(json_encode(array("code"=>-1,"msg"=>"参数有误")));
        }
        if($_GET["userid"] < 0 || !is_numeric($_GET["userid"])){
            die(json_encode(array("code"=>-1,"msg"=>"会员有误")));
        }
        $arr=array("torder"=>daddslashes($_GET["torder"]),"userid"=>$_GET["userid"]);
        $getbalance = new proxyvendorgame();
        $result = $getbalance->retry($arr);
        die($result);

    }
}