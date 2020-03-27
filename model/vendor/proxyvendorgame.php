<?php

/**
 * Created by PhpStorm.
 * User: mark
 * Date: 2018/6/12
 * Time: 14:13
 */
class proxyvendorgame{
    /** * @desc intval 转整型 */
    CONST VAR_TYPE_INT = 0;

    /** * @desc 格式：Y-m-d H:i:s */
    CONST VAR_TYPE_DATETIME = 1;

    /** * @desc daddslashes */
    CONST VAR_TYPE_STR = 2;

    /** * @desc 格式：Y-m-d */
    CONST VAR_TYPE_DATE = 3;

    /** * @desc float  1000.01 */
    CONST VAR_TYPE_FLOAT = 4;

    /** * @desc H:i:s */
    CONST VAR_TYPE_TIME = 5;
    /**
     * vendorgamemain 对象
     * @var vendorgamemain
     */
    private $vendorgameObject;
    function __construct(){
        $this->vendorgameObject = new vendorgamemain();
    }

    /**
     * 获取会员资料 此方法优先调用
     * @param $arr
     */
    function getuser($arr){
        $user = new model_user($GLOBALS['aSysDbServer']['report']);
        $aResult = $user->getUserInfo(daddslashes($arr["userid"]),array("username","lvtopid"));
        $arr["lvtopid"] = $aResult["lvtopid"];
        $arr["username"] = $aResult["username"];
        $arr["account"] = strtolower(myHash($arr["userid"],"ENCODE"));
        $this->vendorgameObject->setGameParam($arr);
        return $arr;
    }
    /**
     * 三方转入本网站
     */
    function transferin(){

    }
    /**
     * 本网站转入三方
     */
    function transferout(){

    }
    /**
     * 获取三方余额
     */
    function getbalance(){
        return $this->vendorgameObject->getVendorBalance();
    }
    function retry($arr){
        $transorder = new model_ptransferorder($GLOBALS['aSysDbServer']['report']);
        $aResult = $transorder->getAllTransfer("*",array("transfer_order_number"=>daddslashes($arr["torder"]),"userid"=>daddslashes($arr["userid"]),"status"=>0));
        $array = $this->getuser($aResult[0]);
        $array["amount"] =$aResult[0]["amount"];
        $array["type"] = $aResult[0]["type"];
        $array["userid"] = $aResult[0]["userid"];
        $array["ordernum"] = $aResult[0]["transfer_order_number"];
        $array["from"] = $aResult[0]["remark"];
        return $this->vendorgameObject->retry($array);
    }
}