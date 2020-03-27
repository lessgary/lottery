<?php

class model_proxymanualpay extends basemodel
{
    /**
     * 读取用户信息 ( 一个 )
     *
     * @order 1
     * @rewrite true
     * @author ken 2017
     * @access  public
     * @param   int $iUserId //要读取的用户ID
     * @param   array $aUserInfo //要读取的用户的信息(字段名)
     * @param   string $sAndWhereSql //附加的搜索条件，以 'and' 开始，例如：' AND `isfrozen`=0'
     * @return  mixed   //失败返回FALSE，成功则返回用户信息数组
     */
    public function getUserInfo ($iUserId = 0, $aUserInfo = array(), $sAndWhereSql = '')
    {
        //如果用户ID取不到值则直接返回FALSE
        if (empty($iUserId)) {
            return false;
        }
        $sTempWhereSql = " `userid` = '" . intval($iUserId) . "' ";
        if (!empty($sAndWhereSql)) {
            $sTempWhereSql .= $sAndWhereSql;
        }
        if (is_array($aUserInfo) && !empty($aUserInfo)) {
//如果指定了要读取的内容
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
     * 根据用户名, 获取用户ID
     *
     * @order 2
     * @rewrite true
     * @author ken 2017
     * @param string $sUsername
     * @param string $sLvtopId
     * @return int userid or 0
     */
    public function getUseridByNames ($sUsername = '', $sLvtopId = '')
    {
        $sUsername = daddslashes(trim($sUsername));
        $sSql = "SELECT a.username,a.userid,a.nickname, b.identity,b.parentid,b.usertype FROM `users` AS a " . "LEFT JOIN `usertree` AS b ON (a.userid=b.userid)" .
            " WHERE  a.username = '$sUsername'  AND a.lvtopid = $sLvtopId AND b.`isdeleted`='0'";
        $aResult = $this->oDB->getOne($sSql);
        $aResult['parentid'] = isset($aResult['parentid']) ? $aResult['parentid'] : '';
        if (0 == $aResult['parentid']){// 不查询总代
            return -100;
        }
        if (is_array($aResult) && ($this->oDB->errno() <= 0) && !empty($aResult['userid'])) {
            return $aResult;
        } else {
            return -100;
        }
    }

    /**
     * 根据用户名模糊获取用户
     *
     * @order 2
     * @rewrite true
     * @author ken 2017
     * @param string $sUsername
     * @param string $sLvtopId
     * @return int userid or 0
     */
    public function getFuzzyByName ($sUsername = '', $sLvtopId = '')
    {
        $sUsername = daddslashes(trim($sUsername));
        $sSql = "SELECT a.username,a.userid,a.nickname, b.identity,b.parentid,b.usertype FROM `users` AS a " . "LEFT JOIN `usertree` AS b ON (a.userid=b.userid)" .
            " WHERE  a.username LIKE '%$sUsername%'  AND a.lvtopid = $sLvtopId AND b.`isdeleted`='0'";
        $aResult = $this->oDB->getAll($sSql);
        $aResultNew =array();
        foreach ($aResult as $k=>$v){
            $aResult[$k]['parentid'] = isset($aResult[$k]['parentid']) ? $aResult[$k]['parentid'] : '';
            if (0 != $aResult[$k]['parentid']){// 不查询总代
                $aResultNew[] = $aResult[$k];
            }
        }
        if (is_array($aResultNew) && ($this->oDB->errno() <= 0) && !empty($aResultNew[0]['userid'])) {
            return $aResultNew;
        } else {
            return -100;
        }
    }
    
    /**
     * 根据账号的id获取用户10秒内的现金余额
     *
     * @order 3
     * @param $sUserid
     * @return bool
     * @author ken 2017
     */
    public function getUserfundByUserid ($sUserid)
    {
        $sSql = "SELECT * FROM userfund AS a LEFT JOIN users AS b ON (a.userid=b.userid) WHERE a.userid = '{$sUserid}'";
        $aResult = $this->oDB->getDataCached($sSql, 10);
        if (empty($aResult)) {
            return false;
        }
        return $aResult[0]; //取10秒缓存
    }

    /**
     * 根据用户id获取用户银行卡信息
     * @param $iUserId
     * @return array
     */
    public function getUserBankInfoByUserId($iUserId){
        $aResulet = array();
        $sSql = "SELECT bi.bankname,ubi.branch,ubi.cardno FROM userbankinfo AS ubi LEFT JOIN bankinfo AS bi ON(ubi.bankid = bi.bankid) WHERE ubi.userid = '".$iUserId."' AND ubi.`isdel` = 0";
        $aResulet = $this->oDB->getAll($sSql);
        return $aResulet;

    }
    
    /**
     * 插入数据
     *
     * @order 4
     * @author ken 2017
     * @param $aGetData
     * @param $iLvtopid
     * @return bool|mixed
     */
    public function Insert ($aGetData, $iLvtopid = '')
    {
        $aData = []; //init
        $aData['checkbets'] = 0;

        // 2 单个存入 -- 整理数据
        if ($aGetData['ext_bets'] != '') {
        //有打码量
            $aData['ext_bets'] = isset($aGetData['ext_bets']) && is_numeric($aGetData["ext_bets"]) ? doubleval($aGetData["ext_bets"]) : 0.00;
        }
        if (!empty($aGetData['apply_remark'])) {
            $aData['apply_remark'] = $aGetData['apply_remark'];
        } else {
            return -1;
        }

        if ($aGetData['audit_bets'] > 0) {
            $aData['checkbets'] = 1;
            $aData['audit_bets'] = $aGetData['audit_bets'];
        }else{
            $aData['audit_bets'] = 0;
        }


        if ($aGetData['order_type'] !== '') {
            $aData['order_type'] = $aGetData['order_type'];
        } else {
            return -2;
        }
        if ($aGetData['single'] === 'single') {
            // 单个用户
            $aData['user_ids'] = isset($aGetData['userid']) ? $aGetData['userid'] : '';
            if ($aData['user_ids'] == '') {
                return -3;
            }
            if ($aGetData['username'] == '') {
                return -3;
            }
            $aData['usernames'] = $aGetData['username'];
        }
        if (empty($aGetData['apply_adminid']) || empty($aGetData['adminname'])) {
            return -9;
        }
        
        // 2 格式化数据
        $aData['inserttime'] = date("Y-m-d H:i:s"); // 插入时间
        $aData['amount'] = isset($aGetData['amount']) && is_numeric($aGetData["amount"]) ? doubleval($aGetData["amount"]) : 0.00; //初始化金额并判断
//        if ($aData['amount'] <= 0) {
//            return -4;
//        }
        $aData['ext_amount'] = isset($aGetData['ext_amount']) && is_numeric($aGetData["ext_amount"]) ? doubleval($aGetData["ext_amount"]) : 0.00;
        $aData['optype'] = $aGetData['optype'];
        $sValues = "";
        // 1 批量存入
        if (isset($aGetData['seleceM']) && $aGetData['seleceM'] != '') {

            if ($aGetData['audit_bets'] > 0) {
                $aData['checkbets'] = 1;
                $aData['audit_bets'] = $aGetData['audit_bets'];
            }else{
                $aData['audit_bets'] = 0;
            }

            if ($aGetData['seleceM'] == 1) {
                //会员
                //2, 获取接收者ID
                $sSep = array(" ", "　", ",", "，"); // 半角,全角逗号, 空格
                $aUserNameArray = explode(',', trim(str_replace($sSep, ',', $aGetData['member'])));
                unset($sSep);
                foreach ($aUserNameArray as $k => $v) {
                    if (trim($v) == '') {
                        unset($aUserNameArray[$k]);
                    } else {
                        $aUserNameArray[$k] = "'" . daddslashes(trim($v)) . "'";
                    }
                }
                
                if (count($aUserNameArray) == 0) {
                    return -5; // 无法获取接收消息的用户
                }
                $sUsernames = join(',', $aUserNameArray);
                $sql = " SELECT u.username,userid,ut.parentid FROM `users` AS u LEFT JOIN `usertree` AS ut USING(userid) WHERE u.`username` IN  ( {$sUsernames} ) AND  u.lvtopid = '{$iLvtopid}' AND ut.parentid <> 0 AND ut.`isdeleted`='0'  ";
                $aUserNum = $this->oDB->getAll($sql);

                if (count($aUserNum) != count($aUserNameArray)) {
                    // 判断输入的用户名个数与输入应户名数量
                    return -10;
                }
                unset($sUsernames); // 就近释放
                if ($this->oDB->ar() == 0) {
                    return -6;
                }
                $aTemp = [];
                foreach ($aUserNum as $k => $v){
                    $aTemp[$k]['lvtopid'] = $iLvtopid;
                    $aTemp[$k]['apply_remark'] = $aData['apply_remark'];
                    $aTemp[$k]['checkbets'] = $aData['checkbets'];
                    $aTemp[$k]['audit_bets'] = $aData['audit_bets'];
                    $aTemp[$k]['ext_bets'] = $aData['ext_bets'];
                    $aTemp[$k]['order_type'] = $aData['order_type'];
                    $aTemp[$k]['inserttime'] = $aData['inserttime'];
                    $aTemp[$k]['amount'] = $aData['amount'];
                    $aTemp[$k]['ext_amount'] = $aData['ext_amount'];
                    $aTemp[$k]['optype'] = $aData['optype'];
                    $aTemp[$k]['user_ids'] = $v['userid'];
                    $aTemp[$k]['usernames'] = $v['username'];
                    $aTemp[$k]['apply_adminid'] = $aGetData['apply_adminid'];
                    $aTemp[$k]['adminname'] = $aGetData['adminname'];
                    $aTemp[$k]['bank_number'] = $aGetData['bankNumber'];
                }
            }

            if ($aGetData['seleceM'] == 2) {
                //层级
                $iLayerid = isset($aGetData['layerid']) ? $aGetData['layerid'] : '';
                if ($iLayerid === '') {
                    return -1000;// 用户层级不能为空
                }
                if (is_numeric($iLayerid)) { //是数字
                    $sSql = " SELECT u.userid,u.username FROM `users` AS u LEFT JOIN `usertree` AS ut USING(userid) WHERE u.layerid = '{$iLayerid}'
                      AND u.lvtopid = '{$iLvtopid}' AND ut.parentid <> 0 AND ut.`isdeleted`='0' ";
                    $aResutlss = $this->oDB->getAll($sSql);
                }
                if (empty($aResutlss) || !is_array($aResutlss)) {
                    return -7;
                }
                foreach ($aResutlss as $kk => $vv) {
                    $aUsername[] = $aResutlss[$kk]['username'];
                    $aUserid[] = $aResutlss[$kk]['userid'];
                }
                $aTemp = [];
                foreach ($aResutlss as $k => $v){
                    $aTemp[$k]['lvtopid'] = $iLvtopid;
                    $aTemp[$k]['apply_remark'] = $aData['apply_remark'];
                    $aTemp[$k]['checkbets'] = $aData['checkbets'];
                    $aTemp[$k]['audit_bets'] = $aData['audit_bets'];
                    $aTemp[$k]['ext_bets'] = $aData['ext_bets'];
                    $aTemp[$k]['order_type'] = $aData['order_type'];
                    $aTemp[$k]['inserttime'] = $aData['inserttime'];
                    $aTemp[$k]['amount'] = $aData['amount'];
                    $aTemp[$k]['ext_amount'] = $aData['ext_amount'];
                    $aTemp[$k]['optype'] = $aData['optype'];
                    $aTemp[$k]['user_ids'] = $v['userid'];
                    $aTemp[$k]['usernames'] = $v['username'];
                    $aTemp[$k]['apply_adminid'] = $aGetData['apply_adminid'];
                    $aTemp[$k]['adminname'] = $aGetData['adminname'];
                    $aTemp[$k]['bank_number'] = $aGetData['bankNumber'];
                }
            }

            foreach ($aTemp as $k => $v){
                $aTemp[$k]= implode("','",$v);
            }
            $sValues .= "('".implode("'),('",$aTemp)."')";
            $sSql = "INSERT IGNORE INTO `manualpay_confirm` ( `lvtopid`,`apply_remark`, `checkbets`, `audit_bets`, `ext_bets`, `order_type`, `inserttime`, `amount`, `ext_amount`, `optype`, `user_ids`, `usernames`, `apply_adminid`, `adminname`, `bank_number` ) VALUES $sValues";
            return $this->oDB->query($sSql);
        }

        $aData['lvtopid'] = $iLvtopid;
        $aData['apply_adminid'] = $aGetData['apply_adminid'];
        $aData['adminname'] = $aGetData['adminname'];
        $aData['bank_number'] = $aGetData['bankNumber'];
        $sTableName = 'manualpay_confirm';
        return $this->oDB->insert($sTableName, $aData);
    }
    
    /**
     * 获取列表数据
     *
     * @order 5
     * @param string $sFields
     * @param string $sCondition
     * @param int $iPageRecords
     * @param int $iCurrPage
     * @param ini $sLvtopId
     * @param $sOrderBy
     * @return mixed
     * @author ken 2017
     */
    public function getConfirmList ($sFields = "*", $sCondition = "1", $iPageRecords = 25, $iCurrPage = 1,$sOrderBy = '')
    {
        $sTableName = " `manualpay_confirm` AS a LEFT JOIN  `proxyuser` AS b  ON( a.apply_adminid=b.proxyadminid ) ";
        $sFields = " a.*, b.lvtopid  ";
        if (empty($sOrderBy)) {//默认没有排序
        } else {
            $sOrderBy = " ORDER BY " . $sOrderBy;
        }
        $aResult = $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy);
        if (empty($aResult) || !is_array($aResult)) {
            return -1000;
        } else {
            foreach ($aResult['results'] as &$v) {
                if ($v['finishtime'] == "0000-00-00 00:00:00") {
                    $v['finishtime'] = "";
                }
            }
            return $aResult;
        }
    }
    
    /**
     * 根据id获取一条信息确认表信息
     *
     * @order 6
     * @param $sId
     * @return array
     * @author ken 2017
     */
    public function getOneConfirmById ($sId)
    {
        $sSql = "SELECT * FROM `manualpay_confirm` WHERE manualpay_confirm.id = '{$sId}'";
        return $this->oDB->getOne($sSql);
    }
    
    /**
     * 如果是审批拒绝 直接走更新确认表 直接更新状态为审批拒绝
     *
     * @order 7
     * @param $aData
     * @return bool|int
     * @author ken 2017
     */
    public function UpdateOneConfirmById ($aData = [])
    {
        $sWhere = " `id` = '{$aData['id']}' AND `isconfirm` = 0 ";
        $sfinishtime = date( "Y-m-d H:i:s", time());
        $aData['finishtime'] = $sfinishtime;
        unset($aData['id']);
        return $this->oDB->update('manualpay_confirm', $aData, $sWhere);
    }
    
    /**
     * 获取用户层级
     *
     * @param $sLvtopid
     * @return array
     * @author ken 2017
     */
    public function getUserLayer ($sLvtopid = '')
    {
        $sSql = "SELECT user_layer.lvtopid,user_layer.name,user_layer.id,user_layer.layerid FROM `user_layer` WHERE user_layer.lvtopid = '{$sLvtopid}'";
        return $this->oDB->getAll($sSql);
    }
}
