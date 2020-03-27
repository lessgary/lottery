<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/7/6
 * Time: 16:27
 */
class model_payset extends basemodel{
    /**
     * @desc 添加支付设定
     * @author pierce
     * @date 2017-07-06
     * @return bool
     */
    public function addPaySet($aData,$iLvtopid){
        $aPetSet = $this->oDB->getAll("SELECT * FROM proxy_pay_set WHERE title = '".$aData['title']."' AND lvtopid = '".$iLvtopid."'");
        if (!empty($aPetSet)){
            return "支付设定名称不可以重复";
        }
        $a = $this->oDB->insert('proxy_pay_set', $aData);
        if($this->oDB->ar() < 1) {
            return "添加失败";
        }
        return true;
    }

    /**
     * @desc 支付设定列表(查询列表或者查询单条)
     * @author pierce
     * @date 2017-07-06
     * @param $lvtopid
     * @param int $id
     * @return array|int
     */
    public function paySetList($lvtopid,$id=0){
        $lvtopid = intval($lvtopid);
        $sWhere = "lvtopid = {$lvtopid}";

        if (isset($id) && $id != 0){
            $id = intval($id);
            $sWhere .= " AND id = {$id}";
            $aArr = $this->oDB->getOne("SELECT * FROM `proxy_pay_set` WHERE " . $sWhere . " GROUP BY id");
        }else{
            $aArr = $this->oDB->getAll("SELECT * FROM `proxy_pay_set` WHERE " . $sWhere . " GROUP BY id");
        }
        if (0 == $this->oDB->ar()) {
            return [];
        } else {
            return $aArr;
        }
    }

    /**
     * @desc 修改支付设定
     * @author pierce
     * @date  2017-07-07
     * @param $aParams
     * @param $id
     * @return bool|int
     */
    public function editPaySet($aParams,$id){
        $sWhere = " `id` = '" . intval($id) . "'";
        return $this->oDB->update('proxy_pay_set', $aParams, $sWhere);
    }
    /**
     * @desc 删除支付设定
     * @author pierce
     * @date 2017-07-06
     * @param $id
     * @return bool|mysqli_result|null
     */
    public function delPaySet($iLvtopid,$id){
        $aPetSet = $this->paySetList($iLvtopid,$id);
        if(isset($aPetSet['id']) && $aPetSet['id'] != 0){
            $aLayer = $this->oDB->getOne("SELECT * FROM user_layer WHERE p_paysetid = '".$aPetSet['id']."' AND lvtopid = '".$iLvtopid."'");
            if (!empty($aLayer)){
                return false;
            }
        }
        return $this->oDB->query("DELETE FROM `proxy_pay_set` WHERE `id`='" . $id . "'");
    }

    /**
     * 根据充值金额获取优惠
     * @author pierce
     * @date 2017-07-10
     * @param $lvtopid
     * @param $userId
     * @param $isFirst
     * @param $money
     * @return bool
     */
    public function getFreeById($lvtopid,$userId,$isFirst,$money){
        $oPuser = new model_puser();
        $oUserLayer = new model_userlayer();
        $aUserInfo = $oPuser->getUserInfo($userId);
        if (empty($aUserInfo)) {
            return false;//用户信息查不到
        }
        $aPaySetId = $oUserLayer->getPaySetIdByLayerId($aUserInfo['layerid'],$lvtopid);
        if (empty($aPaySetId)){
            return false;//用户层级查不到
        }
        $aPaySetInfo = $this->paySetList($lvtopid,$aPaySetId['p_paysetid']);
        if (empty($aPaySetInfo)){
            return false;//查不到用户支付设定信息
        }
        //优惠金额
        if ($aPaySetInfo['deposit_favortime'] == 0 || ($aPaySetInfo['deposit_favortime'] == 1 && $isFirst == 2)){
            $aFree['free'] = 0;//优惠次数不满足
        }else{
            if ($aPaySetInfo['deposit_favorbase'] > $money){
                $aFree['free'] = 0;//充值金额小于优惠标准
            }else{
                if ($aPaySetInfo['deposit_favormax'] == 0){
                    $aFree['free'] = 0;//优惠金额为0
                }else{
                    $iFree =$money * ($aPaySetInfo['deposit_favorrate']/100);
                    if ($iFree <= $aPaySetInfo['deposit_favormax']){
                        $aFree['free'] = $iFree;
                    }else{
                        $aFree['free'] = $aPaySetInfo['deposit_favormax'];
                    }
                }
            }
        }
        //常态打码量
        if ($aPaySetInfo['deposit_isbets'] == 0){
            $aFree['recover'] = 0;
        }else{
            $aFree['recover'] = $money * ($aPaySetInfo['deposit_betsrate']/100);
        }
        //放款额度
        $aFree['reduce'] = $aFree['recover'] * ($aPaySetInfo['deposit_betsreducerate']/100);
        //综合打码
        if ($aPaySetInfo['deposit_isextbets'] == 0){
            $aFree['comprehensive'] = 0;
        }else{
            $aFree['comprehensive'] = ($money+$aFree['free']) * $aPaySetInfo['deposit_extbets'];
        }
        return $aFree;
    }

    /**
     * 根据充值金额获取优惠
     * @author pierce
     * @date 2017-07-10
     * @param $lvtopid
     * @param $userId
     * @param $isFirst
     * @param $money
     * @return string|array
     */
    public function getOnlineFreeById($lvtopid,$userId,$isFirst,$money){
        $oPuser = new model_puser();
        $oUserLayer = new model_userlayer();
        $aUserInfo = $oPuser->getUserInfo($userId);
        if (empty($aUserInfo)) {
            return "用户信息查不到";//用户信息查不到
        }
        $aPaySetId = $oUserLayer->getPaySetIdByLayerId($aUserInfo['layerid'],$lvtopid);
        if (empty($aPaySetId)){
            return "用户层级查不到或者此用户层级没有对应支付设定";//用户层级查不到
        }
        $aPaySetInfo = $this->paySetList($lvtopid,$aPaySetId['p_paysetid']);
        if (empty($aPaySetInfo)){
            return "查不到用户支付设定信息";//查不到用户支付设定信息
        }
        //优惠金额
        if ($aPaySetInfo['fastpay_favortime'] == 0 || ($aPaySetInfo['fastpay_favortime'] == 1 && $isFirst == 2)){
            $aFree['free'] = 0;//优惠次数不满足
        }else{
            if ($aPaySetInfo['fastpay_favorbase'] > $money){
                $aFree['free'] = 0;//充值金额小于优惠标准
            }else{
                if ($aPaySetInfo['fastpay_favormax'] == 0){
                    $aFree['free'] = 0;//优惠金额为0
                }else{
                    $iFree =$money * ($aPaySetInfo['fastpay_favorrate']/100);
                    if ($iFree <= $aPaySetInfo['fastpay_favormax']){
                        $aFree['free'] = $iFree;
                    }else{
                        $aFree['free'] = $aPaySetInfo['fastpay_favormax'];
                    }
                }
            }
        }
        //常态打码量
        if ($aPaySetInfo['fastpay_isbets'] == 0){
            $aFree['recover'] = 0;
        }else{
            $aFree['recover'] = $money * ($aPaySetInfo['fastpay_betsrate']/100);
        }
        //放宽额度
        $aFree['reduce'] = $aFree['recover'] * ($aPaySetInfo['fastpay_betsreducerate']/100);
        //综合打码
        if ($aPaySetInfo['fastpay_isextbets'] == 0){
            $aFree['comprehensive'] = 0;
        }else{
            $aFree['comprehensive'] = ($money+$aFree['free']) * $aPaySetInfo['fastpay_extbets'];
        }
        return $aFree;
    }

    /**
     * 根据出款金额获取用户手续费
     * @author pierce
     * @date 2017-07-13
     * @param $lvtopid
     * @param $userid
     * @param $money
     * @return bool|float|int|mixed
     */
    public function getFeeById($lvtopid,$userid,$money,$iAdmin_fee){
        $oPuser = new model_puser();
        $oUserLayer = new model_userlayer();
        $oWithdraw = new model_withdraw();
        $aUserInfo = $oPuser->getUserInfo($userid);
        if (empty($aUserInfo)) {
            return false;//用户信息查不到
        }
        $aPaySetId = $oUserLayer->getPaySetIdByLayerId($aUserInfo['layerid'],$lvtopid);
        if ($aPaySetId['p_paysetid'] == 0){
            return false;//用户层级查不到
        }
        $aPaySetInfo = $this->paySetList($lvtopid,$aPaySetId['p_paysetid']);
        if (empty($aPaySetInfo)){
            return false;//查不到用户支付设定信息
        }
        $iWithdrawTimes = $oWithdraw->getWithdrawTimes($userid,$lvtopid);
        if ($aPaySetInfo['withdraw_freetimes'] > $iWithdrawTimes  && ($aPaySetInfo['withdraw_isallowfree'] == 1 && $iAdmin_fee <= 0 )){
            $iFee = 0;
        }else{
            if ($aPaySetInfo['withdraw_chargetype'] == 1){
                $iFee = $aPaySetInfo['withdraw_charge'];
            }else{
                if ((($aPaySetInfo['withdraw_charge']/100) * $money) > $aPaySetInfo['withdraw_chargemax']){
                    $iFee = $aPaySetInfo['withdraw_chargemax'];
                }else{
                    $iFee = ($aPaySetInfo['withdraw_charge']/100) * $money;
                }
            }
        }
        return $iFee;
    } 
    /**
     * desc 获取行政费百分比
     * @author rhovin 2017-07-24
     * @return fastpay_betschargerate/100
     */
    public function getBetsChargeRate($iUserId , $lvtopid) {
        $oPuser = new model_puser();
        $oUserLayer = new model_userlayer();
        $aUserInfo = $oPuser->getUserInfo($iUserId);
        $aPaySetId = $oUserLayer->getPaySetIdByLayerId($aUserInfo['layerid'],$lvtopid);
        if(!isset($aPaySetId['p_paysetid'])) {
            return 'NaN';
        } 
        $aPaySetInfo = $this->paySetList($lvtopid,intval($aPaySetId['p_paysetid']));
        if (empty($aPaySetInfo)) {
            return 'NaN';//查不到用户支付设定信息
        }
        return bcdiv($aPaySetInfo['fastpay_betschargerate'],100,2);
    }
    /**
     * desc 根据用户ID和总代ID查找payset
     * @author rhovin 2017-07-24
     * @return []
     */
    public function findOnePaySet($iUserId , $lvtopid) {
        $oPuser = new model_puser();
        $oUserLayer = new model_userlayer();
        $aUserInfo = $oPuser->getUserInfo($iUserId);
        $aPaySetId = $oUserLayer->getPaySetIdByLayerId($aUserInfo['layerid'],$lvtopid);
        if(!isset($aPaySetId['p_paysetid'])) {
            return [];
        } 
        $aPaySetInfo = $this->paySetList($lvtopid,intval($aPaySetId['p_paysetid']));
        if (empty($aPaySetInfo)) {
            return [];//查不到用户支付设定信息
        }else{
            return $aPaySetInfo;
        }
        
    }
    /**
     * desc 查找总代默认的支付设定
     * @param lvtopid
     */
    public function findDefaultPaySet($lvtopid) {
        $sSql = "SELECT `id` FROM proxy_pay_set WHERE lvtopid='${lvtopid}' AND `isdefault`=1";
        return $this->oDB->getOne($sSql);
    }
}