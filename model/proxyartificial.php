<?php

class model_proxyartificial extends model_user
{
    /**
     * @order 1
     * @rewrite true
     * @author ken 2017
     * 读取用户信息 ( 一个 )
     * @access  public
     * @param   int     $iUserId        //要读取的用户ID
     * @param   array   $aUserInfo      //要读取的用户的信息(字段名)
     * @param   string  $sAndWhereSql   //附加的搜索条件，以 'and' 开始，例如：' AND `isfrozen`=0'
     * @return  mixed   //失败返回FALSE，成功则返回用户信息数组
     */
    public function getUserInfo($iUserId = 0, $aUserInfo = array(), $sAndWhereSql = '')
    {
        //如果用户ID取不到值则直接返回FALSE
        if (empty($iUserId)) {
            return FALSE;
        }
        $sTempWhereSql = " `userid` = '" . intval($iUserId) . "' ";
        if (!empty($sAndWhereSql)) {
            $sTempWhereSql .= $sAndWhereSql;
        }
        if (is_array($aUserInfo) && !empty($aUserInfo)) {//如果指定了要读取的内容
            //格式化字段名
            foreach ($aUserInfo as &$v) {
                $v = "`" . $v . "`";
            }
            $sSql = "SELECT " . implode(',', $aUserInfo) . " FROM `users` WHERE " . $sTempWhereSql;
        } else {
            $sSql = "SELECT * FROM `users` WHERE " . $sTempWhereSql;
        }
        unset($sTempWhereSql);
        $sSql .= " LIMIT 1";
        return $this->oDB->getOne($sSql);
    }
    
    /**
     * @order 2
     * @rewrite true
     * @author ken 2017
     * 根据用户名, 获取用户ID
     *
     * @param string $sUsername
     * @return int userid or 0
     */
    public function getUseridByUsername($sUsername = '')
    {
        $sUsername = daddslashes(trim($sUsername));
        if (FALSE === strpos($sUsername, '*')) {
            $aResult = $this->oDB->getOne("SELECT u.`userid`,c.`channelid` FROM `users` AS u "
                . "LEFT JOIN `userchannel` AS c ON(u.`userid`=c.`userid`) "
                . "WHERE `username`='$sUsername' LIMIT 1");
            if ($this->oDB->ar()) {
                return $aResult['userid'];
            } else {
                return 0;
            }
        } else {
            $aResult = $this->oDB->getAll("SELECT u.`userid`,c.`channelid` FROM `users` AS u "
                . "LEFT JOIN `userchannel` AS c ON(u.`userid`=c.`userid`) "
                . "WHERE u.`username` LIKE '" .
                str_replace('*', '%', $sUsername) . "' ");
            if ($this->oDB->ar()) {
                return $aResult;
            } else {
                return 0;
            }
        }
    }
    
    /**
     * 根据账号的id获取用户10秒内的现金余额
     * @param $sUserid
     * @return bool
     * @author ken 2017
     */
    public function getUserfundByUserid($sUserid)
    {
        $sSql = "SELECT * FROM userfund AS a LEFT JOIN users AS b ON (a.userid=b.userid) WHERE a.userid = '{$sUserid}'";
        $aResult = $this->oDB->getDataCached($sSql, 10);
        if (empty($aResult)) {
            return FALSE;
        }
        return $aResult[0];//取10秒缓存
        //$aResult = $this->oDB->getOne($sSql);
        //return $aResult;
    }
    /**
     * 管理员给用户理赔
     **@order 3
     * @rewrite true
     * @author ken 2017
     * @param int $adminid
     * @param int $iUserId
     * @param money $fMoney
     * @param string $description
     * @param int $iChannelId
     * @return int
     */
    function adminPayMent($iAdminId, $iUserId, $fMoney, $sDescription = "", $iChannelId = 0, $iClaimType = 0, $sDay = '') {
        if ((!is_numeric($iAdminId)) || (!is_numeric($iUserId))) {
            return -1; //数据不全
        }
        if ($sDescription != "") {
            $sDescription = ":" . daddslashes($sDescription);
        }
        $fMoney = round(floatval($fMoney), 4); //资金格式化
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
        if (empty($aUsers)) {//非总代
            $iOrderType = ORDER_TYPE_LPCZ;
            $sDescription = "理赔充值" . $sDescription;
        } else {//总代
            $iOrderType = ORDER_TYPE_LPCZ;
            $sDescription = "理赔充值" . $sDescription;
        }
        $sActionTime = date("Y-m-d H:i:s"); //动作时间
        /*         * *(开始进行充值账变)** */
        $this->oDB->doTransaction();
        $aOrders = array();
        $aOrders['iFromUserId'] = $iUserId;
        $aOrders['iToUserId'] = $iClaimType;
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
            $this->switchLock($iUserId, 0, FALSE);  //解锁资金表
            return $mResult; //相关的返回值(参考orders)
        }
        //添加理赔报表数据
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
        $aClaim['type'] = $iClaimType; //理赔类型:0:普通理赔,1充值理赔,2分红理赔,3红包理赔
        $aClaim['amount'] = $fMoney; //理赔金额
        $aClaim['desc'] = str_replace("理赔充值:", "", $sDescription); //理赔原因
        if ($sDay == ''){
            $sDay = date("Y-m-d");
        }
        $aClaim['day'] = $iClaimType == 2 ? $sDay : date("Y-m-d"); //理赔日期
        $aClaim['times'] = $sActionTime; //理赔时间
        $this->oDB->insert("claims", $aClaim);//添加理赔数据
        $this->oDB->doCommit();
        //提交事务
        if (FALSE == $this->switchLock($iUserId, 0, FALSE)) {
            return -3;
        }
        return 1;
    }
    
    
    /**
     * 判断管理员权限
     * @param $iAdminId
     * @param int $iUserId
     * @return bool
     * @author ken 2017
     */
    public function checkAdminForUser($iAdminId, $iUserId) {
        if (($iAdminId <= 0) || ($iUserId < 0)) {
            return FALSE;
        }
        //查询是不是销售管理员
        $this->oDB->query("SELECT * FROM `proxyuser` WHERE  proxyuser.proxyadminid ='{$iAdminId}'");
        if ($this->oDB->ar() == 1) {
            return TRUE;
        } else {
            if ($iUserId == 0) {//直接获取关系树
                return FALSE;
            } else {//查询用户的总代和销售管理员之间的关系
                $this->oDB->query("SELECT `userid` FROM `usertree` WHERE `userid`='" . $iUserId . "' AND "
                    . "`lvtopid` IN (SELECT `lvtopid` FROM `proxyuser` WHERE `adminid`='" . $iAdminId . "')");
                if ($this->oDB->ar() == 1) {
                    return TRUE;
                } else {
                    return FALSE;
                }
            }
        }
    }
    
    
    
    
}