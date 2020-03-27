<?php

class model_pmanualpaychecked extends model_userfund
{
    /**
     * 用户理赔 复写父类方法 1
     *
     * 如果充值类型为1 则对应报表中字段 realpayment
     * 如果充值类型为 3 则对应报表中字段 activity
     * 如果充值类型为其他 则对用报表中字段 payment
     * @author ken 2017
     * @todo rewrite true NO.1
     * @param $aData
     * @return int
     *
     * @des $aCode : [0 => 错误码]
     *               [1 => 不通过的userid]
     *                [2 => 通过的userid]
     */
    public function adminPayMentProxy($aData)
    {
        // 还原数据
        $sDescription = $aData['confirm_remark'];
        $iOptype = isset($aData['optype']) ? $aData['optype'] : '';
//        $aData['ext_amount'] = isset($aData['ext_amount']) ? $aData['ext_amount'] : 0 ;// 判断是否更新审核表
        $exMoney = $aData['ext_amount'];
        $fMoney = $aData['amount'];
        $iUserId = $aData['user_ids'];
        $iClaimType = isset($aData['claimtype']) ? $aData['claimtype'] : '';
        $aClaimType = [0 => '[普通存入]', 1 => '[人工存款]', 3 => '[优惠存款]', 4 => '[扣款]', 5 => '[手工申请]'
            , 6 => '[公司入款误存]', 7 => '[优惠扣减]'
        ];
        if ($iClaimType === '') {
            return -11;
        }
        // 2017-08-09 ken 修复报表账变 完善一致性原则 确保不能伪造表单
        if (3 == $iClaimType) {
            $fMoney = $exMoney;// 仅仅有优惠存入
        }
        $iChannelId = $aData['channelid'];
        $iAdminId = $aData['confirm_admin_id'];// 当前登录管理员id
        $iAdminName = $aData['confirm_admin']; // 当前登录管理员名
        $sDay = '';
        if ($sDescription != "") {
            $sDescription = ":" . daddslashes($sDescription);
        }
        $fMoney = round(floatval($fMoney), 4); //资金格式化
        
//        if (strpos($iUserId,',')) { // 如果是批量存入 多用户
//            $iOneUserid = intval($iUserId);
//            if (empty($iOneUserid) && !is_numeric($iOneUserid)) {
//                return -13;
//            }
//            $aUserId = explode(',',$iUserId);
//            if (count($aUserId) < 2 || !is_array($aUserId)) {
//                return -12;// 批量存入用户错误
//            }
//            unset($iUserId);
//            // 记录成功或者失败条数
//            $aCode = [];
//            $lock = true;//避免重复写入
//            $go = true;
//            foreach ($aUserId as $iUserId) {
//            // 开始事务
//                $this->oDB->doTransaction();
//                    if (FALSE == $this->switchLock($iUserId, 0, TRUE, '理赔充值')) {
//                        $this->oDB->doRollback();
//                        $aCode[0][] = -2;// 账号被锁
//                        $aCode[1][] = $iUserId;
//                        continue;
//                    }
//                    //执行账户被锁
//                $this->oDB->doCommit();
//                /*         * *(构建参数)** */
//                $sSql = "SELECT * FROM `usertree` WHERE `userid`='" . $iUserId . "' AND `parentid`='0' AND `isdeleted`='0' ";
//                $aUsers = $this->oDB->getone($sSql);
//                $oOrder = new model_orders();
//                $iOrderType = ORDER_TYPE_LPCZ;
//                if ($lock) {
//                    $sDescription = isset($aClaimType[$iClaimType]) . $sDescription ? $aClaimType[$iClaimType] . $sDescription : "[理赔充值]" . $sDescription;
//                }
//                $lock = false;
//                $sActionTime = date("Y-m-d H:i:s"); //动作时间
//                /*         * *(开始进行充值账变)** */
//                $this->oDB->doTransaction();
//                $aOrders = array();
//                $aOrders['iFromUserId'] = $iUserId;
//                $aOrders['iToUserId'] = $iClaimType;
//                $aOrders['iOrderType'] = $iOrderType;
//                $aOrders['fMoney'] = $fMoney;
//                $aOrders['sActionTime'] = $sActionTime;
//                $aOrders['sDescription'] = $sDescription;
//                $aOrders['iAdminId'] = $iAdminId;
//                $aOrders['iChannelID'] = $iChannelId;
//                $mResult = $oOrder->addOrders($aOrders);
//                unset($aOrders);
//                /*  **（如果优惠金额大于零 且存入金额大于零 则进行第二次账变）***/
//                if (!empty($aData['amount']) && $aData['amount'] > 0 && $aData['ext_amount'] > 0
//                    && !empty($aData['ext_amount']) && $iClaimType == 1) {
//                    // 整理数据
////                $this->oDB->doTransaction();
//                    $aOrders = array();
//                    $aOrders['iFromUserId'] = $iUserId;
//                    $aOrders['iToUserId'] = 3;//第二次账变为活动优惠
//                    $aOrders['iOrderType'] = $iOrderType;
//                    $aOrders['fMoney'] = $aData['ext_amount'];//优惠金额
//                    $aOrders['sActionTime'] = $sActionTime;
//                    $aOrders['sDescription'] = $sDescription;
//                    $aOrders['iAdminId'] = $iAdminId;
//                    $aOrders['iChannelID'] = $iChannelId;
//                    $mResult = $oOrder->addOrders($aOrders);
//                    unset($aOrders);
//                }
//                //添加理赔报表数据
//                $aClaim = array(); //理赔的相关数据
//                $aClaim['userid'] = $iUserId; //理赔用户ID
//                if (empty($aUsers)) {
//                    $sSql = "SELECT * FROM `usertree` WHERE `userid`='" . $iUserId . "' AND `isdeleted`='0' ";
//                    $aUsers = $this->oDB->getone($sSql);
//                }
//                $aClaim['username'] = $aUsers['username']; //理赔用户名称
//                $aClaim['istester'] = $aUsers['istester']; //是否测试账户
//                //$aClaim['adminid'] = isset($_SESSION["admin"]) ? $_SESSION["admin"] : ($iAdminId > 0 ? $iAdminId : 9999); //操作管理员ID
//                $aClaim['adminid'] = isset($iAdminId) ? $iAdminId : ($iAdminId > 0 ? $iAdminId : 9999);// ken 2017-08-09
////            $aClaim['adminname'] = isset($_SESSION["adminname"]) ? $_SESSION["adminname"] : "系统管理员"; //操作管理名称
//                $aClaim['adminname'] = isset($iAdminName) ? $iAdminName : "系统管理员";
//                $aClaim['type'] = $iClaimType; //理赔类型:0:普通理赔,1充值理赔,2分红理赔,3红包理赔
//                $aClaim['amount'] = $fMoney; //理赔金额
//                $aClaim['desc'] = str_replace("理赔充值:", "", $sDescription); //理赔原因
//                if ($sDay == '') {
//                    $sDay = date("Y-m-d");
//                }
//                $aClaim['day'] = $iClaimType == 2 ? $sDay : date("Y-m-d"); //理赔日期
//                $aClaim['times'] = $sActionTime; //理赔时间
//                $this->oDB->insert("claims", $aClaim);//添加理赔数据
//                /*todo 更新打码表事务*/
//                $iUpdateBets = $this->updateUserBetsCheck($aData,$iUserId);
//                // 同一回滚
//                if (TRUE !== $mResult || TRUE !== $iUpdateBets) {
//                    $this->oDB->doRollback();
//                    $this->switchLock($iUserId, 0, FALSE);
//                    if ($mResult < 0) {
//                        $aCode[0][$iUserId] = $mResult;
//                    } else if ($iUpdateBets < 0) {
//                        $aCode[0][$iUserId] = $iUpdateBets;
//                    }
//                    $aCode[1][] = $iUserId;
//                    continue;
//                }
//                /*todo 增加更新数据表事务*/
//                 if ($go){
//                     $iUpdateCode = $this->UpdateOneConfirmById($aData); // 原子性操作1
//                     if (TRUE !== $iUpdateCode) {
//                         $this->oDB->doRollback();
//                         $this->switchLock($iUserId, 0, FALSE);//解锁资金表
//                         return $iUpdateCode;// 表状态不正确
//                     }
//                        }
//                    $go=false;
//                    $this->oDB->doCommit();
//                    $this->switchLock($iUserId, 0, FALSE);
//                    $aCode[2][] = $iUserId;
//            }// end of foreach
//            unset($iUserId);
//            return $aCode;
//        }//end of if
        // 开始事务
        $this->oDB->doTransaction();
        if (FALSE == $this->switchLock($iUserId, 0, TRUE, '理赔充值')) {
            $this->oDB->doRollback();
            return -2; //账户被锁
        }
        //执行账户被锁
        $this->oDB->doCommit();
        /*         * *(构建参数)** */
        $sSql = "SELECT * FROM `usertree` WHERE `userid`='" . $iUserId . "' AND `parentid`='0' AND `isdeleted`='0' ";
        $aUsers = $this->oDB->getone($sSql);
        $oOrder = new model_orders();
       // $iOrderType = $iClaimType == 3 ? ORDER_TYPE_HDHB:ORDER_TYPE_LPCZ;
       // $sDescription = isset($aClaimType[$iClaimType]).$sDescription ? $aClaimType[$iClaimType].$sDescription :"[理赔充值]" . $sDescription;
        $sActionTime = date("Y-m-d H:i:s"); //动作时间
        /*         * *(开始进行充值账变)** */
        $this->oDB->doTransaction();

        //理赔报表数据
        $aClaim = array(); //理赔的相关数据
        $aClaim['userid'] = $iUserId; //理赔用户ID
        if (empty($aUsers)) {
            $sSql = "SELECT * FROM `usertree` WHERE `userid`='" . $iUserId . "' AND `isdeleted`='0' ";
            $aUsers = $this->oDB->getone($sSql);
        }
        $aClaim['username'] = $aUsers['username']; //理赔用户名称
        $aClaim['istester'] = $aUsers['istester']; //是否测试账户
        $aClaim['adminid'] = isset($_SESSION["admin"]) ? $_SESSION["admin"] : ($iAdminId > 0 ? $iAdminId : 9999); //操作管理员ID
        $aClaim['adminname'] = isset($_SESSION["adminname"]) ? $_SESSION["adminname"] : "系统管理员"; //操作管理名称
        if ($sDay == '') {
            $sDay = date("Y-m-d");
        }
        $aClaim['day'] = $iClaimType == 2 ? $sDay : date("Y-m-d"); //理赔日期
        $aClaim['times'] = $sActionTime; //理赔时间

        //判断是否是普通存入 或 存款存入
        if(($iClaimType == 0 || $iClaimType == 1) && $fMoney > 0){
            $aOrders = array();
            $aOrders['iFromUserId'] = $iUserId;
            $aOrders['iToUserId'] = $iClaimType;
            $aOrders['iOrderType'] = ORDER_TYPE_LPCZ;
            $aOrders['fMoney'] = $fMoney;
            $aOrders['sActionTime'] = $sActionTime;
            $aOrders['sDescription'] = $aClaimType[$iClaimType].$sDescription;
            $aOrders['iAdminId'] = $iAdminId;
            $aOrders['iChannelID'] = $iChannelId;
            $mResult = $oOrder->addOrders($aOrders);
            unset($aOrders);
            if (TRUE !== $mResult) {
                $this->oDB->doRollback();
                $this->switchLock($iUserId, 0, FALSE);  //解锁资金表
                return $mResult; //相关的返回值(参考orders)
            }
            //理赔报表插入
            $aClaim['type'] = $iClaimType; //理赔类型:0:普通理赔,1充值理赔,2分红理赔,3红包理赔
            $aClaim['amount'] = $fMoney; //理赔金额
            $aClaim['desc'] = str_replace("理赔充值:", "", $aClaimType[$iClaimType].$sDescription); //理赔原因
            $this->oDB->insert("claims", $aClaim);//添加理赔数据
        }
        //判断是否是存款优惠 或  活动优惠
        if(($iClaimType == 1 && $aData['ext_amount'] > 0) || ($iClaimType == 3 && $aData['ext_amount'] > 0)){
            $aOrders = array();
            $aOrders['iFromUserId'] = $iUserId;
            $aOrders['iToUserId'] = 3;// 活动优惠
            $aOrders['iOrderType'] = ORDER_TYPE_HDHB;
            $aOrders['fMoney'] = round(floatval($aData['ext_amount']),4);//优惠金额
            $aOrders['sActionTime'] = $sActionTime;
            $aOrders['sDescription'] = $aClaimType[3].":".daddslashes($aData['confirm_remark']);
            $aOrders['iAdminId'] = $iAdminId;
            $aOrders['iChannelID'] = $iChannelId;
            $mResult = $oOrder->addOrders($aOrders);
            unset($aOrders);
            if (TRUE !== $mResult) {
                $this->oDB->doRollback();
                $this->switchLock($iUserId, 0, FALSE);  //解锁资金表
                return $mResult; //相关的返回值(参考orders)
            }
            //优惠金额理赔报表插入
            $aClaim['type'] = 3;
            $aClaim['amount'] = round($exMoney,4);
            $aClaim['desc'] = $aClaimType[3].":".daddslashes($aData['confirm_remark']);
            $this->oDB->insert("claims",$aClaim);
        }
        /*todo 增加更新数据表事务*/
        $iCheckF = $this->UpdateOneConfirmById($aData); // 原子性操作1
        if ($iCheckF !== TRUE) {
            $this->oDB->doRollback();
            $this->switchLock($iUserId, 0, FALSE);  //解锁资金表
            return $iCheckF; //相关的返回值(参考orders)
        }
        //等于人工存款的时候
        if ($iClaimType == 1){
            $sSql = "update users set loadmoney = loadmoney+$fMoney,rechargetimes = rechargetimes+1 WHERE userid = $iUserId";
            $bResult = $this->oDB->query($sSql);
            if ($bResult !== TRUE) {
                $this->oDB->doRollback();
                $this->switchLock($iUserId, 0, FALSE);  //解锁资金表
                return -25; //相关的返回值(参考orders)
            }
        }
        /*todo 增加更新数据表事务*/
        $iUpdateBets = $this->updateUserBetsCheck($aData);
        if ($iUpdateBets !== TRUE) {
            $this->oDB->doRollback();
            $this->switchLock($iUserId, 0, FALSE);  //解锁资金表
            return $iUpdateBets; //相关的返回值(参考orders)
        }
        $this->oDB->doCommit();
        //提交事务
        if (FALSE == $this->switchLock($iUserId, 0, FALSE)) {// 如果解锁失败
            return -3;
        }
        return 1;
    }

    /**
     * 更新打码表
     * @param $aOriginData
     * @param $iUserId
     * @return bool|int
     */
    private function updateUserBetsCheck ($aOriginData,$iUserId = '')
    {
        $aData = array();//init
        $aData['lvtopid'] = $aOriginData['lvtopid'];
        $aData['amount'] = isset($aOriginData['amount']) ? round(floatval($aOriginData['amount']), 4) : 0; // 充值金额
        $aData['ext_amount'] = isset($aOriginData['ext_amount']) ? round(floatval($aOriginData['ext_amount']), 4) : 0; // 额外金额
        $aData['ext_bets'] = isset($aOriginData['ext_bets']) ? round(floatval($aOriginData['ext_bets']), 4) : 0; // 综合打码量
        $aData['need_bets'] = isset($aOriginData['need_bets']) ? $aOriginData['need_bets'] : '';
        $aData['inserttime'] = date("Y-m-d H:i:s");
        $aData['userid'] = isset($aOriginData['user_ids']) ? $aOriginData['user_ids'] : '';
        if (!empty($iUserId) && is_numeric($iUserId)) {
            $aData['userid'] = $aOriginData['user_ids'];
            $iRst = $this->oDB->insert('user_bets_check' ,$aData);
            if ($iRst > 0) {
                return TRUE;
            }else {
                return -2002;// 更新打码表失败
            }
        }
        $aUserId = explode(',', $aOriginData['user_ids']);
        unset($sSql);
        unset($iuserid);
        foreach ($aUserId as $iuserid) {
            $aData['userid'] = $iuserid;
            $iRst = $this->oDB->insert('user_bets_check' ,$aData);
        }
        unset($aData);
        if ($iRst > 0) {
            return TRUE;
        }else {
            return -2002;// 更新打码表失败
        }
    }

    /**
     * 更新确认表
     * @author ken 2017
     * @param $aOriginData
     * @return bool|int
     */
    private function UpdateOneConfirmById ($aOriginData)
    {
        $aData = array();
        $aData['isconfirm'] = 1;
        $sfinishtime = date( "Y-m-d H:i:s", time());
        $aData['confirmadmin'] = $aOriginData['confirm_admin'];
        $aData['confirm_admin'] = $aOriginData['confirm_admin_id'];
        $aData['confirm_remark'] = $aOriginData['confirm_remark'];
        $aData['finishtime'] = $sfinishtime;
        $sWhere = " `id` = '{$aOriginData['id']}' AND `isconfirm` = 0 ";
        unset($aOriginData['id']);
        $iResult = $this->oDB->update('manualpay_confirm', $aData, $sWhere);
        unset($aData);
        if ($iResult > 0 ) {
            return TRUE;
        } else {
            return -2001;// 更新审核表失败
        }
    }

    /**
     * 管理员扣减
     *
     * @author ken 2017
     *@todo rewrite true NO.2
     * @param int $iAdminId
     * @param int $iUserId
     * @param money $fMoney
     * @param string $sDescription
     * @param int $iChannelId
     * @return int
     *
     */
    function admintoUserPayWithDraw($iAdminId, $iUserId, $fMoney, $sDescription = "", $iChannelId, $aData = []) {
        if ((!is_numeric($iAdminId)) || (!is_numeric($iUserId))) {
            return -1; //数据不全
        }
        if ($sDescription != "") {
            $sDescription = ":" . daddslashes($sDescription);
        }
        $fMoney = round(floatval($fMoney), 4); //资金格式化
        $this->oDB->doTransaction();
        if (FALSE == $this->switchLock($iUserId, 0, TRUE, '管理员扣减')) {//账户被锁
            $this->oDB->doRollback();
            return -2;
        }
        $this->oDB->doCommit();
        $oOrder = new model_orders();
        $oUser = new model_user();
        $aUserfund = $this->getFundByUser($iUserId, "", 0, FALSE);
        if ($oUser->isTopProxy($iUserId)) {//总代部分
            $fUserTeam = $oUser->getTeamBank($iUserId); //团队资金
            //$aUserCredit = $this->getUserCredit($iUserId);//信用资金
            //$fUserc      = (!empty($aUserCredit)) ? $aUserCredit['proxyvalue']:0;
            $fUserMax = min(($fUserTeam - $aUserfund['creditbalance']), $aUserfund['availablebalance']);
            if ($fUserMax < $fMoney) {
                $this->switchLock($iUserId, 0, FALSE);  //解锁资金表
                return -3; //用户资金不足
            }
            $iOrderType = ORDER_TYPE_XTKJ;
            $sDescription = "管理员扣减" . $sDescription;
        } else {//非总代
            $fUserMax = $aUserfund['availablebalance'];
            if ($fUserMax < $fMoney) {
                $this->switchLock($iUserId, 0, FALSE); //解锁资金表
                return -3; //用户资金不足
            }
            $iOrderType = ORDER_TYPE_XTKJ;// 系统扣减
            $sDescription = "管理员扣减" . $sDescription;
        }
        $this->oDB->doTransaction();
        $sActionTime = date("Y-m-d H:i:s"); //动作时间
        $aOrders = array();
        $aOrders['iFromUserId'] = $iUserId;
        $aOrders['iToUserId'] = $aData["claimtype"];//获取系统扣减中的某一个小类别[ 4 = 人工取款  5 = 手动申请出款]
        $aOrders['iOrderType'] = $iOrderType;
        $aOrders['fMoney'] = $fMoney;
        $aOrders['sActionTime'] = $sActionTime;
        $aOrders['sDescription'] = $sDescription;
        $aOrders['iAdminId'] = $iAdminId;
        $aOrders['iChannelID'] = $iChannelId;
        $mResult = $oOrder->addOrders($aOrders);
        unset($aOrders);
        if (TRUE !== $mResult) {
            $this->oDB->doRollback();
            $this->switchLock($iUserId, 0, FALSE); //解锁资金表
            return $mResult; //相关的返回值(参考orders)
        }

        /*todo 增加更新数据表事务*/
        $iCheckF = $this->UpdateOneConfirmById($aData); // 原子性操作1
        if ($iCheckF !== TRUE) {
            $this->oDB->doRollback();
            $this->switchLock($iUserId, 0, FALSE);  //解锁资金表
            return $iCheckF; //相关的返回值(参考orders)
        }

        $this->oDB->doCommit();
        //提交事务
        if (FALSE == $this->switchLock($iUserId, 0, FALSE)) {
            return -4;
        }
        return 1;
    }

    /**
     * 根据用户出款时间获取出款前所有人工入款金额
     * @date 2017-07-18
     * @author pierce
     * @param $iUserid
     * @param $applytime
     * @return array
     */
    public function getAllMoneyByUserId($iUserid,$applytime){
        $sSql = "SELECT SUM(amount + ext_amount) AS allmoney FROM manualpay_confirm WHERE FIND_IN_SET({$iUserid}, user_ids) AND optype = 0 AND updatetime < '{$applytime}'";
        return $this->oDB->getOne($sSql);
    }

    /**
     * 获取用户金钱
     * @param $iUserid
     * @return array
     */
    public function getUserFundInfoByUserId($iUserid){
        return $this->oDB->getOne("SELECT * FROM userfund WHERE userid = {$iUserid}");
    }

}