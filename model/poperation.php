<?php

class model_poperation extends basemodel
{
    /**
     * 运营总报表数据
     *
     * @author ken 2017
     * @param array $aData
     * @param $iCurrPage
     * @param $iPageRecords
     * @param $ext
     * @return mixed
     */
    public function getOperationDataList ($aData,$iLvtopid,$iUserId = 0,$ext='')
    {
        $sCondition = "ur.lvtopid = '".$iLvtopid."'";
        //时间条件
        if (isset($aData['sTime']) && $aData['sTime'] != '') {
            $sCondition .= " AND ur.`day`>='" . $aData['sTime'] . "'";
        }
        if (isset($aData['eTime']) && $aData['eTime'] != '') {
            $sCondition .= " AND ur.`day`<='" . $aData['eTime'] . "'";
        }
       // $sCondition.= " AND ur.`bets`>0 ";
        $aUserChileInfo = $this->oDB->getAll("SELECT * FROM `usertree` WHERE `userid`='" . $iUserId . "' OR `parentid`='" . $iUserId . "'");
        foreach ($aUserChileInfo as $v) {
            $iTmpUserId = intval($v['userid']);
            if ($iTmpUserId == $iUserId) {
                $aUserInfo = $v;
                $aUserLevel = explode(",", $aUserInfo["parenttree"]);
                if (empty($aUserInfo["parenttree"])) {
                    $iUserLevel = 2;
                } else {
                    $iUserLevel = count($aUserLevel) + 2;  //用户树的层级
                }
            }
            $aChildData[$iTmpUserId]["username"] = $v["username"];
            if ($v['usertype'] == 0) {
                $aChildData[$iTmpUserId]["groupname"] = "会员";
            } else {
                $aChildData[$iTmpUserId]["groupname"] = $this->getGroupName($aUserInfo['usertype'],$aUserInfo['parenttree']);
            }
        }
        $sFields = "ur.`userid`, ur.`usertype`, ur.`lvproxyid`, ur.`parentid`, ur.`username`, SUM(ur.`realpayment`) AS payment, SUM(ur.`realwithdraw`) AS withdraw,";
        $sFields .= " SUM(ur.`realbets`) AS bets, SUM(ur.`points`) AS points, SUM(ur.`bonus`) AS bonus, SUM(ur.activity) AS activity, SUM(ur.deduction) AS deduction,";
        $sFields .= " uf.availablebalance AS availablebalance, SUBSTRING_INDEX(ur.`parenttree`, ',', '".$iUserLevel."') AS `USERTREE`";
        $sTable = "`user_report` AS ur LEFT JOIN userfund AS uf ON ur.userid = uf.userid";

        $sSql="SELECT $sFields FROM $sTable WHERE $sCondition AND ( find_in_set('".$iUserId."', ur.`parenttree`) OR ur.`userid` = '".$iUserId."' ) AND ur.`istester` = 0 GROUP BY ur.`userid`";
        $aTemp = $this->oDB->getAll($sSql);
//        $aNewUser = $this->oDB->getAll("SELECT ut.userid, ut.username, ( SELECT COUNT(*) FROM users AS u LEFT JOIN usertree AS ut1 USING (userid) WHERE ( FIND_IN_SET(ut.userid, ut1.parenttree) OR ut.userid = ut1.userid ) AND u.registertime BETWEEN '".$aData['sTime']."' AND '".$aData['eTime']."' ) AS reg_num FROM usertree AS ut WHERE parentid = '".$iUserId."'");
//        foreach ($aNewUser as $v){
//            $aNewUserStr[$v['userid']] = $v;
//        }
        $aResult = array();
        $aTotal = array();
        $aProxySelf = array();
        //总计
        $aTotal["total_newuser"] = 0;
        $aTotal["total_usercount"] = 0;
        $aTotal["total_payment"] = 0;
        $aTotal["total_withdraw"] = 0;
        $aTotal["total_bets"] = 0;
        $aTotal["total_points"] = 0;
        $aTotal["total_realbets"] = 0;
        $aTotal["total_bonus"] = 0;
        $aTotal["total_profit"] = 0;
        $aTotal["total_activity"] = 0;
        $aTotal["total_availablebalance"] = 0;
        $aTotal["total_deduction"] = 0;
        foreach ($aTemp as $v) {
            $bType=true;
            $aTree = explode(",",  trim($v["USERTREE"]));
            $iTreeCount = count($aTree);
            if ($v["parentid"] == $iUserId || $v["userid"] == $iUserId) {//如果用户是总代或者以及代理
                $bType=false;
                $sStrUser = $v["userid"];
                $aResult[$sStrUser]["username"] = $v["username"];
                if ($v['usertype'] == 0) {
                    $aResult[$sStrUser]["groupname"] = "会员";
                } else {//获取用代理层级
                    $aResult[$sStrUser]["groupname"] = $this->getGroupName($v['usertype'],$v['USERTREE']);
                }
            } else {
                $sStrUser = $aTree[$iTreeCount - 1];
            }
            if (empty($aResult[$sStrUser])) {
                $aResult[$sStrUser]['username'] = $aChildData[$sStrUser]['username'];
                $aResult[$sStrUser]['groupname'] = $aChildData[$sStrUser]['groupname'];
            }
            if (isset($aResult[$sStrUser]["bets"])) {
                if($v["bets"]>0 ){
                    $aResult[$sStrUser]["usercount"] += 1;
                    $aTotal["total_usercount"] += 1;
                }
                $aResult[$sStrUser]["payment"] += $v["payment"];
                $aResult[$sStrUser]["withdraw"] += $v["withdraw"];
                $aResult[$sStrUser]["bets"] += $v["bets"];
                $aResult[$sStrUser]["points"] += $v["points"];
                $aResult[$sStrUser]["realbets"] = $aResult[$sStrUser]["bets"] - $aResult[$sStrUser]["points"];
                $aResult[$sStrUser]["bonus"] += $v["bonus"];
                $aResult[$sStrUser]["activity"] += $v["activity"];
                $aResult[$sStrUser]["availablebalance"] += $v["availablebalance"];
                $aResult[$sStrUser]["deduction"] += $v["deduction"];
                $aResult[$sStrUser]["profit"] = sprintf("%.4f",$aResult[$sStrUser]["bets"] - $aResult[$sStrUser]["points"] - $aResult[$sStrUser]["bonus"]);
                //总计
                $aTotal["total_payment"] += $v["payment"];
                $aTotal["total_withdraw"] += $v["withdraw"];
                $aTotal["total_bets"] += $v["bets"];
                $aTotal["total_points"] += $v["points"];
                $aTotal["total_bonus"] += $v["bonus"];
                $aTotal["total_activity"] += $v["activity"];
                $aTotal["total_availablebalance"] += $v["availablebalance"];
                $aTotal["total_deduction"] += $v["deduction"];
            } else {
                if($v['bets'] >0 ){
                    $aResult[$sStrUser]["usercount"] = 1;
                    $aTotal["total_usercount"] += 1;
                }else{
                    $aResult[$sStrUser]["usercount"] = 0;
                }
                $aResult[$sStrUser]["payment"] = $v["payment"];
                $aResult[$sStrUser]["withdraw"] = $v["withdraw"];
                $aResult[$sStrUser]["bets"] = $v["bets"];
                $aResult[$sStrUser]["points"] = $v["points"];
                $aResult[$sStrUser]["realbets"] = $aResult[$sStrUser]["bets"] - $aResult[$sStrUser]["points"];
                $aResult[$sStrUser]["bonus"] = $v["bonus"];
                $aResult[$sStrUser]["activity"] = $v["activity"];
                $aResult[$sStrUser]["availablebalance"] = $v["availablebalance"];
                $aResult[$sStrUser]["deduction"] = $v["deduction"];
                $aResult[$sStrUser]["profit"] = sprintf("%.4f",$v["bets"] - $v["points"] - $v["bonus"]);
                //总计\
                $aTotal["total_payment"] += $v["payment"];
                $aTotal["total_withdraw"] += $v["withdraw"];
                $aTotal["total_bets"] += $v["bets"];
                $aTotal["total_points"] += $v["points"];
                $aTotal["total_activity"] += $v["activity"];
                $aTotal["total_availablebalance"] += $v["availablebalance"];
                $aTotal["total_deduction"] += $v["deduction"];
                $aTotal["total_bonus"] += $v["bonus"];
            }
        }
        $aTotal["total_realbets"] = $aTotal['total_bets'] - $aTotal['total_points'];
        $aTotal["total_profit"] = $aTotal['total_bets'] - $aTotal['total_points'] - $aTotal['total_bonus'];
        if ($iUserId > 0 && !isset($aResult[$iUserId]) && $aUserInfo['parentid'] != 0) {//如果当前用户没有查询到相关报表数据
            $aTmp = array();
            //当前用户信息
            $aTmp[$iUserId]["username"] = $aUserInfo['username'];
            if ($aUserInfo['usertype'] == 0) {
                $aTmp[$iUserId]["groupname"] = "会员";
            } else {
                $aTmp[$iUserId]["groupname"] = $this->getGroupName($aUserInfo['usertype'],$aUserInfo['parenttree']);
            }
            $aTmp[$iUserId]["payment"] = 0;
            $aTmp[$iUserId]["usercount"] = 0;
            $aTmp[$iUserId]["withdraw"] = 0;
            $aTmp[$iUserId]["bets"] = 0;
            $aTmp[$iUserId]["points"] = 0;
            $aTmp[$iUserId]["realbets"] = 0;
            $aTmp[$iUserId]["bonus"] = 0;
            $aTmp[$iUserId]["deduction"] = 0;
            $aTmp[$iUserId]["activity"] = 0;
            $aTmp[$iUserId]["availablebalance"] = 0;
            $aTmp[$iUserId]["profit"] = 0;
            $aResult = $aTmp + $aResult;
        }
        if ($iUserId > 0 && $aUserInfo['parentid'] != 0) {//代理自身数据
            $aProxySelf = $aResult[$iUserId];
        }
        unset($aResult[$iUserId]);
//        foreach ($aResult as $k =>$v) {
//            $aResult[$k]['newuser'] = $aNewUserStr[$k]['reg_num'];
//            $aTotal['total_newuser'] += $aNewUserStr[$k]['reg_num'];
//        }
        $aAllResult = array();
        if (!empty($aResult) || !empty($aProxySelf)) {//没有相关数据
            $aAllResult = array_values($aResult);
            if ($iUserId != $iLvtopid) {
                array_unshift($aAllResult,$aProxySelf);
            }
            $aAllResult[0]['total'] = $aTotal;
        }
        if ($ext = 'ext') {
            return $aAllResult;
        }
        return $aAllResult;
    }
    
    /**
     * 查看是否有直接下级
     *
     * @author ken 2017
     * @param $aData
     * @return array
     */
    public function getDirectUsers($aData)
    {
        $oUserModel = new model_puser();
        $userid = $oUserModel->getIdByUsername($aData['lvtopid'], $aData['searchname']);
        if (empty($userid) || !is_array($userid)) {
            return FALSE;
        }
        return $this->oDB->getOne(" SELECT COUNT(userid) AS usernum FROM `usertree` WHERE lvtopid = '".$aData['lvtopid']."' AND `parentid` = ".$userid['userid']);
    }
    /**
     * 根据用户信息获取身份名称
     * @author pierce
     * @param $iUserType
     * @date 2017-09-01
     * @param $sUserTree
     * @return string
     */
    public function getGroupName($iUserType,$sUserTree){
        $sGroupName = "";
        $aLevel = ['一', '二', '三','四','五','六','七','八','九','十'];
        if ($iUserType == 0) {
            $sGroupName = "会员";
        } else {
            $sGroupName = $aLevel[substr_count($sUserTree,',')] . '级代理';
        }
        return $sGroupName;
    }
    /**
     * pierce
     * 获取平台有史以来总数据
     * @param $iLvtopid
     * @return array
     */
    public function getTotalList ($iLvtopid)
    {
        $aReturn = $this->oDB->getOne("SELECT SUM(ur.payment) AS total_payment, SUM(ur.withdraw) AS total_withdraw, SUM(ur.activity) AS total_activity, SUM(ur.points) AS total_points, SUM(uf.availablebalance) AS total_availablebalance, SUM(realbets) AS total_realbets, SUM(ur.bonus) AS total_bonus, SUM(ur.deduction) AS total_deduction, SUM(ur.payment - ur.withdraw) AS total_inout_difference FROM user_report AS ur LEFT JOIN userfund AS uf USING (userid) WHERE lvtopid = '".$iLvtopid."'");
        return $aReturn;
    }
}