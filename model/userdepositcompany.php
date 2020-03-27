<?php

/**
 * @desc 用户充值(商户公司账号收款)
 * @author Ben
 * @date 2017-07-07
 *
 */
class model_userdepositcompany extends basemodel {
    /**
     * 状态
     * @var array
     */
    public static $STATUS = [
        0 => '未处理',
        1 => '已取消',
        2 => '已存入'
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
        if (!empty($aData['company_payacc_id'])) {
            $sCondition .= " AND `c`.`id` in (${aData['company_payacc_id']})";
        }

        if (!empty($aData['min'])) {
            $sCondition .= " AND `a`.`apply_amount` >= '${aData['min']}'";
        }
        if (!empty($aData['max'])) {
            $sCondition .= " AND `a`.`apply_amount` <= '${aData['max']}'";
        }

        if (!empty($aData['key_word']) && !empty($aData['search_type'])) {
            switch ($aData['search_type']) {
                case 1:
                    // 会员账号
                    $sCondition .= " AND `a`.`username` = '${aData['key_word']}'";
                    break;
                case 2:
                    // 存款人
                    $sCondition .= " AND `a`.`apply_realname` = '${aData['key_word']}'";
                    break;
                case 3:
                    // 附言码
                    $sCondition .= " AND `a`.`notes` = '${aData['key_word']}'";
                    break;
                case 4:
                    // 订单号
                    $sCondition .= " AND `a`.`company_order_no` = '${aData['key_word']}'";
                    break;
            }
        }

        if (!empty($aData['layerid']) || '0' === $aData['layerid']) {
            $sCondition .= " AND `a`.layerid in (${aData['layerid']})";
        }

        $sTableName = '`user_deposit_company` `a` 
        LEFT JOIN `user_layer` `b` ON (`a`.`layerid` =`b`.`layerid` AND `a`.`lvtopid`=`b`.`lvtopid`) 
        LEFT JOIN `proxy_pay_acc` `c` ON (`a`.`company_payacc_id` = `c`.`id` AND `a`.`lvtopid`=`c`.`lvtopid`)';
        $sFields = '`a`.*, `b`.`name` AS `layer_name`,`c`.`nickname`,`c`.`accout_no`';
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
            foreach ($aPage['results'] as $key=>$item) {
                if ('0' == $item['status']) {
                    $aUserInfo = $oUser->getUserInfo($item['userid'], ['rechargetimes'], " AND `lvtopid`='${iLvtopid}'");
                    if (!$aUserInfo) {
                        break;
                    }
                    $bIsFirst = empty($aUserInfo['rechargetimes']) ? 1 : 2;
                    $aPayInfo = $oPaySet->getFreeById($iLvtopid, $item['userid'], $bIsFirst, $item['apply_amount']);
                    if (!$aPayInfo) {
                        break;
                    }
                    $aPage['results'][$key]['favor_amount'] = $aPayInfo['free'] <= 0 ? '0.00' : sprintf("%.2f", $aPayInfo['free']);
                }
            }
        }
        $aPage['total'] = $this->oDB->getOne("SELECT  count(`a`.`id`) as count,sum(`a`.`apply_amount`) as sum ,sum(`a`.`real_amount`) as sum_real_amount,sum(`a`.`favor_amount`) as sum_favor_amount FROM ${sTableName} WHERE ${sCondition}");
        return $aPage;
    }


    /**
     * 申请确认
     * @author Ben
     * @date 2017-07-14
     * @param $iLvtopid
     * @param $iId
     * @param $iAdminId
     * @param $sAdminName
     * @return bool|string
     */
    public function confirm($iLvtopid, $iId, $iAdminId, $sAdminName,$apply_amount,$favor_amount) {
        $this->oDB->doTransaction();
        try {
            $aApplyInfo = $this->oDB->getOne("SELECT * FROM `user_deposit_company` WHERE `lvtopid`='${iLvtopid}' AND `id`='${iId}' LIMIT 1");
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
                $this->oDB->doRollback();
                return '消息返回: 账户资金临时被锁, 请稍后再试';
            }

            // 写入充值成功账变
            $oOrder = new model_orders();
            $fRealMoney = 0; // 实际上分金额

            // 计算实际上分金额
            /* @var $oPaySet model_payset */
            $oPaySet = a::singleton('model_payset');

            $bIsFirst =  empty($aUserInfo['rechargetimes']) ? 1 : 2;
            $aPayInfo = $oPaySet->getFreeById($iLvtopid, $aUserInfo['userid'], $bIsFirst, $apply_amount);
            if (false === $aPayInfo) {
                // 计算入款信息错误，回滚数据库
                $this->oDB->doRollback();
                return '计算入款信息错误';
            }
            $aApplyInfo['apply_amount'] = $apply_amount; //确认之后的金额
            $aPayInfo['free'] = $favor_amount; //确认之后的优惠金额
            $fRealMoney = $aApplyInfo['apply_amount'] + $aPayInfo['free'];

            // 添加稽核记录
            $aUserBetsCheck = [
                'lvtopid' => $iLvtopid,
                'userid' => $aUserInfo['userid'],
                'amount' => $aApplyInfo['apply_amount'],
                'ext_amount' => $aPayInfo['free'],
                'need_bets' => $aPayInfo['recover'],
                'reduce_bets' => $aPayInfo['reduce'],
                'ext_bets' => $aPayInfo['comprehensive'],
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
            $aOrders['sDescription'] = '[公司入款]';
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
            if ($aApplyInfo['apply_amount'] > $aUserInfo['loadmax']) {
                $this->oDB->query("UPDATE `users` SET `loadmoney`= `loadmoney` + ${aApplyInfo['apply_amount']}, `rechargetimes`=`rechargetimes`+1,`loadmax`=${aApplyInfo['apply_amount']} WHERE `userid`='${aApplyInfo['userid']}' AND `loadmoney` ='${aUserInfo['loadmoney']}' AND   `rechargetimes` = ${aUserInfo['rechargetimes']}");
            } else {
                $this->oDB->query("UPDATE `users` SET `loadmoney`= `loadmoney` + ${aApplyInfo['apply_amount']}, `rechargetimes`=`rechargetimes`+1 WHERE `userid`='${aApplyInfo['userid']}' AND `loadmoney` ='${aUserInfo['loadmoney']}' AND   `rechargetimes` = ${aUserInfo['rechargetimes']}");
            }
            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 增加用户累计充值总额失败
                $this->oDB->doRollback();
                $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE);
                return '增加用户累计充值总额失败';
            }

            $sfinishtime = date( "Y-m-d H:i:s", time());
            // 更新申请记录状态
            if (0 > $aPayInfo['free']) {
                $aPayInfo['free'] =  abs($aPayInfo['free']);
                $this->oDB->query("UPDATE `user_deposit_company` SET `status` = 2,`proxy_adminid`='${iAdminId}',`proxy_adminname`='${sAdminName}',`real_amount`='".$aApplyInfo['apply_amount']."',`charge`=${aPayInfo['free']},`finishtime`='{$sfinishtime}'  WHERE `id` = '${iId}' AND `lvtopid`='${iLvtopid}' AND `status` = '0'");
            } else {
                $this->oDB->query("UPDATE `user_deposit_company` SET `status` = 2,`proxy_adminid`='${iAdminId}',`proxy_adminname`='${sAdminName}',`real_amount`='".$aApplyInfo['apply_amount']."',`favor_amount`=${aPayInfo['free']},`finishtime`='{$sfinishtime}'  WHERE `id` = '${iId}' AND `lvtopid`='${iLvtopid}' AND `status` = '0'");
            }

            if (!$this->oDB->ar() || $this->oDB->ar() != 1) { // 更新申请记录状态失败
                $this->oDB->doRollback();
                $oUserFund->switchLock($aApplyInfo['userid'], 0, FALSE);
                return '更新申请记录状态失败';
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
     * desc 修改用户金额之后计算优惠金额
     * @author rhovin 2017-08-28
     * @return favor_money 优惠金额
     */
    public function ChangeApplyAmount($iLvtopid, $iId,$apply_amount) {
        $aApplyInfo = $this->oDB->getOne("SELECT * FROM `user_deposit_company` WHERE `lvtopid`='${iLvtopid}' AND `id`='${iId}' LIMIT 1");
        if (!$aApplyInfo) {
            return FALSE;
        }

        $aUserInfo = $this->oDB->getOne("SELECT * FROM `users` WHERE `lvtopid`='{$iLvtopid}' AND  `userid`='${aApplyInfo['userid']}' LIMIT 1");
        if (!$aUserInfo) {
            return FALSE;
        }
         /* @var $oPaySet model_payset */
        $oPaySet = a::singleton('model_payset');

        $bIsFirst =  empty($aUserInfo['rechargetimes']) ? 1 : 2;
        $aPayInfo = $oPaySet->getFreeById($iLvtopid, $aUserInfo['userid'], $bIsFirst, $apply_amount);
        return $aPayInfo['free'];

    }

    /**
     * 取消
     * @author Ben
     * @date 2017-07-14
     * @param $iLvtopid
     * @param $iId
     * @param $rejectRemark
     * @return bool;
     */
    public function cancel($iLvtopid, $iId, $iAdminid, $sAdminname, $rejectRemark) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid) || empty($iId) || !is_numeric($iId)) {
            return false;
        }
        $sfinishtime = date( "Y-m-d H:i:s", time());
        $this->oDB->query("UPDATE `user_deposit_company` SET `status` = 1,`proxy_adminid` = '${iAdminid}',`proxy_adminname` = '${sAdminname}',`finishtime` = '${sfinishtime}', `reject_remark` = '{$rejectRemark}' WHERE `lvtopid`='${iLvtopid}' AND `id`= '${iId}' AND `status` = 0");
        return $this->oDB->ar();
    }
    /**
     * 撤销取消
     * @author Rhovin
     * @date 2017-07-14
     * @param $iLvtopid
     * @param $iId
     * @return bool;
     */
    public function backCancel($iLvtopid, $iId, $iAdminid, $sAdminname) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid) || empty($iId) || !is_numeric($iId)) {
            return false;
        }
        $sfinishtime = date( "Y-m-d H:i:s", time());
        //echo "UPDATE `user_deposit_company` SET `status` = 0,`proxy_adminid` = '${iAdminid}',`proxy_adminname` = '${sAdminname}',`finishtime` = '${sfinishtime}' WHERE `lvtopid`='${iLvtopid}' AND `id`= '${iId}' AND `status` = 1";exit;
        $this->oDB->query("UPDATE `user_deposit_company` SET `status` = 0,`proxy_adminid` = '${iAdminid}',`proxy_adminname` = '${sAdminname}',`finishtime` = '${sfinishtime}' WHERE `lvtopid`='${iLvtopid}' AND `id`= '${iId}' AND `status` = 1");
        return $this->oDB->ar();
    }
}