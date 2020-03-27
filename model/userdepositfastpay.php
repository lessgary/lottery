<?php

/**
 * @desc 三方公司入款记录
 * @author Ben
 * @date 2017-07-14
 *
 */
class model_userdepositfastpay extends basemodel {

    public static $status = [
        '0' => '未处理',
        '1' => '已处理',
        '2' => '成功',
        '3' => '人工取消',
        '4' => '人工确认'
    ];

    /**
     * 获取列表
     * @author Ben
     * @date 2017-07-07
     * @param $iLvtopid
     * @param $aData
     * @return bool|mixed
     */
    public function getList($iLvtopid, $aData) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid)) {
            return false;
        }
        $sCondition = "`a`.`lvtopid`='${iLvtopid}'";

        if (!empty($aData['starttime'])) {
            $sCondition .= " AND `a`.`inserttime` >= '${aData['starttime']}'";
        }
        if (!empty($aData['endtime'])) {
            $sCondition .= " AND `a`.`inserttime` <= '${aData['endtime']}'";
        }
        if (!empty($aData['sfinishtime'])) {
            $sCondition .= " AND `a`.`finishtime` >= '${aData['sfinishtime']}'";
        }
        if (!empty($aData['efinishtime'])) {
            $sCondition .= " AND `a`.`finishtime` <= '${aData['efinishtime']}'";
        }

        if (-1 != $aData['status']) {
            $sCondition .= " AND `a`.`status` ='${aData['status']}'";
        }
        if (-1 != $aData['company_fastpayacc_id']) {
            $sCondition .= " AND `c`.`id` ='${aData['company_fastpayacc_id']}'";
        }

        if (!empty($aData['min'])) {
            $sCondition .= " AND `a`.`apply_amount` >= '${aData['min']}'";
        }
        if (!empty($aData['max'])) {
            $sCondition .= " AND `a`.`apply_amount` <= '${aData['max']}'";
        }

        if (-1 != $aData['paytypeid']) {
            $sCondition .= " AND `a`.`paytypeid` ='${aData['paytypeid']}'";
        }

        if (!empty($aData['key_word']) && !empty($aData['search_type'])) {
            switch ($aData['search_type']) {
                case 1:
                    // 会员账号
                    $sCondition .= " AND `a`.`username` = '${aData['key_word']}'";
                    break;
                case 2:
                    // 订单号
                    $sCondition .= " AND `a`.`company_order_no` = '${aData['key_word']}'";
                    break;
                case 3:
                    // 操作者
                    $sCondition .= " AND `a`.`proxy_adminname` = '${aData['key_word']}'";
                    break;
            }
        }

        if (!empty($aData['layerid']) || '0' === $aData['layerid']) {
            $sCondition .= " AND `a`.layerid in (${aData['layerid']})";
        }

        $sTableName = '`user_deposit_fastpay` `a` 
        LEFT JOIN `user_layer` `b` ON (`a`.`layerid` =`b`.`layerid` AND `a`.`lvtopid`=`b`.`lvtopid`) 
        LEFT JOIN `proxy_fastpay_acc` `c` ON (`a`.`company_fastpayacc_id` = `c`.`id` AND `a`.`lvtopid`=`c`.`lvtopid`)';
        $sFields = '`a`.*, `b`.`name` AS `layer_name`,`c`.`nickname` as fastpay_acc_name';
        $iPageRecords = empty($aData['rows']) ? 25 : $aData['rows'];
        $iCurrPage = empty($aData['page']) ? 1 : $aData['page'];
        $aPage = $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecords,
            $iCurrPage, $sOrderby = 'ORDER BY `a`.`id` DESC', $sUseIndex = '', $sCountSql = '');
        if (!empty($aPage['results'])) {
            $oUser = new model_user();
            $oPaySet = new model_payset();
            foreach ($aPage['results'] as &$v) {
                if ($v['finishtime'] == "0000-00-00 00:00:00") {
                    $v['finishtime'] = "";
                }
            }
            foreach ($aPage['results'] as $key => $item) {
                if ('0' == $item['status']) {
                    $aUserInfo = $oUser->getUserInfo($item['userid'], ['rechargetimes'], " AND `lvtopid`='${iLvtopid}'");
                    if (!$aUserInfo) {
                        break;
                    }
                    $bIsFirst = empty($aUserInfo['rechargetimes']) ? 1 : 2;
                    $aPayInfo = $oPaySet->getOnlineFreeById($iLvtopid, $item['userid'], $bIsFirst, $item['apply_amount']);
                    if (!$aPayInfo) {
                        break;
                    }
                    $aPage['results'][$key]['real_amount'] = $item['apply_amount'] + $aPayInfo['free'];
                    $aPage['results'][$key]['charge'] = $aPayInfo['free'] < 0 ? sprintf("%.2f", abs($aPayInfo['free'])) : '0.00';
                }
            }
        }

        if ($aData) {
            foreach ($aPage['results'] as &$item) {
                if (array_key_exists($item['status'], self::$status)) {
                    $item['status_msg'] = self::$status[$item['status']];
                }

            }
        }
         $aPage['total'] = $this->oDB->getOne("SELECT count(`a`.`id`) AS total_record,SUM(`apply_amount`) AS sum_apply_amount ,SUM(`real_amount`) AS sum_real_amount FROM $sTableName WHERE $sCondition"); 
        return $aPage;
    }

    /**
     * 人工确认
     * @author Ben
     * @date 2017-07-14
     * @param $iLvtopid
     * @param $iId
     * @param $iAdminId
     * @param $sAdminName
     * @param $sRemark
     * @param $fRealMoney
     * @return bool|string
     */
    public function confirm($iLvtopid, $iId, $iAdminId, $sAdminName, $sRemark, $fRealMoney = null, $sFastpayOrderNo = null) {
        $this->oDB->doTransaction();
        try {
            $aApplyInfo = $this->oDB->getOne("SELECT * FROM `user_deposit_fastpay` WHERE `lvtopid`='${iLvtopid}' AND `id`='${iId}' LIMIT 1");
            if (!$aApplyInfo) {
                $this->oDB->doRollback();
                return '记录不存在';
            }
            if (!is_null($fRealMoney) && is_numeric($fRealMoney)) {
                $aApplyInfo['apply_amount'] = $fRealMoney;
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
                $this->oDB->doRollback();
                return '消息返回: 账户资金临时被锁, 请稍后再试';
            }

            // 写入充值成功账变
            $oOrder = new model_orders();
            $fRealMoney = 0; // 实际上分金额

            // 计算实际上分金额
            /* @var $oPaySet model_payset */
            $oPaySet = a::singleton('model_payset');
            $bIsFirst = empty($aUserInfo['rechargetimes']) ? 1 : 2;
            $aPayInfo = $oPaySet->getOnlineFreeById($iLvtopid, $aUserInfo['userid'], $bIsFirst, $aApplyInfo['apply_amount']);
            if (!is_array($aPayInfo)) {
                // 计算入款信息错误，回滚数据库
                $this->oDB->doRollback();
                return '计算入款信息错误,' . strval($aPayInfo);
            }
            $fRealMoney = $aApplyInfo['apply_amount'] + $aPayInfo['free'];

            // 添加稽核记录
            $aUserBetsCheck = [
                'lvtopid' => $iLvtopid,
                'userid' => $aUserInfo['userid'],
                'amount' => $aApplyInfo['apply_amount'],
                'ext_amount' => $aPayInfo['free'], // 优惠
                'need_bets' => $aPayInfo['recover'], // 普通打码
                'reduce_bets' => $aPayInfo['reduce'], // 放宽额度
                'ext_bets' => $aPayInfo['comprehensive'], // 综合打码
            ];
            if (false === $this->oDB->insert('user_bets_check', $aUserBetsCheck)) {
                $this->oDB->doRollback();
                return '添加稽查数据失败';
            }

            $aOrders = array();
            $aOrders['iFromUserId'] = $aUserTreeInfo['userid'];
            $aOrders['iOrderType'] = ORDER_TYPE_CZCG;
            $aOrders['fMoney'] = $aApplyInfo['apply_amount']; //$aRecharge['amount'];
            $aOrders['sActionTime'] = $sNowTime;
            $aOrders['sDescription'] = $iAdminId ? '[三方入款]手动补单' : '[三方入款]';
            $aOrders['iAdminId'] = $iAdminId;
            $result = $oOrder->addOrders($aOrders);
            unset($aOrders);
            if (TRUE !== $result) { // 账变发生异常, 事务回滚
                $this->oDB->doRollback();
                $oUserFund->switchLock($aUserTreeInfo['userid'], 0, FALSE);
                return '账变发生异常';
            }

            if (!empty($aPayInfo['free'])) {
                $aOrders = array();
                $aOrders['iFromUserId'] = $aUserTreeInfo['userid'];
                $aOrders['iOrderType'] = $aPayInfo['free'] > 0 ? ORDER_TYPE_HDHB : ORDER_TYPE_CZKF;
                $aOrders['fMoney'] = $aPayInfo['free'] > 0 ? $aPayInfo['free'] : -$aPayInfo['free']; //$aRecharge['amount'];
                $aOrders['sActionTime'] = $sNowTime;
                $aOrders['sDescription'] = $aPayInfo['free'] > 0 ? '[活动礼金]' : '[充值扣费]';
                $aOrders['iAdminId'] = $iAdminId;
                $result = $oOrder->addOrders($aOrders);
                unset($aOrders);
                if (TRUE !== $result) { // 账变发生异常, 事务回滚
                    $this->oDB->doRollback();
                    $oUserFund->switchLock($aUserTreeInfo['userid'], 0, FALSE);
                    return '账变发生异常';
                }
            }

            // 增加用户累计充值总额
            if ($fRealMoney > $aUserInfo['loadmax']) {
                $this->oDB->query("UPDATE `users` SET `loadmoney`= `loadmoney` + ${fRealMoney},`rechargetimes`=`rechargetimes`+1,`loadmax`=${fRealMoney} WHERE `userid`='${aApplyInfo['userid']}' AND `loadmoney` ='${aUserInfo['loadmoney']}' AND `rechargetimes`=${aUserInfo['rechargetimes']}");
            } else {
                $this->oDB->query("UPDATE `users` SET `loadmoney`= `loadmoney` + ${fRealMoney},`rechargetimes`=`rechargetimes`+1 WHERE `userid`='${aApplyInfo['userid']}' AND `loadmoney` ='${aUserInfo['loadmoney']}' AND `rechargetimes`=${aUserInfo['rechargetimes']}");
            }

            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 增加用户累计充值总额失败
                $this->oDB->doRollback();
                $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE);
                return '增加用户累计充值总额失败';
            }

            // 更新申请记录状态
            $status = empty($iAdminId) ? 2 : 4;
            $sRemark = empty($iAdminId) ? '三方自动充值成功' : $sRemark;
            $sFastpayOrderNo = empty($sFastpayOrderNo) ? '' : $this->oDB->real_escape_string($sFastpayOrderNo);
            $sfinishtime = date( "Y-m-d H:i:s", time());
            if (0 > $aPayInfo['free']) {
                $aPayInfo['free'] =  abs($aPayInfo['free']);
                $this->oDB->query("UPDATE `user_deposit_fastpay` SET `status` = '${status}',`proxy_adminid`='${iAdminId}',`proxy_adminname`='${sAdminName}',`real_amount`=${fRealMoney},`charge`=${aPayInfo['free']},`remark`='${sRemark}',`fastpay_order_no`='${sFastpayOrderNo}',`finishtime`='${sfinishtime}'  WHERE `id` = '${iId}' AND `lvtopid`='${iLvtopid}' AND `status` = '0'");
            } else {
                $this->oDB->query("UPDATE `user_deposit_fastpay` SET `status` = '${status}',`proxy_adminid`='${iAdminId}',`proxy_adminname`='${sAdminName}',`real_amount`=${fRealMoney},`favor_amount`=${aPayInfo['free']},`remark`='${sRemark}',`finishtime`='${sfinishtime}'  WHERE `id` = '${iId}' AND `lvtopid`='${iLvtopid}' AND `status` = '0'");
            }

            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                $this->oDB->doRollback();
                $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE);
                return '更新申请记录状态失败';
            }
            //更新三方账号额度
            $this->oDB->query("UPDATE `proxy_fastpay_acc` SET `current_quota`= `current_quota` + ${fRealMoney} WHERE `id`='${aApplyInfo['company_fastpayacc_id']}'");
            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                $this->oDB->doRollback();
                $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE);
                return '增加三方账号额度失败';
            }
            // 账户解锁
            if (1 != $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE)) {
                $this->oDB->doRollback();
                return '资金账户解锁失败'; // 资金账户解锁失败
            }

            $this->oDB->doCommit(); // 事务提交
            return TRUE; // 中断程序执行
        } catch (Exception $e) {
            $this->oDB->doRollback();
            return FALSE;
        }
    }

    /**
     * 人工取消申请
     * @author Ben
     * @date 2017-07-14
     * @param $iLvtopid
     * @param $iId
     * @param $sRemark
     * @return bool|int
     */
    public function cancel($iLvtopid, $iId, $iAdminid, $sAdminname, $sRemark) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid) || empty($iId) || !is_numeric($iId)) {
            return false;
        }
        $sfinishtime = date( "Y-m-d H:i:s", time());
        $this->oDB->query("UPDATE `user_deposit_fastpay` SET `status` = 3,`proxy_adminid` = '${iAdminid}',`proxy_adminname` = '${sAdminname}',`remark` = '${sRemark}',`finishtime` = '${sfinishtime}' WHERE `lvtopid`='${iLvtopid}' AND `id`= '${iId}' AND `status` = 0");
        return $this->oDB->ar();
    }

    public function getAllArtificialMoneyById($iUserid, $iLvtopid, $sTime) {
        $sSql = "SELECT SUM(real_amount) AS real_amount FROM user_deposit_fastpay WHERE `status` = 4 AND userid = '" . $iUserid . "' AND lvtopid = '" . $iLvtopid . "' AND updatetime < '" . $sTime . "'";
        return $this->oDB->getOne($sSql);

    }

    //获取用戶充值一条记录
    public  function  getUserDepositFastpayOne($iLvtopid, $iId){
        $aApplyInfo = $this->oDB->getOne("SELECT * FROM `user_deposit_fastpay` WHERE `lvtopid`='${iLvtopid}' AND `id`='${iId}' LIMIT 1");
        if (!$aApplyInfo) {
            return false;
        }
        return $aApplyInfo;
    }

}