<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/7/8
 * Time: 14:44
 */
class model_withdraw extends basemodel{
    /**
     * 获取出款管理列表
     * @author pierce
     * @date 2017-07-08
     * @param string $sCondition
     * @param int $iPageRecord
     * @param int $iCurrentPage
     * @return array|mixed
     */
    public function getWithdrawList($sCondition = '',$iPageRecord = 0,$iCurrentPage = 0){
        $sFields = "uw.*, ul.`name`,u.`realname`,u.`withdrawaltimes`,uf.availablebalance";
        $sTableName = "user_withdraw AS uw LEFT JOIN user_layer AS ul ON (uw.layerid = ul.layerid AND uw.lvtopid = ul.lvtopid) LEFT JOIN users AS u ON(uw.userid = u.userid AND uw.lvtopid = u.lvtopid) LEFT JOIN userfund AS uf ON(uw.userid = uf.userid) LEFT JOIN `usertree` ut ON uw.`userid`=ut.`userid`";
        $sOrderBy = "ORDER BY uw.applytime DESC";
        if ($iPageRecord == 0) {//不分页显示
            $sCondition = empty($sCondition) ? "" : $sCondition;
            $sSql = "SELECT $sFields FROM $sTableName  WHERE  $sCondition $sOrderBy";
            $aRequest['results'] = $this->oDB->getAll($sSql);
        } else {
            $sCondition = empty($sCondition) ? "1" : $sCondition;
            $aRequest = $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecord, $iCurrentPage, $sOrderBy, '');
        }
        if (!empty($aRequest['results'])) {
            foreach ($aRequest['results'] as &$v) {
                if ($v['finishtime'] == "0000-00-00 00:00:00") {
                    $v['finishtime'] = "";
                }
            }
        }
        $aRequest['total'] = $this->oDB->getOne("SELECT  count(`uw`.`id`) as count,sum(`uw`.`apply_amount`) as sumApply FROM ${sTableName} WHERE ${sCondition}");
        $aRequest['total']['sumReal'] = $this->oDB->getOne("SELECT sum(`uw`.`real_amount`) as sumReal FROM ${sTableName} WHERE ${sCondition} AND uw.`status` = 2")['sumReal'];
        return $aRequest;
    }

    /**
     * 根据id获取出款信息
     * @author pierce
     * @date 2017-07-14
     * @param $iId
     * @param $iLvtopid
     * @return array
     */
    public function getWithdrawById($iId,$iLvtopid){
        return $this->oDB->getOne("SELECT * FROM user_withdraw WHERE id={$iId} and lvtopid = {$iLvtopid}");
    }

    /**
     * 根据用户获取有效投注金额
     * @author pierce
     * @date 2017-07-11
     * @param $iUserid
     * @param $iLvtopid
     * @return array
     */
    public function getRealBets($iUserid,$iLvtopid){
        //withdrawtime 为实际出款时间
        $aFinishtime = $this->oDB->getOne("SELECT MAX(finishtime) AS finishtime FROM user_withdraw WHERE `status` = 2 AND userid = {$iUserid} AND lvtopid = {$iLvtopid}");
        if ($aFinishtime['finishtime'] == ""){
            $aFinishtime['finishtime'] = 0;
        }
        $aSetreadytime = $this->oDB->getOne("SELECT MAX(updatetime) AS setreadytime FROM user_withdraw WHERE `status` = 1 AND userid = {$iUserid} AND lvtopid = {$iLvtopid}");
        if ($aSetreadytime['setreadytime'] != "" && strtotime($aSetreadytime['setreadytime'])>strtotime($aFinishtime['finishtime'])){
            $aInsertTime['finishtime']=$aSetreadytime['setreadytime'];
        }else{
            $aInsertTime['finishtime']=$aFinishtime['finishtime'];
        }
        $aFastpayTime = $this->oDB->getOne("SELECT MIN(finishtime) AS finishtime FROM user_deposit_fastpay WHERE `status` IN (2,4) AND userid = {$iUserid} AND lvtopid = {$iLvtopid} AND finishtime >= '".$aInsertTime['finishtime']."'");
        $aCompanyTime = $this->oDB->getOne("SELECT MIN(finishtime) AS finishtime FROM user_deposit_company WHERE `status` = 2 AND userid = {$iUserid} AND lvtopid = {$iLvtopid} AND finishtime >= '".$aInsertTime['finishtime']."'");
        $aManualpayTime = $this->oDB->getOne("SELECT MIN(finishtime) AS finishtime FROM manualpay_confirm WHERE optype = 0 AND order_type IN (0,1,3) AND isconfirm = 1 AND user_ids = {$iUserid} AND lvtopid = {$iLvtopid} AND finishtime >= '".$aInsertTime['finishtime']."'");
        $aTime = array();
        if (!empty($aFastpayTime['finishtime'])){
            $aTime['fastpay'] = $aFastpayTime['finishtime'];
        }
        if (!empty($aCompanyTime['finishtime'])){
            $aTime['company'] = $aCompanyTime['finishtime'];
        }
        if (!empty($aManualpayTime['finishtime'])){
            $aTime['manualpay'] = $aManualpayTime['finishtime'];
        }
        if (!empty($aTime)) {
            $sTime = min($aTime);
        }else{
            return array('realbets' => 0);
        }
        // 之前代码  return $this->oDB->getOne("SELECT SUM(`sell` - `totalpoints`) AS realbets FROM user_sales WHERE userid = {$iUserid} AND lvtopid = {$iLvtopid} AND inserttime >='".$sTime."'");
        // 以下代码 都是新增的  主要是要获取 三方的 流水
        $vendorGame = $this->oDB->getOne("SELECT SUM(`realbets`) AS realbets FROM project_game WHERE userid = {$iUserid}  AND insert_time >='".$sTime."'");
        $cp = $this->oDB->getOne("SELECT SUM(`sell` - `totalpoints`) AS realbets FROM user_sales WHERE userid = {$iUserid} AND lvtopid = {$iLvtopid} AND inserttime >='".$sTime."'");
        $aResult = array();
        $totalcp = isset($cp["realbets"])?$cp["realbets"]:0;
        $totalvendor = isset($vendorGame["realbets"])?$vendorGame["realbets"]:0;
        $aResult["realbets"] = $totalcp+$totalvendor;
        return $aResult;
    }

    /**
     * 风控操作
     * @author pierce
     * @date 2017-07-11
     * @param $iId
     * @param array $aData
     * @param $iLvtopid
     * @param $iUserid
     * @return bool|string
     */
    public function setReview($iId,$aData = [],$iLvtopid,$iUserid){
        if($aData['verify_status'] == 2){
            $this->oDB->doTransaction();
            try{
                $aApplyInfo = $this->oDB->getOne("SELECT * FROM `user_withdraw` WHERE `lvtopid`='${iLvtopid}' AND `id`='${iId}' LIMIT 1");
                if (!$aApplyInfo) {
                    $this->oDB->doRollback();
                    return '记录不存在';
                }
                $sNowTime = date("Y-m-d H:i:s");
                $aUserTreeInfo = $this->oDB->getOne("SELECT * FROM `usertree` WHERE `userid`='${aApplyInfo['userid']}' LIMIT 1");
                if (!$aUserTreeInfo) {
                    $this->oDB->doRollback();
                    return '记录不存在';
                }

                // 锁定用户资金账户
                $oUserFund = new model_userfund();
                if (1 != $oUserFund->switchLock($aUserTreeInfo['userid'], 0, TRUE)) {
                    return '消息返回: 账户资金临时被锁, 请稍后再试';
                }
                //写入账变
                $oOrder = new model_orders();
                $aOrders = array();
                $aOrders['iFromUserId'] = $aUserTreeInfo['userid'];//账变人
                $aOrders['iOrderType'] = ORDER_TYPE_TXJD;
                $aOrders['fMoney'] = $aApplyInfo['apply_amount'];
                $aOrders['sActionTime'] = $sNowTime;
                $aOrders['sDescription'] = '[出款失败]';
                $aOrders['iAdminId'] = $aData['verify_adminid'];
                $result = $oOrder->addOrders($aOrders);
                unset($aOrders);
                if (TRUE !== $result) { // 账变发生异常, 事务回滚
                    $this->oDB->doRollback();
                    $oUserFund->switchLock($aUserTreeInfo['userid'], 0, FALSE);
                    return '账变发生异常';
                }
                $sfinishtime = date( "Y-m-d H:i:s", time());
                $aData['finishtime'] = $sfinishtime;
                $this->oDB->update("user_withdraw", $aData, "`id`='" . $iId . "' AND `lvtopid`='" . $iLvtopid . "' AND `userid`= '".$iUserid."' AND `verify_status`= 0");
                if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                    $this->oDB->doRollback();
                    return '更新申请记录状态失败';
                }

                // 账户解锁
                if (1 != $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE)) {
                    return '资金账户解锁失败'; // 资金账户解锁失败
                }

                $this->oDB->doCommit(); // 事务提交
                return TRUE; // 中断程序执行
            }catch (Exception $e) {
                $this->oDB->doRollback();
                return FALSE;
            }
        } else if($aData['status'] == 3 && $aData['verify_status'] == 1){ //取消出款
            return $this->cancel($iLvtopid, $iId, $aData['verify_adminid'], $aData['verify_adminname'],$aData['user_remark'],$aData['admin_remark']);
        } else{
            $this->oDB->update("user_withdraw", $aData, "`id`='" . $iId . "' AND `lvtopid`='" . $iLvtopid . "' AND `userid`= '".$iUserid."' AND `verify_status`= 0");
            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                $this->oDB->doRollback();
                return '更新申请记录状态失败';
            }
            return true;
        }
    }
    /**
     * 根据用户获取用户出款扣手续费次数
     * @param $iUserid
     * @param $iLvtopid
     * @return mixed
     */
    public function getWithdrawTimes($iUserid,$iLvtopid){
        $sToday = date("Y-m-d 00:00:00");
        $aArr = $this->oDB->getOne("SELECT COUNT(*) AS count_withdraw_times FROM user_withdraw WHERE userid = {$iUserid} AND lvtopid = {$iLvtopid} AND admin_fee = 0 AND `status` = 2 AND finishtime >= '".$sToday."'");
        return isset($aArr['count_withdraw_times']) ? $aArr['count_withdraw_times'] : 0;
    }

    /**
     * 设置预备出款
     * @param $iId
     * @param array $aData
     * @param $iLvtopid
     * @return bool
     */
    public function setReadyStatus($iId,$iUserid,$aData = [],$iLvtopid){
        $this->oDB->doTransaction();
        try{
            $this->oDB->update("user_withdraw", $aData, "`id`='" . $iId . "' AND `lvtopid`='" . $iLvtopid . "' AND `status`= 0");
            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                $this->oDB->doRollback();
                return '更新申请记录状态失败';
            }
            // 更新稽核记录
            $aUserBetsCheck = [
                'isclear' => 1,
                'withdraw_id' => $iId,
            ];
            $this->oDB->update("user_bets_check", $aUserBetsCheck, "`isclear`=0 AND `userid` = '".$iUserid."' AND `lvtopid`='" . $iLvtopid . "' AND `withdraw_id`=0");
            $this->oDB->doCommit(); // 事务提交
            return TRUE; // 中断程序执行
        }catch (Exception $e){
            $this->oDB->doRollback();
            return FALSE;
        }
    }
    /**
     * 取消预备出款
     * @param $iId
     * @param array $aData
     * @param $iLvtopid
     * @return bool
     */
    public function consoleReadyStatus($iId,$iUserid,$aData = [],$iLvtopid){
        $this->oDB->doTransaction();
        try{
            $this->oDB->update("user_withdraw", $aData, "`id`='" . $iId . "' AND `lvtopid`='" . $iLvtopid . "' AND (`status`= 1 OR `status`= 4) ");
            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                $this->oDB->doRollback();
                return '更新申请记录状态失败';
            }
            // 更新稽核记录
            $aUserBetsCheck = [
                'isclear' => 0,
                'withdraw_id' => 0,
            ];
            $this->oDB->update("user_bets_check", $aUserBetsCheck, "`isclear`=1 AND `userid` = '".$iUserid."' AND `lvtopid`='" . $iLvtopid . "' AND `withdraw_id`='" . $iId . "'");
            $this->oDB->doCommit(); // 事务提交
            return TRUE; // 中断程序执行
        }catch (Exception $e){
            $this->oDB->doRollback();
            return FALSE;
        }
    }

    /**
     * 自动出款
     * @param $iId
     * @param $iUserid
     * @param array $aData
     * @param $iLvtopid
     * @return bool|string
     */
    public function autoWithdraw($iId,$iUserid,$aData = [],$iLvtopid) {
        $this->oDB->doTransaction();
        try{
            $aWithdraOrder = $this->oDB->getOne("SELECT ubi.realname,ubi.cardno,ubi.bankid,bk.bankname,bk.nickname,uw.* FROM user_withdraw AS uw LEFT JOIN userbankinfo AS ubi ON(uw.userbankcardid = ubi.entry) LEFT JOIN bankinfo AS bk ON(ubi.bankid = bk.bankid) WHERE uw.id = '".$iId."' AND uw.verify_status = 1 AND uw.`status` = 0 AND ubi.isdel = 0 AND ubi.isblack = 0");
            if (empty($aWithdraOrder)) {
                $this->oDB->doRollback();
                return '未查询到出款记录';
            }
            $aWithdraOrder['real_amount'] = $aData['real_amount'];
            $aWithdrawAccount = $this->oDB->getOne("SELECT fc.enname,pfa.* FROM `proxy_fastpay_acc` AS pfa LEFT JOIN fastpay_company AS fc ON(pfa.companyid = fc.id) WHERE pfa.`account_type` = '1' AND pfa.lvtopid = '".$iLvtopid."' AND pfa.`status` = 1");
            if (empty($aWithdrawAccount)) {
                $this->oDB->doRollback();
                return '未查询到三方出款账号';
            }
            if (!empty($aWithdrawAccount['key'])) {
                $aWithdrawAccount['key'] = authcode($aWithdrawAccount['key'], 'DECODE', $GLOBALS['config']['AesKeys']);
            }
            include_once PDIR_USER_MODEL . 'newfastpay' . DS . 'base.php';
            include_once PDIR_USER_MODEL . 'newfastpay' . DS . $aWithdrawAccount['enname'].'.php';
            $oPayModel = A::singleton("model_newfastpay_".$aWithdrawAccount['enname']);
            if (!method_exists ($oPayModel, 'withdrawQuery')){
                $this->oDB->doRollback();
                return '此三方不支持出款';
            }
            $aWithdrawMsg = $oPayModel->withdrawQuery(['with_account' => $aWithdrawAccount, 'withdraw_order' => $aWithdraOrder]);
            // 写日志
            @model_rechargemoney::addFastPayLog([
                'lvtopid' => $iLvtopid,
                'userid' => $iUserid,
                'user_withdraw_id' => $iId,
                'request_id' => $aWithdrawAccount['id'],
                'log_type' => 1,
                'remark' => $aWithdrawMsg['data']['retMsg'],
                'interface_name' => $aWithdrawAccount['enname'],
                'method' => 1,
                'request_data' => json_encode($aWithdrawMsg['request_data']),
                'response_data' => json_encode($aWithdrawMsg['data'])
            ]);
            if ($aWithdrawMsg['code'] != 2) {
                $aData['fastpayid'] = $aWithdrawAccount['id'];
                $this->oDB->update("user_withdraw", $aData, "`id`='" . $iId . "' AND `lvtopid`='" . $iLvtopid . "' AND `status`= 0");
                if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                    $this->oDB->doRollback();
                    return '更新申请记录状态失败';
                }
                // 更新稽核记录
                $aUserBetsCheck = [
                    'isclear' => 0,
                    'withdraw_id' => 0,
                ];
                $this->oDB->update("user_bets_check", $aUserBetsCheck, "`isclear`=1 AND `userid` = '".$iUserid."' AND `lvtopid`='" . $iLvtopid . "' AND `withdraw_id`='" . $iId . "'");
                $this->oDB->doCommit(); // 事务提交
                return TRUE; // 中断程序执行

            }else{
                return $aWithdrawMsg['msg']['retMsg'];
            }
        }catch (Exception $e){
            $this->oDB->doRollback();
            return FALSE;
        }
    }
    /**
     * 取消
     * @author pierce
     * @date 2017-07-15
     * @param $iLvtopid
     * @param $iId
     * @return bool;
     */
    public function cancel($iLvtopid, $iId, $iAdminid, $sAdminname,$user_remark,$admin_remark) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid) || empty($iId) || !is_numeric($iId)) {
            return false;
        }
        // 写入出款失败账变
        $this->oDB->doTransaction();
        try{
            $aApplyInfo = $this->oDB->getOne("SELECT * FROM `user_withdraw` WHERE `lvtopid`='${iLvtopid}' AND `id`='${iId}' LIMIT 1");
            if (!$aApplyInfo) {
                $this->oDB->doRollback();
                return '记录不存在';
            }

            $aUserInfo = $this->oDB->getOne("SELECT * FROM `users` WHERE `lvtopid`='{$iLvtopid}' AND  `userid`='${aApplyInfo['userid']}' LIMIT 1");
            if (!$aUserInfo) {
                $this->oDB->doRollback();
                return '记录不存在';
            }

            $sNowTime = date("Y-m-d H:i:s");
            $aUserTreeInfo = $this->oDB->getOne("SELECT * FROM `usertree` WHERE `userid`='${aApplyInfo['userid']}' LIMIT 1");
            if (!$aUserTreeInfo) {
                $this->oDB->doRollback();
                return '记录不存在';
            }

            // 锁定用户资金账户
            $oUserFund = new model_userfund();
            if (1 != $oUserFund->switchLock($aUserTreeInfo['userid'], 0, TRUE)) {
                return '消息返回: 账户资金临时被锁, 请稍后再试';
            }
            // 更新稽核记录
            $aUserBetsCheck = [
                'isclear' => 0,
                'withdraw_id' => 0,
            ];
            if (false === $this->oDB->update('user_bets_check', $aUserBetsCheck,"`withdraw_id`='" . $iId . "' AND `lvtopid`='" . $iLvtopid . "' AND `userid`='" . $aApplyInfo['userid'] . "' AND `isclear`= '1'")) {
                $this->oDB->doRollback();
                return '更新稽查数据失败';
            }
            //写入账变
            $oOrder = new model_orders();
            $aOrders = array();
            $aOrders['iFromUserId'] = $aUserTreeInfo['userid'];//账变人
            $aOrders['iOrderType'] = ORDER_TYPE_TXJD;
            $aOrders['fMoney'] = $aApplyInfo['apply_amount'];
            $aOrders['sActionTime'] = $sNowTime;
            $aOrders['sDescription'] = '[出款失败]';
            $aOrders['iAdminId'] = $iAdminid;
            $result = $oOrder->addOrders($aOrders);
            unset($aOrders);
            if (TRUE !== $result) { // 账变发生异常, 事务回滚
                $this->oDB->doRollback();
                $oUserFund->switchLock($aUserTreeInfo['userid'], 0, FALSE);
                return '账变发生异常';
            }
            // 更新申请记录状态
            $sfinishtime = date( "Y-m-d H:i:s", time());
            $this->oDB->query("UPDATE `user_withdraw` SET `status` = 3,`finishtime` = '${sfinishtime}',`admin_remark` = '${admin_remark}',`user_remark` = '${user_remark}' WHERE `id` = '${iId}' AND `lvtopid`='${iLvtopid}' AND `status` = '0'");
            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                $this->oDB->doRollback();
                $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE);
                return '更新申请记录状态失败';
            }

            // 账户解锁
            if (1 != $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE)) {
                return '资金账户解锁失败'; // 资金账户解锁失败
            }

            $this->oDB->doCommit(); // 事务提交
            return TRUE; // 中断程序执行
        }catch (Exception $e) {
            $this->oDB->doRollback();
            return FALSE;
        }
    }
    /**
     * 申请确认
     * @author Pierce
     * @date 2017-07-15
     * @param $iLvtopid
     * @param $iId
     * @param $iAdminId
     * @param $sAdminName
     * @return bool|string
     */
    public function confirm($iLvtopid, $iId, $iAdminId, $sAdminName,$sRemark,$iWithDrawType = 0,$iFastAcc = 0) {
        $this->oDB->doTransaction();
        try {
            $aApplyInfo = $this->oDB->getOne("SELECT * FROM `user_withdraw` WHERE `lvtopid`='${iLvtopid}' AND `id`='${iId}' LIMIT 1");
            if (!$aApplyInfo) {
                $this->oDB->doRollback();
                return '记录不存在';
            }

            $aUserInfo = $this->oDB->getOne("SELECT * FROM `users` WHERE `lvtopid`='{$iLvtopid}' AND  `userid`='${aApplyInfo['userid']}' LIMIT 1");
            if (!$aUserInfo) {
                $this->oDB->doRollback();
                return '记录不存在';
            }

            $sNowTime = date("Y-m-d H:i:s");
            $aUserTreeInfo = $this->oDB->getOne("SELECT * FROM `usertree` WHERE `userid`='${aApplyInfo['userid']}' LIMIT 1");
            if (!$aUserTreeInfo) {
                $this->oDB->doRollback();
                return '记录不存在';
            }

            // 锁定用户资金账户
            $oUserFund = new model_userfund();
            if (1 != $oUserFund->switchLock($aUserTreeInfo['userid'], 0, TRUE)) {
                return '消息返回: 账户资金临时被锁, 请稍后再试';
            }

            // 写入出款成功账变
            $oOrder = new model_orders();

            // 更新稽核记录
            $aUserBetsCheck = [
                'isclear' => 2,
            ];
            if (false === $this->oDB->update('user_bets_check', $aUserBetsCheck,"`withdraw_id`='" . $iId . "' AND `lvtopid`='" . $iLvtopid . "' AND `userid`='" . $aApplyInfo['userid'] . "' AND `isclear` = '1'")) {
                $this->oDB->doRollback();
                return '更新稽查数据失败';
            }

            $aOrders = array();
            $aOrders['iFromUserId'] = $aUserTreeInfo['userid'];//账变人
            $aOrders['iOrderType'] = ORDER_TYPE_TXCG;
            $aOrders['fMoney'] = $aApplyInfo['apply_amount'];
            $aOrders['sActionTime'] = $sNowTime;
            $aOrders['sDescription'] = '[出款成功]';
            $aOrders['iAdminId'] = $iAdminId;
            $result = $oOrder->addOrders($aOrders);
            unset($aOrders);
            if (TRUE !== $result) { // 账变发生异常, 事务回滚
                $this->oDB->doRollback();
                $oUserFund->switchLock($aUserTreeInfo['userid'], 0, FALSE);
                return '账变发生异常。CODE: ' . $result;
            }

            // 增加用户累计提现总额       此处理 以迁至 model/userreport.php  124行进行处理
//            $this->oDB->query("UPDATE `users` SET `totalwithdrawal`= `totalwithdrawal` + {$aApplyInfo['apply_amount']},`withdrawaltimes`= `withdrawaltimes` + 1 WHERE userid='${aApplyInfo['userid']}' and `totalwithdrawal` ='${aUserInfo['totalwithdrawal']}' and `withdrawaltimes` ='${aUserInfo['withdrawaltimes']}'");
//            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 增加用户累计提现总额失败
//                $this->oDB->doRollback();
//                $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE);
//                return '增加用户累计提现总额失败';
//            }

            // 更新申请记录状态
            $sfinishtime = date( "Y-m-d H:i:s", time());
            $this->oDB->query("UPDATE `user_withdraw` SET `status` = 2,`finishtime` = '${sfinishtime}',`admin_remark` = '${sRemark}'  WHERE `id` = '${iId}' AND `lvtopid`='${iLvtopid}' AND `status` = '1' AND `withdraw_type`='${iWithDrawType}'");
            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                $this->oDB->doRollback();
                $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE);
                return '更新申请记录状态失败';
            }
            if ($iWithDrawType == 1 && $iFastAcc > 0) {
                //更新三方账号额度
                $this->oDB->query("UPDATE `proxy_fastpay_acc` SET `current_quota`= `current_quota` + '".$aApplyInfo['apply_amount']."' WHERE `id`='".$iFastAcc."'");
                if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                    $this->oDB->doRollback();
                    $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE);
                    return '增加三方账号额度失败';
                }
            }
            // 更新用户银行卡信息
            $this->oDB->query("UPDATE `userbankinfo` SET `totaltransfer` = `totaltransfer` + {$aApplyInfo['real_amount']} WHERE `entry` = '".$aApplyInfo['userbankcardid']."'  AND `isdel` = '0' AND `isblack` = '0'");
            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                $this->oDB->doRollback();
                $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE);
                return '此卡已被删除或列入黑名单,无法通过';
            }

            // 账户解锁
            if (1 != $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE)) {
                return '资金账户解锁失败'; // 资金账户解锁失败
            }

            $this->oDB->doCommit(); // 事务提交

            // 发送站内信
            $aMsg['proxyadminid'] = $iAdminId;//发送者
            $aMsg['lvtopid']      = $iLvtopid; //发送者
            $aMsg['sendername']   = $sAdminName; //发送者
            $aMsg['send_range'] = "ismember"; //给用户发送
            $aMsg['receivename'] = $aUserTreeInfo['username']; // 接收人
            $aMsg['mt'] = 1; //消息类型，用户充提
            $aMsg['subject'] = "恭喜您，您的提现申请已通过"; //消息标题
            $aMsg['content'] = "恭喜您，您的提现申请已通过，提现金额为".$aApplyInfo['real_amount']."，请注意查看您的账变信息，如果有任何疑问请联系在线客服";
            $oProxyMessage = new model_proxymessage();
            $a = $oProxyMessage->InsertMessageFromAdmin($aMsg);
            
            return TRUE; // 中断程序执行
        } catch (Exception $e) {
            $this->oDB->doRollback();
            return FALSE;
        }
    }

    /**
     * 根据id更新出款表备注
     * @author pierce
     * @data 2017-07-30
     * @param $aData
     * @param $iId
     * @param $iUserid
     * @param $iLvtopid
     * @return bool|int
     */
    public function addRemark($aData,$iId,$iUserid,$iLvtopid){
        $aKey = array_keys($aData);
        $key = $aKey[0];
        return $this->oDB->query("UPDATE `user_withdraw` SET $key = concat('$aData[$key]',$key) WHERE  `id` = '$iId' AND  `lvtopid` = '$iLvtopid' AND `userid` = '$iUserid'");
    }

    /**
     * 根据用户获取当天出款次数
     * @param $iUserid
     * @param $iLvtopid
     * @return mixed
     */
    public static function getTodayWithdrawTimes($iUserid,$iLvtopid){

        $oSelf = new self();

        $iUserid = intval($iUserid);
        $iLvtopid = intval($iLvtopid);

        $sStart = date("Y-m-d 00:00:00");

        $aResult = $oSelf->oDB->getOne("SELECT COUNT(*) AS `count_withdraw_times` FROM `user_withdraw` WHERE`userid` = '{$iUserid}' AND `lvtopid` = '{$iLvtopid}' AND `status` in ('0','1','2') AND `applytime` >= '{$sStart}'");
        return isset($aResult['count_withdraw_times']) ? $aResult['count_withdraw_times'] : 0;
    }

    /**
	 * 根据出款id查询该id前，各个提款申请订单状态的数目
	 * @author  james liang
	 * @date     2017-8-1
	 *
	 * @param  int  $id   提款申请id
	 */
    public function getCountById($id) {
    	$sql = "select count(*) as num,`status` from `user_withdraw` where `id`<={$id} group by `status` order by `status`";
    	$aAll = $this->getDB()->getAll($sql);
    	$aList = [];
    	foreach ($aAll as $list) {
    		if ($list['status'] == 0) $aList['status_0'] = $list['num'];
    		if ($list['status'] == 1) $aList['status_1'] = $list['num'];
    		if ($list['status'] == 2) $aList['status_2'] = $list['num'];
    		if ($list['status'] == 3) $aList['status_3'] = $list['num'];
		}
		unset($aAll);
    	return $aList;
	}
    /**
     * 30秒获取是否有入款 提款 风控数据
     * @author Mark
     */
    public function getMoneyData($lvtopid){
        $sSql = "SELECT SUM(gsrk) gsrk, SUM(yhtk) yhtk, SUM(fk) fk,SUM(sf) sf FROM(
                SELECT MAX(id) gsrk, 0 yhtk,0 dsf,0 fk,0 sf FROM `user_deposit_company` WHERE  `lvtopid` = '{$lvtopid}' AND `status` =0 UNION ALL
                SELECT 0 gsrk,COUNT(id) yhtk ,0 dsf,0 fk,0 sf FROM `user_withdraw` WHERE  `lvtopid` = '{$lvtopid}' AND `verify_status`!=2 AND `status`=0 UNION ALL
                SELECT 0 gsruk,0 yhtk,0 dsf,COUNT(id) fk,0 sf FROM proxy_lotteryrisk WHERE status=0 AND lvtopid='{$lvtopid}' UNION ALL
                SELECT 0 gsruk,0 yhtk,0 dsf,0 fk,COUNT(id) sf FROM proxy_fastpay_acc WHERE lvtopid='{$lvtopid}' AND current_quota >= quota AND quota > 0)tab";
        return $this->oDB->getOne($sSql);
    }
    /**
     * desc 获取30秒内是否有提现用户
     * @author rhovin 2017-10-30
     */
    public function getCountWithDrawByTime($iLvtopid) {
        $sSql = "SELECT MAX(id) AS `count_withdraw` FROM `user_withdraw` WHERE  `lvtopid` = '{$iLvtopid}' AND `verify_status`!=2 AND `status`=0";
        return $this->getDB()->getOne($sSql);
    }

    /**
     * 获取上次确认出款至今总入款
     * @param $iUserid
     * @param $iLvtopid
     * @return mixed
     */
    public function getLastTimesInfo($iUserid,$iLvtopid) {
        $aInsertTime = $this->oDB->getOne("SELECT MAX(finishtime) AS finishtime FROM user_withdraw WHERE `status` = 2 AND userid = {$iUserid} AND lvtopid = {$iLvtopid}");
        if ($aInsertTime['finishtime'] == ""){
            $aInsertTime['finishtime'] = 0;
        }
        $aLastInfo =array();
        $aCompanyDeposit = $this->oDB->getOne("SELECT SUM(real_amount) AS companydeposit, SUM(favor_amount) AS companyfavor  FROM user_deposit_company WHERE lvtopid = '".$iLvtopid."' AND userid = '".$iUserid."' AND `status` = 2 AND finishtime >= '".$aInsertTime['finishtime']."'");
        $aFastpayDeposit = $this->oDB->getOne("SELECT SUM(real_amount) AS fastpaydeposit, SUM(favor_amount) AS fastpayfavor FROM user_deposit_fastpay WHERE lvtopid = '".$iLvtopid."' AND userid = '".$iUserid."' AND `status` = 2 AND finishtime >= '".$aInsertTime['finishtime']."'");
        $aManualpayDeposit = $this->oDB->getOne("SELECT SUM(amount) AS manualpaydeposit, SUM(ext_amount) AS manualpayfavor FROM manualpay_confirm WHERE lvtopid = '".$iLvtopid."' AND user_ids = '".$iUserid."' AND `order_type` IN(0,1,3,5,8) AND isconfirm = 1 AND finishtime >= '".$aInsertTime['finishtime']."'");
        $aPoint = $this->oDB->getOne("SELECT SUM(points) AS points, SUM(bonus) AS bonus,SUM(bets) AS bets FROM user_report WHERE lvtopid = '".$iLvtopid."' AND userid = '".$iUserid."' AND DATE_FORMAT(`day` ,'%Y-%m-%d') >='" . $aInsertTime['finishtime'] . "'");
        $aLastInfo['deposit'] = sprintf("%.4f",$aCompanyDeposit['companydeposit'] + $aFastpayDeposit['fastpaydeposit'] + $aManualpayDeposit['manualpaydeposit']);
        $aLastInfo['favor'] = sprintf("%.4f",$aCompanyDeposit['companyfavor'] + $aFastpayDeposit['fastpayfavor'] + $aManualpayDeposit['manualpayfavor']);
        $aLastInfo['points'] = sprintf("%.4f",$aPoint['points']);
        $aLastInfo['bonus'] = sprintf("%.4f",$aPoint['bonus']);
        $aLastInfo['bets'] = sprintf("%.4f",$aPoint['bets']);
        $aLastInfo['profit'] = sprintf("%.4f",$aPoint['bets'] - $aPoint['bonus'] - $aPoint['points']);
        return $aLastInfo;
    }

    /**
     * 查询自动出款中的订单并处理
     */
    public function searchWithdrawOrder($sStartDate = ""){
        if ($sStartDate == "") {
            $sStartDate = date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d'),date('Y')));
        }
        $sSql = "SELECT pfa.merchantid, pfa.`key`, pfa.pubkey,fc.enname, uw.* FROM `user_withdraw` AS uw LEFT JOIN proxy_fastpay_acc AS pfa ON (uw.fastpayid = pfa.id)  LEFT JOIN fastpay_company AS fc ON(pfa.companyid = fc.id) WHERE uw.`applytime`>='" . $sStartDate  . "' AND uw.`applytime`<='" .  date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1) . "' AND uw.`verify_status`=1 AND uw.`status`=1 AND uw.`withdraw_type` =1";
        $aWithdrawOrders = $this->oDB->getAll($sSql);
        if (empty($aWithdrawOrders)) {
            return "";
        }
        include_once PDIR_USER_MODEL . 'newfastpay' . DS . 'base.php';
        include_once PDIR_USER_MODEL . 'newfastpay' . DS . $aWithdrawOrders[0]['enname'].'.php';
        $oPayModel = A::singleton("model_newfastpay_".$aWithdrawOrders[0]['enname']);
        foreach ($aWithdrawOrders as $k => $v) {
            $aWithdrawMsg = $oPayModel->searchWithdraw($v);
            // 写日志
            @model_rechargemoney::addFastPayLog([
                'lvtopid' => $v['lvtopid'],
                'userid' => $v['userid'],
                'user_withdraw_id' => $v['id'],
                'request_id' => $v['fastpayid'],
                'log_type' => 1,
                'interface_name' => $v['enname'],
                'method' => 1,
                'remark' => $aWithdrawMsg['data']['retMsg'],
                'request_data' => json_encode($aWithdrawMsg['request_data']),
                'response_data' => json_encode($aWithdrawMsg['data'])
            ]);
            if (!empty($aWithdrawMsg['data']) && $aWithdrawMsg['code'] == 0) {
                $bResult = $this->confirm($v['lvtopid'], $v['id'], $v['adminid'], $v['adminname'],'自动出款成功',1,$v['fastpayid']);
            }else{
                return $aWithdrawMsg['msg'];
            }
            return $bResult;
        }
    }
}