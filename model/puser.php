<?php 

/**
 * @desc 商户端用户模型
 * @author rhovin
 * @date 2017-05-027
 *
 */
class model_puser extends model_user {
    /**
     * @desc 更新用户是否锁定层级状态
     * @date 2017-06-22
     * @author rhovin
     */
    public function lockUserLayer($iUserId,$iIslocklayer) {
        $sSql = " UPDATE `users` SET `islocklayer`='" . $iIslocklayer . "' WHERE `userid`='" . $iUserId . "'  ";
        $this->oDB->query($sSql);
        if ($this->oDB->errno() > 0) { // 修改失败
            return FALSE;
        }else {
            return true ;
        }
    }
    /** 
     * @desc 获取单个用户配额列表
     * @author rhovin
     * @date 2017-06-23
     */
    public function getUserQuota($iUserId,$point ,$lvtopid){
        $sSql = "SELECT * FROM `user_accquota` WHERE `userid`='".$iUserId."' AND `point`='".$point."' AND `lvtopid`='".$lvtopid."' ";
        return $this->oDB->getOne($sSql);
    }
    
    /**
     * @desc 获取用户类型
     * @author pierce
     * @date 2017-06-27
     */
    public function getUserTypeByUserId($iUserId,$lvtopid){
        $sSql = "SELECT usertype FROM `usertree` WHERE `userid`='".$iUserId."'AND `lvtopid`='".$lvtopid."' ";
        return $this->oDB->getOne($sSql);
    }
    /**
     * @desc 获取用户安全信息审核资料列表
     * @author rhovin
     * @date 2017-06-24
     */
    public function getUserReviewList($lvtopid,$sAndWhere = '',$iCurrPage = 1,$iPageRecords = 20,$sOrderBy = '') {
        $sTableName = " `proxy_apply_confirm` AS pac  "
                . " LEFT JOIN `users` AS u ON u.`userid` = pac.`userid` "
                . " LEFT JOIN `usertree` AS ut ON ut.`userid` = pac.`userid` "
                . " LEFT JOIN `proxyuser` AS p ON p.`proxyadminid` = pac.`apply_adminid` "
                . " LEFT JOIN `proxyuser` AS pu ON pu.`proxyadminid`=pac.`confirm_adminid` ";
        $sFields = "pac.id,pac.apply_ip,pac.apply_info, pac.type,pac.remark,pac.isconfirm, pac.inserttime,pac.updatetime,u.`username`,p.adminname applyadmin,pu.adminname confirmadmin";
        $sAndWhere .= " AND pac.lvtopid='".$lvtopid."' AND ut.`isdeleted`='0'";
       
        $sCondition = 1 ;
        $sCondition .= $sAndWhere;
        $sCountSql = '';
        $result = $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '', $sCountSql);
        return $result;
    }
    /**
     * @desc 添加用户安全信息修改数据
     * @author rhovin
     * @date 2017-06-24
     */
    public function addReview($aData) {
        $this->oDB->insert('proxy_apply_confirm', $aData);
        if ($this->oDB->ar() < 1) { // 写入usertree表失败
            return FALSE;
        } else {
            return TRUE;
        }
    }
    /**
     * @desc 单条安全信息资料查询
     * @author rhovin
     * @date 2017-06-24
     */
    public function getOneReview($iId , $lvtopid) {
        $sSql = "SELECT * FROM proxy_apply_confirm WHERE id='".$iId."' AND lvtopid='".$lvtopid."'";
        return $this->oDB->getOne($sSql);
    }
    /**
     * @update 更新安全信息审核记录
     * @author rhovin
     * @date 2017-06-26
     */
    public function updateReview($iId, $lvtopid,$aUpData , $apply_data = []) {
        $this->oDB->doTransaction();
        try {
            $sWhere = "`id`='{$iId}' AND `lvtopid`='{$lvtopid}' AND `isconfirm`=0";
            $this->oDB->update('proxy_apply_confirm',$aUpData, $sWhere);
            if ($this->oDB->ar() != 1) { // 更新登录时间失败
                throw new Exception("审核失败，当前记录已完成操作");
            } else {
                if(!empty($apply_data)) {
                    if($aUpData['type'] == 5) { //更新银行卡信息
                        $oBankCard = new model_bankcard();
                        $aResult = $oBankCard->updateUserBank($apply_data);
                        if ($aResult == FALSE) {
                            throw new Exception("审核失败");
                        }
                    } elseif(6 == $aUpData['type']){
                        // 修改上级
                        $oUser = new model_user();
                        if(empty($apply_data['userid']) || empty($apply_data['new'])) {
                            throw new Exception('参数有误，请重新操作');
                        }
                        $iUserId = intval($apply_data['userid']);
                        $iNewParentId = intval($apply_data['new']);
                        $iFlag =  $oUser->changeParent($iUserId, $iNewParentId, $lvtopid);
                        if($iFlag !== TRUE) {
                            switch ($iFlag) {
                                case -1:
                                    throw new Exception('修改失败：目标用户自身返点不得超过新上级的返点');break;
                                case -2:
                                case -3:
                                    throw new Exception('用户数据异常，请联系管理员。CODE:'. $iFlag);break;
                                case -4:
                                    throw new Exception('修改失败：不能将目标用户的下级调整为目标用户的上级');break;
                                case -5:
                                    throw new Exception('修改失败：下级代理等级超过平台最大等级限制');break;
                                case -6:
                                default:
                                    throw new Exception('更新异常，请联系管理员。CODE:'. $iFlag);break;
                            }
                        }
                    } else { //更新用户信息
                        $aUserInfo = $this->getUserInfo(intval($apply_data['userid']),['nickname','username','realname','remark','qq','mobile','email','securitypwd','loginpwd']);
                        //$bResult = $this->_checkForm($aGetData);
                       if(isset($apply_data['loginpwd']) && $apply_data['loginpwd'] != "") {
                            $apply_data['loginpwd'] = MD5($apply_data['loginpwd']);
                        }
                       if(isset($apply_data['securitypwd']) && $apply_data['securitypwd'] != "") {
                            $apply_data['securitypwd'] = MD5($apply_data['securitypwd']);
                        }
                        //需要更新的数据存在，并且提交的数据不为空并且有变化的数据才更新
                        $aData = [] ;
                        foreach ($apply_data as $key => $value) {
                            if( isset($aUserInfo[$key])) {
                                 if($aUserInfo[$key] == $apply_data[$key] || $apply_data[$key] == "" ) {
                                    continue ;
                                 } else {
                                    $aData[$key] = $value; 
                                 }
                            }
                        }
                        if(empty($aData)){
                            throw new Exception("没有需要更新的数据");
                        }
                        //基本资料修改
                        $mResult = $this->updateUser($apply_data['userid'] , $aData);
                        if($mResult){
                            if(isset($aUpData['type']) && $aUpData['type'] == 2) { //如果是修改真实姓名同时修改银行卡用户名
                                $sSql = "UPDATE userbankinfo  SET `realname` ='${apply_data['realname']}' WHERE userid='${apply_data['userid']}'";
                                $this->oDB->query($sSql);
                                if($this->oDB->errno > 0){
                                    throw new Exception("更新用户银行卡姓名失败");
                                }
                            }
                        }else{
                            throw new Exception("更新用户数据失败");
                        }
                    }
                }
            }
            $this->oDB->doCommit();
            return TRUE;
        } catch (Exception $e) {
            $this->_errMsg = $e->getMessage();
            $this->oDB->doRollback();
            return FALSE;
        }
    }
    /**
     * @desc 获取有效会员列表
     * @author rhovin
     * @date 2017-06-27
     */
    public function getValidUserList($lvtopid, $sAndWhere = '', $realbets = 0, $starttime, $endtime) {
        $sSql = "SELECT ut.userid,
                          ut.lvproxyid,
                          u.username,
                (SELECT count(u.userid)
                   FROM users u
                   LEFT JOIN usertree AS ut2 USING(userid)
                   WHERE ut2.lvproxyid=ut.lvproxyid
                       AND u.registertime >= '${starttime}'
                       AND u.registertime <'${endtime}') AS 'count_new_user',-- 新增用户数

                (SELECT IFNULL(sum(up.realpayment),0)
                   FROM user_report up
                   WHERE up.lvproxyid=ut.lvproxyid
                       AND `day` >= '${starttime}'
                       AND `day` <= '${endtime}' ) AS 'count_charge', -- 总充值

                (SELECT IFNULL(sum(up.realwithdraw),0)
                   FROM user_report up
                   WHERE up.lvproxyid=ut.lvproxyid
                       AND `day` >= '${starttime}'
                       AND `day` <= '${endtime}' ) AS 'count_withdraw', -- 总提现

                (SELECT COUNT(userid)
                    FROM
                        (SELECT userid,
                            lvproxyid,
                            sum(realbets) total_realbets
                        FROM user_report WHERE lvtopid ='${lvtopid}'
                        AND `day` >= '${starttime}'
                        AND `day` <= '${endtime}'
                        GROUP BY userid) t
                    WHERE t.lvproxyid=ut.lvproxyid
                     AND t.total_realbets > '${realbets}' ) AS 'count_valid_user' -- 有效会员
                FROM usertree ut
                LEFT JOIN users u USING(userid)
                WHERE ut.userid=ut.lvproxyid AND ut.`isdeleted`='0'
                  AND ut.lvtopid = '${lvtopid}' ${sAndWhere};";
        return $this->oDB->getAll($sSql);
    }

    /** 
     * @desc 用户资金统计
     * @author rhovin
     * @date 2017-06-28
     */
    public function getUserMoneyStatistics($lvtopid, $sAndWhere = '', $iCurrPage = 1, $iPageRecords = 20, $sOrderBy = '') {
        $oPreport = new model_preport();
        $sTableName = " `usertree` AS ut  "
                . " LEFT JOIN `users` AS u ON u.`userid` = ut.`userid` "
                . " LEFT JOIN `userfund` AS uf ON ut.`userid`=uf.`userid` ";
        $sFields = "ut.userid,ut.username,ut.parenttree,ut.usertype,ut.`isdeleted`,ut.username,(SELECT username FROM users where userid = ut.lvproxyid AND ut.`isdeleted`=0) as proxyname,uf.channelbalance,u.realname,if(u.`lasttime`<='1970-01-01 00:00:00',u.`registertime`,u.`lasttime`) as lasttime,u.registertime,u.rechargetimes,u.withdrawaltimes,u.totalwithdrawal,u.totalactivity,u.loadmoney";
        $sAndWhere .= " AND ut.lvtopid='".$lvtopid."' AND ut.`isdeleted`='0'";
        $sCondition = 1 ;
        $sCondition .= $sAndWhere;
        $sCountSql = '';
        $result = $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '', $sCountSql);
        if (!empty($result['results'])){
            foreach ($result['results'] as $k => &$v) {
                $v['groupname'] = $oPreport->getGroupName($v['usertype'],$v['parenttree']);
            }
        }
        //用户名下级数据
        return $result;
    }
    /**
     * @desc 统计用户总金额
     * @author rhovin
     * @date 2017-06-28
     */

    public function getUserSumStatistics($lvtopid,$sWhere) {
        $sSql = "SELECT sum(uf.channelbalance) allchannelbalance,sum(u.rechargetimes) allrechargetimes, sum(u.withdrawaltimes) allwithdrawaltimes,sum(u.totalwithdrawal) alltotalwithdrawal,sum(u.loadmoney) allloadmoney ,sum(u.totalactivity) alltotalactivity FROM `usertree` ut LEFT JOIN `users` u ON u.userid=ut.userid LEFT JOIN `userfund` uf ON uf.userid=ut.userid WHERE ut.lvtopid='".$lvtopid."'$sWhere";
        return $this->oDB->getOne($sSql);
    }
    /**
     * @desc 出入款统计查询方式
     * @author rhovin
     * @date 2017-06-29
     */
    public function getSelectStatisticsPattern ($key ="",$where = FALSE) {
        $aData = [
            "-1" => "全部" ,
            "0" => "有存款" ,
            "1" => "有取款" ,
            "2" => "有存取款" ,
            "3" => "无存款" ,
            "4" => "无取款" ,
            "5" => "无存取款" ,
        ];
        if(isset($aData[$key]) && !$where){
            return     $aData[$key] ;
        } elseif ($key === "" && $where == FALSE) {
            return $aData;
        } elseif(isset($aData[$key]) && $where == TRUE) {
            switch ($key) {
                case '0':
                    return " AND u.loadmoney > 0 "; 
                    break;
                case '1':
                    return " AND u.totalwithdrawal > 0 ";
                    break;
                case '2':
                    return " AND u.totalwithdrawal > 0 AND u.loadmoney > 0";
                    break;
                case '3':
                    return " AND u.loadmoney = 0";
                    break;
                case '4':
                    return " AND u.totalwithdrawal = 0 ";
                    break;
                case '5':
                return " AND u.totalwithdrawal = 0 AND u.loadmoney = 0";
                    break;
                default:
                    return "";
                    break;
            }
        }
    }
    /**
     * @desc 根据用户名读取id
     * @author rhovin
     * @date 2017-06-29
     * @return  mixed
     */
    public function getIdByUsername($lvtopid, $username,$isDel = 0) {
        if($isDel == 1) {
            $sIsDel = '';
        } else {
            $sIsDel = "AND `isdeleted`='0' ";
        }
        $sSql = "SELECT `userid`,`lvtopid`,`lvproxyid` FROM usertree where lvtopid=${lvtopid} AND username='${username}' ${sIsDel}";
        return $this->oDB->getOne($sSql);
    }


    /**
     * @desc 获取本商户所有一级代理账号
     * @author pierce
     * @date 2017-06-28
     */
    public function getProxyByLvTopId($lvtopid){
        $sSql = "SELECT userid,username FROM `usertree` WHERE `parentid`='".$lvtopid."'";
        return $this->oDB->getAll($sSql);
    }
    /**
     * @desc 根据id获取用户名
     * @author pierce
     * @date 2017-06-28
     */
    public function getUsernameByUserId($userid){
        $sSql = "SELECT username FROM `users` WHERE `userid`='".$userid."'";
        return $this->oDB->getOne($sSql);
    }
   
    /**
     * @desc 获取代理等级
     * @author rhovin
     * @date 2017-07-03
     *
     */
    public function getProxyLevel($key='') {
        $aProxy = [
            "0" => "一级代理",
            "1" => "二级代理",
            "2" => "三级代理",
            "3" => "四级代理",
            "4" => "五级代理",
            "5" => "六级代理",
            "6" => "七级代理",
            "7" => "八级代理",
            "8" => "九级代理",
            "9" => "十级代理",
        ];
        return isset($aProxy[$key]) ? $aProxy[$key] : $aProxy;
    }
    
    /**
     * 获取商户的所有用户数量(包括总代和总代管理员后期更改)
     * @author ken
     * @param $lvtopid
     * @return 该商户下所有用户数量
     */
    public function getAllUser($lvtopid)
    {
        $sSql = " SELECT COUNT(*) AS usernum FROM usertree AS ut LEFT JOIN users AS u USING (userid) WHERE ut.lvtopid = '{$lvtopid}' ";
        $aReturn = $this->oDB->getOne($sSql);
        return $aReturn['usernum'];
    }
    
    /**
     * @desc 增加获取有效会员列表
     * @author ken
     * @date 2017-06-27
     * @return  返回用户信息1新增用户2总充值3总提现4总有效会员5有存款用户
     */
    public function getUserCont($lvtopid, $username = '', $realbets = 0, $starttime, $endtime) {
        $sSql = "SELECT ut.userid,
                          ut.lvproxyid,
                          u.username,
                (SELECT count(u.userid)
                   FROM users u
                   LEFT JOIN usertree AS ut2 USING(userid)
                   WHERE ut2.lvproxyid=ut.lvproxyid
                       AND u.registertime >= '${starttime}'
                       AND u.registertime <'${endtime}') AS 'count_new_user',-- 新增用户数

                (SELECT IFNULL(sum(up.realpayment),0)
                   FROM user_report up
                   WHERE up.lvproxyid=ut.lvproxyid
                       AND up.updatetime >= '${starttime}'
                       AND up.updatetime <= '${endtime}' ) AS 'count_charge', -- 总充值

                (SELECT IFNULL(sum(up.realwithdraw),0)
                   FROM user_report up
                   WHERE up.lvproxyid=ut.lvproxyid
                       AND up.updatetime >= '${starttime}'
                       AND up.updatetime <= '${endtime}' ) AS 'count_withdraw', -- 总提现

                (SELECT COUNT(userid)
                    FROM
                        (SELECT userid,
                            lvproxyid,
                            sum(realbets) total_realbets
                        FROM user_report WHERE lvtopid ='${lvtopid}'
                        AND updatetime >= '${starttime}'
                        AND updatetime <= '${endtime}'
                        GROUP BY userid) t
                    WHERE t.lvproxyid=ut.lvproxyid
                     AND t.total_realbets > '${realbets}' ) AS 'count_valid_user', -- 有效会员
                   (SELECT COUNT(userid)
                    FROM
                        (SELECT userid,
                            lvproxyid,
                            sum(realpayment) total_realpayment
                        FROM user_report WHERE lvtopid ='${lvtopid}'
                        AND updatetime >= '${starttime}'
                        AND updatetime <= '${endtime}'
                        GROUP BY userid) t
                    WHERE t.lvproxyid=ut.lvproxyid
                     AND t.total_realpayment > 0 ) AS 'count_payment_user' -- 有充值用户
                FROM usertree ut
                LEFT JOIN users u USING(userid)
                WHERE ut.userid=ut.lvproxyid
                  AND ut.lvtopid = '${lvtopid}' ${username};";
        return $this->oDB->getAll($sSql);
    }
    /**
     * 获取今日 新注册  有效  入款  入款人数  充值  取款  盈利
     * @author  Mark
     */
    public function getAllToday($lvtopid,$stime='',$etime=''){
        if(empty($stime) || empty($etime)){
            $stime = date("Y-m-d 00:00:00");
            $etime = date("Y-m-d 23:59:59");
        }
        $oProxyConfig = A::singleton('model_proxyconfig', $GLOBALS['aSysDbServer']['report']);
        $fMemberBets = $oProxyConfig->getConfigs($lvtopid, "other_member_comdition");//有效用户最低投注
        $sSql="SELECT SUM(ucount) newUser, SUM(userpay) yxUser, SUM(onlinecount) hyUser, SUM(realPay) todayCr, SUM(rkrs) todayRs, SUM(total_withdraw) todayQk, (SUM(realPay)-SUM(total_withdraw)) win FROM(
               SELECT COUNT(*) ucount, 0 userpay, 0 onlinecount, 0 rkrs, 0 total_withdraw, 0 realPay FROM users WHERE lvtopid = {$lvtopid} AND registertime BETWEEN '{$stime}' AND '{$etime}' UNION ALL
               SELECT 0 ucount, 0 userpay, 0 onlinecount, COUNT(userid) rkrs, 0 total_withdraw, 0 realPay FROM user_report WHERE lvtopid = {$lvtopid} AND realpayment > 0 AND `day` = '".date("Y-m-d",strtotime($etime))."' UNION ALL
			   SELECT 0 ucount, 0 userpay, COUNT(*) AS onlinecount, 0 rkrs, 0 total_withdraw, 0 realPay FROM users WHERE lvtopid = {$lvtopid} AND last_access_time >= DATE_ADD(NOW(),INTERVAL -5 MINUTE) UNION ALL
               SELECT 0 ucount, 0 userpay, 0 onlinecount, 0 rkrs, SUM(`real_amount`) AS total_withdraw, 0 realPay FROM user_withdraw WHERE finishtime BETWEEN '{$stime}' AND '{$etime}' AND `status` = 2 AND `lvtopid` = {$lvtopid} UNION ALL
               SELECT 0 ucount, 0 userpay, 0 onlinecount, 0 rkrs, 0 total_withdraw, SUM(realpayment) realPay FROM user_report WHERE lvtopid = {$lvtopid} AND `day`= '".date("Y-m-d",strtotime($etime))."' UNION ALL
               SELECT 0 ucount, COUNT(userid) userpay, 0 onlinecount, 0 rkrs, 0 total_withdraw, 0 realPay FROM user_report WHERE lvtopid = {$lvtopid} AND `day` >= '".date("Y-m-d",strtotime($etime))."' AND realbets > '{$fMemberBets}' ) tab";
        return $this->oDB->getOne($sSql);
    }
    /**
     *
     * 根据日期 返回某商户下的数据 //已对每一条SQL进行一致性验证
     *
     * @author ken 2017
     * @param $lvtopid
     * @param int $realbets
     * @param $starttime
     * @param $endtime
     * @param $ext 选择输入的日期格式按日或按月day|month
     * @return 根据日期，总充值，总提取
     */
    public function getUserCount($lvtopid, $realbets = 0){
        $starttime = date("Y-m-d 00:00:00");
        $endtime = date("Y-m-d 23:59:59");
        $sQl = "SELECT IFNULL(SUM(realpayment),0) AS total_payment, IFNULL(SUM(realwithdraw),0) AS total_withdraw FROM `user_report` WHERE lvtopid = '{$lvtopid}' AND `day` >= '{$starttime}' AND `day` <= '{$endtime}' ";
        return $this->oDB->getAll($sQl);
    }
    /**
     *  desc今日取款
     * @author rhovin 2017-10-11;
     */
    public function getUserWithdraw($lvtopid) {
        $date = date("Y-m-d");
        $sSql = "SELECT SUM(`real_amount`) AS total_withdraw  FROM user_withdraw WHERE  DATE_FORMAT(`finishtime` ,'%Y-%m-%d') ='".$date."' AND `status`=2 AND `lvtopid`= $lvtopid";
        return $this->oDB->getOne($sSql);
    }
    /**
     * desc 今日普通存入
     * @author rhovin 2017-10-13
     */
    public function getOrdinaryAmount($lvtopid) {
        $date = date("Y-m-d");
         $sSql = "SELECT   round(SUM(`amount`),2) AS manuaordinarypay_money FROM manualpay_confirm  WHERE   DATE_FORMAT(finishtime ,'%Y-%m-%d') ='".$date."' AND `isconfirm`=1 AND `optype`=0 AND order_type=0 AND `lvtopid`= $lvtopid ";
        return $this->oDB->getOne($sSql);
    }

    /**
     * 获取每天的新注册用户
     *
     * @author ken 2017
     * @param $lvtopid
     * @return 返回每天|每月新注册用户
     */
    public function getNewUser($lvtopid,$format = '')
    {
        $starttime = date("Y-m-d 00:00:00");
        $endtime = date("Y-m-d 23:59:59");
        return $this->oDB->getOne("SELECT COUNT(userid) AS new_user FROM users WHERE lvtopid ='{$lvtopid}' AND registertime >= '{$starttime}' AND registertime <= '{$endtime}'");

    }
    /**
     * 获取每天有投注的用户//已对每条SQL进行校验 基本功能满足
     *
     * @author ken 2017
     * @param $lvtopid
     * @return 返回每天|每月有投注的用户
     */
    public function getWithBetsUser($lvtopid)
    {
        /* @var $oProxyConfig model_proxyconfig */
        $oProxyConfig = A::singleton('model_proxyconfig', $GLOBALS['aSysDbServer']['report']);
        $fMemberBets = $oProxyConfig->getConfigs($lvtopid, "other_member_comdition");//有效用户最低打码量
        return $this->oDB->getOne("SELECT COUNT(userid) bet_user FROM user_report WHERE lvtopid = '{$lvtopid}' AND `day` >= '".date('Y-m-d')."' AND realbets > '{$fMemberBets}'");
    }

    /**
     * 获取用户汇总数据
     *
     * @author ken 2017
     * @param $lvtopid
     * @return array
     */
    public function getTotalUserDate($lvtopid)
    {
        $starttime = date("Y-m-d 00:00:00");
        $endtime = date("Y-m-d 23:59:59");
        return $this->oDB->getAll(" SELECT SUM(realpayment) AS total_payment,SUM(realwithdraw) AS total_withdraw, `day`  FROM lvtopid='{$lvtopid}' AND `user_report` WHERE `day` >= '{$starttime}' AND `day` <= '{$endtime}' ");
    }
    /**
     * 获取每月充值用户 已对每行进行验证
     * @param $lvtopid
     * @return 返回每月有充值的用户
     */
    public function getUserPayment($lvtopid)
    {
        $starttime = date("Y-m-d 00:00:00");
        $endtime = date("Y-m-d 23:59:59");
        return $this->oDB->getOne("SELECT COUNT(DISTINCT(userid)) AS userpay FROM user_report WHERE lvtopid = '{$lvtopid}' AND realpayment > 0 AND `day` >= '{$starttime}' AND  `day` <= '{$endtime}'");
    }
    /**
     * desc 获取当前活跃用户
     * @author rhovin 2017-08-21
     */
    public function getOnlineUser($lvtopid) {
        $lasttime = date("Y-m-d H:i:s", time()-300);
        $sSql= "SELECT COUNT(*) AS onlinecount FROM users WHERE lvtopid='{$lvtopid}' AND last_access_time>=DATE_ADD(NOW(),INTERVAL -5 minute )" ;
        return $this->oDB->getOne($sSql);
    }
    /**
     * 中国地图数据
     *
     * @author ken
     * @param $lvtopid
     * @return 返回中国地图省份以用户id做唯一区分
     */
    public function getChinaMap($lvtopid)
    {
        //此SQL已弃用  $sSql = " SELECT COUNT(DISTINCT(lg.userid)) AS usernum,lg.area FROM `loginlog` AS lg LEFT JOIN `region_province` AS rg ON(lg.area=rg.title) WHERE lg.lvtopid = '{$lvtopid}' GROUP BY `area` ";
        $sSql = "SELECT lastip FROM users  WHERE  lvtopid='{$lvtopid}' AND last_access_time >= DATE_ADD(NOW(),INTERVAL -5 MINUTE) ";
        return $this->oDB->getAll($sSql);
    }
    
    /**
     * 根据日期获取用户数据-新增-有效-有投注
     *
     * @author ken 2017
     * @param $lvtopid
     * @param int $realbets
     * @param string $format
     * @return 用户新注册用户数|有效会员数|有充值用户数
     */
    public function getUserRegAndBetAndPayInfo($lvtopid,$realbets=0,$format = '')
    {
        $today = date('Y-m-01');
        $endDay = date('Y-m-d', strtotime("+1 day"));
        $thisyear = date("Y-01-01",strtotime("0 years"));
        $nexmonth = date("Y-m-01",strtotime("+1 Months"));
        if ('day' == $format) {
            $sSql = " SELECT d.dt ,
        (SELECT count(userid) FROM users WHERE DATE_FORMAT(registertime,'%Y-%m-%d')=d.dt AND lvtopid='{$lvtopid}') AS reg_user,
        (SELECT count(userid) FROM user_report  WHERE day=d.dt AND realbets > 0 AND lvtopid='{$lvtopid}') AS bet_user,
        (SELECT count(userid) FROM user_report  WHERE day=d.dt AND realpayment > 0 AND lvtopid='{$lvtopid}') AS real_user

        FROM (
          SELECT DATE_ADD('{$today}',
          INTERVAL(ones.num+tens.num+hundreds.num)day) dt
        FROM
      (SELECT 0 num union ALL
      select 1 num union all
      select 2 num union all
      select 3 num union all
      select 4 num union all
      select 5 num union all
      select 6 num union all
      select 7 num union all
      select 8 num union all
      select 9 num ) ones
      CROSS JOIN
      (SELECT 0 num union ALL
      SELECT 10 num union ALL
      SELECT 20 num union ALL
      SELECT 30 num union ALL
      SELECT 40 num union ALL
      SELECT 50 num union ALL
      SELECT 60 num union ALL
      SELECT 70 num union ALL
      SELECT 80 num union ALL
      SELECT 90 num ) tens
      CROSS JOIN
      (SELECT 0 num union ALL
      SELECT 100 num union ALL
      SELECT 200 num union ALL
      SELECT 300 num) hundreds
      where date_add('{$today}',
      interval(ones.num+tens.num+hundreds.num)DAY)<DATE('{$endDay}')#'2016-09-18'
      ORDER BY 1
      ) d; ";
            return $this->oDB->getAll($sSql);
        }
        if ('month' == $format) {// 按月
            $sSql = " SELECT DATE_FORMAT(dt,'%m') AS month,SUM(reg_user) AS reg_user,SUM(bet_user) AS real_user,SUM(real_user) AS pay_user FROM(
      SELECT d.dt ,
        (SELECT count(userid) FROM users WHERE DATE_FORMAT(registertime,'%Y-%m-%d')=d.dt AND lvtopid='{$lvtopid}') AS reg_user,
        (SELECT count(userid) FROM user_report  WHERE day=d.dt AND realbets > 0 AND lvtopid='{$lvtopid}') AS bet_user,
        (SELECT count(userid) FROM user_report  WHERE day=d.dt AND realpayment > 0 AND lvtopid='{$lvtopid}') AS real_user

        FROM (
          SELECT DATE_ADD('{$thisyear}',
          INTERVAL(ones.num+tens.num+hundreds.num)DAY) dt
        FROM
      (SELECT 0 num union ALL
      select 1 num union all
      select 2 num union all
      select 3 num union all
      select 4 num union all
      select 5 num union all
      select 6 num union all
      select 7 num union all
      select 8 num union all
      select 9 num ) ones
      CROSS JOIN
      (SELECT 0 num union ALL
      SELECT 10 num union ALL
      SELECT 20 num union ALL
      SELECT 30 num union ALL
      SELECT 40 num union ALL
      SELECT 50 num union ALL
      SELECT 60 num union ALL
      SELECT 70 num union ALL
      SELECT 80 num union ALL
      SELECT 90 num ) tens
      CROSS JOIN
      (SELECT 0 num union ALL
      SELECT 100 num union ALL
      SELECT 200 num union ALL
      SELECT 300 num) hundreds
      where date_add('{$thisyear}',
      interval(ones.num+tens.num+hundreds.num)DAY)<DATE('$nexmonth')#'2016-09-18'
      ORDER BY 1
      ) d
    )a GROUP BY DATE_FORMAT(dt,'%m'); ";
            return $this->oDB->getAll($sSql);
        }
    }
    
    /**
     * 根据日期获取用户数据-充值-提现-盈利
     *
     * @author ken 2017
     * @param $lvtopid
     * @param string $format
     * @return 用户充值|提现|盈利
     */
    public function getUserMoneyInfoOutIn($lvtopid,$format = ''){
        $today = date('Y-m-01');
        $endDay = date('Y-m-d', strtotime("+1 day"));
        $thisyear = date("Y-01-01",strtotime("0 years"));
        $nexmonth = date("Y-m-01",strtotime("+1 Months"));
        
        if ('month' == $format){
            $sSql = " SELECT DATE_FORMAT(dt,'%m') AS month,SUM(real_pay) AS total_money_in,SUM(real_out) AS total_money_out FROM(
      SELECT d.dt ,
        (SELECT IFNULL(SUM(realpayment),0) AS real_pay FROM user_report WHERE DATE_FORMAT(`day`,'%Y-%m-%d')=d.dt AND lvtopid='{$lvtopid}') AS real_pay,
        (SELECT IFNULL(SUM(realwithdraw),0) AS real_out FROM user_report WHERE DATE_FORMAT(`day`,'%Y-%m-%d')=d.dt AND lvtopid='{$lvtopid}') AS real_out
        FROM (
          SELECT DATE_ADD('{$thisyear}',
          INTERVAL(ones.num+tens.num+hundreds.num)DAY) dt
        FROM
      (SELECT 0 num union ALL
      select 1 num union all
      select 2 num union all
      select 3 num union all
      select 4 num union all
      select 5 num union all
      select 6 num union all
      select 7 num union all
      select 8 num union all
      select 9 num ) ones
      CROSS JOIN
      (SELECT 0 num union ALL
      SELECT 10 num union ALL
      SELECT 20 num union ALL
      SELECT 30 num union ALL
      SELECT 40 num union ALL
      SELECT 50 num union ALL
      SELECT 60 num union ALL
      SELECT 70 num union ALL
      SELECT 80 num union ALL
      SELECT 90 num ) tens
      CROSS JOIN
      (SELECT 0 num union ALL
      SELECT 100 num union ALL
      SELECT 200 num union ALL
      SELECT 300 num) hundreds
      where date_add('{$thisyear}',
      interval(ones.num+tens.num+hundreds.num)DAY)<DATE('{$nexmonth}')#'2016-09-18'
      ORDER BY 1
      ) d
    )a GROUP BY DATE_FORMAT(dt,'%m'); ";
            return $this->oDB->getAll($sSql);
        }
        
        if ('day' == $format) {
            $ssQl = " SELECT d.dt ,
        (SELECT IFNULL(SUM(realpayment),0) AS real_pay FROM user_report WHERE DATE_FORMAT(`day`,'%Y-%m-%d')=d.dt AND lvtopid='{$lvtopid}') AS total_money_in,
        (SELECT IFNULL(SUM(realwithdraw),0) AS real_out FROM user_report WHERE DATE_FORMAT(`day`,'%Y-%m-%d')=d.dt AND lvtopid='{$lvtopid}') AS total_money_out
        FROM (
          SELECT DATE_ADD('{$today}',
          INTERVAL(ones.num+tens.num+hundreds.num)DAY) dt
        FROM
      (SELECT 0 num union ALL
      select 1 num union all
      select 2 num union all
      select 3 num union all
      select 4 num union all
      select 5 num union all
      select 6 num union all
      select 7 num union all
      select 8 num union all
      select 9 num ) ones
      CROSS JOIN
      (SELECT 0 num union ALL
      SELECT 10 num union ALL
      SELECT 20 num union ALL
      SELECT 30 num union ALL
      SELECT 40 num union ALL
      SELECT 50 num union ALL
      SELECT 60 num union ALL
      SELECT 70 num union ALL
      SELECT 80 num union ALL
      SELECT 90 num ) tens
      CROSS JOIN
      (SELECT 0 num union ALL
      SELECT 100 num union ALL
      SELECT 200 num union ALL
      SELECT 300 num) hundreds
      where date_add('{$today}',
      interval(ones.num+tens.num+hundreds.num)DAY)<DATE('{$endDay}')#'2016-09-18'
      ORDER BY 1
      ) d ";
            return $this->oDB->getAll($ssQl);
        }
    }
    
    /**
     * 获取每一天
     *
     * @author ken
     * @return 返回当前月第一天到最后一天世界末日
     */
    public function getEachDay()
    {
        $today = date('Y-m-01');
        $endDay = date('Y-m-d', strtotime("+1 day"));
        $sSql = "SELECT DATE_ADD('{$today}',
        INTERVAL(ones.num+tens.num+hundreds.num)day) day
        FROM
        (SELECT 0 num union ALL
        select 1 num union all
        select 2 num union all
        select 3 num union all
        select 4 num union all
        select 5 num union all
        select 6 num union all
        select 7 num union all
        select 8 num union all
        select 9 num ) ones
        CROSS JOIN
        (SELECT 0 num union ALL
        SELECT 10 num union ALL
        SELECT 20 num union ALL
        SELECT 30 num union ALL
        SELECT 40 num union ALL
        SELECT 50 num union ALL
        SELECT 60 num union ALL
        SELECT 70 num union ALL
        SELECT 80 num union ALL
        SELECT 90 num ) tens
        CROSS JOIN
        (SELECT 0 num union ALL
        SELECT 100 num union ALL
        SELECT 200 num union ALL
        SELECT 300 num) hundreds
        where date_add('{$today}',
        interval(ones.num+tens.num+hundreds.num)DAY)<DATE('{$endDay}')
        ORDER BY 1";
        return $this->oDB->getAll($sSql);
    }
    
    /**
     * desc 重写商户后台添加用户
     * @author rhovin 2017-08-07
     */
    public function pInsertUser($aUserInfo, $fLastMaxPoint, $iGroupId, $iParentId = 0, $bAddFund = TRUE, $iIsTester = 0, $iDyn = 1, $iPgType = 0, $iChannelId = 0) 
    {
         // 数据合法性检测
        if (empty($aUserInfo) && !is_array($aUserInfo) || empty($iGroupId)
                || !is_numeric($iGroupId) || !is_numeric($iParentId)) {
            return FALSE;
        }
        if (empty($aUserInfo['username']) || empty($aUserInfo['loginpwd'])) {
            return FALSE;
        }
        $aUserInfo['loginpwd'] = md5($aUserInfo['loginpwd']);
        if (!isset($aUserInfo["usertype"]) || !is_numeric($aUserInfo["usertype"])) { // 0 普通用户
            return FALSE;
        }
        $aUserInfo['usertype'] = intval($aUserInfo['usertype']);
        // 数据修复以及填充
        if (!empty($aUserInfo['nickname'])) {
            $aUserInfo['nickname'] = daddslashes($aUserInfo['nickname']);
        } else {
            $aUserInfo['nickname'] = daddslashes($aUserInfo['username']);
        }
        if (!isset($aUserInfo['createaccount']) || ($aUserInfo['createaccount'] != 1 && $aUserInfo['createaccount'] != 0)) { // 默认有开户权限
            $aUserInfo['createaccount'] = 1; // 有待商议
        }
        $aUserInfo['securitypwd'] = empty($aUserInfo['securitypwd']) ? '' : md5($aUserInfo['securitypwd']);
        $aUserInfo['lastip'] = empty($aUserInfo['lastip']) ? '0.0.0.0' : $aUserInfo['lastip'];
        $aUserInfo['lasttime'] = empty($aUserInfo['lasttime']) ? '1970-01-01 00:00:00' : $aUserInfo['lasttime'];
        $aUserInfo['registerip'] = empty($aUserInfo['registerip']) ? getRealIP() : $aUserInfo['registerip'];
        $aUserInfo['registertime'] = date("Y-m-d H:i:s");
        $aUserInfo['username'] = daddslashes($aUserInfo['username']);
        $aUserInfo['realname']  = !empty($aUserInfo['realname']) ? daddslashes($aUserInfo['realname']) : '';
        $aUserInfo['qq']        = !empty($aUserInfo['qq']) ? daddslashes($aUserInfo['qq']) : '';
        $aUserInfo['mobile']    = !empty($aUserInfo['mobile']) ? daddslashes($aUserInfo['mobile']) : '';
        $aUserInfo['email']     = !empty($aUserInfo['email']) ? daddslashes($aUserInfo['email']) : '';
        $iGroupId = intval($iGroupId);
        $iParentId = intval($iParentId);
        $iLvTopId = 0; // 初始化总代ID
        $iLvProxyId = 0; // 初始化一代ID
        $iIsTester = intval($iIsTester) > 0 ? 1 : 0;  // 测试账户
        $iIsSuperProxy = isset($aUserInfo['issuperproxy']) ? intval($aUserInfo['issuperproxy']) : 0;//超级总代
        $sTopProxyList = isset($aUserInfo['topproxylist']) ? $aUserInfo['topproxylist'] : "";//超级总代所管理的总代列表
        $iIsAllowCS = 1;
        $iIsDynamic = $iDyn == 0 ? 0 : 1;
        $iLevel = 0;
        if ($iParentId != 0) { // 获取上级的父亲树
            $aTempData = $this->oDB->getOne("SELECT U.`issafe`,U.`pgtype`,UT.`parenttree`,UT.`istester`,UT.`isallowcs`,UT.`parentid`, " .
                    "UT.`isdynamic`,UT.`closeload`, UT.`closewithdraw`,UC.`channelid` FROM `usertree` AS UT " .
                    "LEFT JOIN `users` AS U ON UT.`userid`=U.`userid` LEFT JOIN `userchannel` AS UC ON U.`userid`=UC.`userid` WHERE UT.`userid`='" . $iParentId . "'");
            if (empty($aTempData)) {
                return FALSE;
            }
            $iIsTester = intval($aTempData['istester']);
            if ($aTempData['parentid'] > 0 && $aTempData['isallowcs'] == 0) {
                $iIsAllowCS = 0;
            }
            $aT = $aTempData['parenttree'];
            $aT = explode(",", $aT);
            if (!empty($aTempData['parenttree'])) {
                $iLevel = count($aT);
            }
            unset($aT);
            if ($aTempData['parentid'] > 0) {
                $iIsDynamic = $aTempData['isdynamic'];
            }
            if ($aTempData['parenttree'] == '') {
                $sTempTree = $iParentId;
            } else {
                $sTempTree = $aTempData['parenttree'] . ',' . $iParentId;
            }
            $temp_aArr = explode(",", $sTempTree);
            $iLvTopId = empty($temp_aArr[0]) ? 0 : intval($temp_aArr[0]);
            $iLvProxyId = empty($temp_aArr[1]) ? 0 : intval($temp_aArr[1]);
            unset($temp_aArr);
            $aUserInfo['issafe'] = 28; //新客户只能使用三方充值YeePay
            if ($aTempData['issafe'] == 31) {//黑名单用户增加的所有用户都是黑名单
                $aUserInfo['issafe'] = 31;
            } elseif ($sTempTree != '') {//判断他所有上级中是否有黑名单用户
                $sCheckBlankUserSql = "SELECT * FROM `users` WHERE `userid` IN(" . $sTempTree . ") AND `issafe`=31 LIMIT 1";
                $aCheckBlankUser = $this->oDB->getOne($sCheckBlankUserSql);
                if (!empty($aCheckBlankUser)) {//上级中存在有黑名单用户
                    $aUserInfo['issafe'] = 31;
                }
            }
            $aUserInfo['pgtype'] = $iPgType == 0 ? $aTempData['pgtype'] : $iPgType; //指定总代的奖金组类型
            $iChannelId = $aTempData['channelid']; //频道ID
        } else {
            $sTempTree = '';
        }
        // 检测用户名是否已经存在
        if ($this->isExists($aUserInfo['username'], $iLvTopId)) {
            return -1;
        }
        if ($iChannelId == 0) {
            return FALSE;
        }
        if (strlen($sTempTree) > 1024) {
            return FALSE;
        }
        $aUserInfo['maxpoint'] = $fLastMaxPoint;
        $aUserInfo['lvtopid'] = $iLvTopId;
        // 事务操作, 执行用户增加.
        $this->oDB->doTransaction();
        unset($aUserInfo['issuperproxy']);
        unset($aUserInfo['topproxylist']);
        if (FALSE == ($iTempUid = $this->oDB->insert('users', $aUserInfo))) { // 增加失败
            $this->oDB->doRollback();
            return FALSE;
        }
        if ($iLvTopId == 0) { // usertree.lvtopid  属所总代ID (总代则写自己userid, 总代管理员写0)
            $iLvTopId = $iTempUid;
            if(FALSE == $this->oDB->update('users', array('lvtopid' => $iTempUid), '`userid`=' . $iTempUid)) {
                $this->oDB->doRollback();
                return FALSE;
            }
        } elseif ($iLvProxyId == 0) { // usertree.lvproxyid  属所一代ID (一代则写自己userid, 总代管理员写0)
            $iLvProxyId = $iTempUid;
        }
        if ($aUserInfo["usertype"] == 2) { // 如果新增账户为总代管理员, 则总代ID和一代ID都记录0
            $iLvTopId = 0;
            $iLvProxyId = 0;
        }
        $aUserInfo['closeload'] = isset($aTempData['closeload']) ? $aTempData['closeload'] : 0;
        $aUserInfo['closewithdraw'] = isset($aTempData['closewithdraw']) ? $aTempData['closewithdraw'] : 0;
        $aTempData = array(
            "userid" => $iTempUid,
            "username" => $aUserInfo['username'],
            "nickname" => empty($aUserInfo['nickname']) ? $aUserInfo['username'] : $aUserInfo['nickname'],
            "usertype" => $aUserInfo["usertype"],
            "lvtopid" => $iLvTopId,
            "lvproxyid" => $iLvProxyId,
            "parentid" => $iParentId,
            "parenttree" => $sTempTree,
            "identity" => $iParentId == 0 ? 1 : 0,
            "isallowcs" => $iIsAllowCS,
            "isdynamic" => $iIsDynamic,
            "istester" => $iIsTester,
            "issuperproxy" => $iIsSuperProxy,
            "topproxylist" => $sTopProxyList,
            "closeload" => $aUserInfo['closeload'],
            "closewithdraw" => $aUserInfo['closewithdraw']
        );
        $this->oDB->insert('usertree', $aTempData);
        if ($this->oDB->ar() < 1) { // 写入usertree表失败
            $this->oDB->doRollback();
            return FALSE;
        }
        // 往 userchannel 表里写入数据
        $aData = array('userid' => $iTempUid, 'channelid' => $iChannelId, 'groupid' => $iGroupId);
        $this->oDB->insert('userchannel', $aData);
        if ($this->oDB->ar() < 1) { // 写入userchannel表失败
            $this->oDB->doRollback();
            return FALSE;
        }
        if ((bool) $bAddFund) { // 往资金记录表里增加记录
            $aData = array('userid' => $iTempUid, 'channelid' => $iChannelId, 'lastactivetime' => date("Y-m-d H:i:s"));
            $this->oDB->insert('userfund', $aData);
            if ($this->oDB->ar() < 1) { // 往userfund表里写记录失败
                $this->oDB->doRollback();
                return FALSE;
            }
        }

        if ($aUserInfo["usertype"] == 1) {//往用户开户额分配表写数据
            $oUserQuota = new model_useraccquota();
            $mResult = $oUserQuota->setDefaultQuota($iLvTopId,$iTempUid,substr_count($sTempTree,',')+1);
            if(!$mResult){
                $this->oDB->doRollback();
                return FALSE;
            }
        }
        $this->oDB->doCommit(); //提交事务
        return $iTempUid;
    }
    /**
     * @desc 获取重庆时时彩前三直选最高返点
     * @author rhovin 2017-08-29
     */
    public function getCQSSCQSpoint($iTopId) {
        $uSql = "SELECT pgtype FROM users WHERE userid = '$iTopId'";
        $aPgetype = $this->oDB->getOne($uSql);
        $pSql = "SELECT `prizegroupid` FROM prizegroup WHERE lotteryid=1 AND type='".$aPgetype['pgtype']."'";
        $aPgroup = $this->oDB->getOne($pSql);
        $sSql = "SELECT p.userpoint FROM `method` m LEFT JOIN proxy_prizelevel p USING(methodid) WHERE m.lotteryid=1 AND m.`pid`=0 AND m.`methodname`='前三直选' AND p.level=1 AND p.`lvtopid`='$iTopId' AND p.`prizegroupid`='" .$aPgroup['prizegroupid']. "'";
        $aTempPoint = $this->oDB->getOne($sSql);
        if(empty($aPgroup) || empty($aTempPoint)) {
            return FALSE;
        }
        return $aTempPoint['userpoint'];
    }

    /**
     * 根据用户ID获取用户表和用户树信息
     * @author pierce 2017-09-07
     * @return array
     */
    public function getUserTree($iLvtopid){
        $sSql = "SELECT ut.parenttree,ut.parentid,u.username,u.userid,u.usertype,u.maxpoint FROM users AS u LEFT JOIN usertree AS ut USING(userid) WHERE ut.lvtopid = '".$iLvtopid."'";
        return $this->oDB->getAll($sSql);
    }

}
