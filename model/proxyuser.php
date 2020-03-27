<?php

/**
 * 管理员用户模型
 * 
 * @author     Tom
 * @version    2.0.0
 * @package    passportadmin
 * 
 * 已对每一行代码进行规范化处理. 最后效验于 2010-09-12 20:24
 */
class model_proxyuser extends basemodel {
    public $_errMsg;
    /**
     * 增加一个管理员 ##############老版##################### 新版addProxyUser############
     * @author Tom 100912 20:24 (最后效验)
     */
    public function addAdmin($sAdminName, $sAdminNick, $iAdminGrpId, $sAdminPass, $sAdminlang = "utf8-zhcn", $iMaxChat=0, $iChatGpId = 0, $iChatManagerGpId = 0, $iChatJinliGpId=0) {
        if (empty($sAdminName) || empty($sAdminNick) || empty($sAdminPass)) { // 参数不全
            return -1;
        }
        $oAdminTeam = new model_proxygroup();
        $aTeam = $oAdminTeam->proxygroup($iAdminGrpId);
        if ($aTeam == -1) { // 非法的管理员组
            return -2;
        }
        if ($this->adminNameIsExists($sAdminName)) { // 管理员名称已经存在
            return -3;
        }
        if ($this->adminNickIsExists($sAdminNick)) { // 管理员昵称存在
            return -4;
        }
        if (!$this->checkAdminName($sAdminName)) { //管理员账号不合符规则
            return -5;
        }
        if (!$this->checkAdminPass($sAdminPass)) { //管理员密码不符合规则
            return -6;
        }
        $aProxyuser['adminname'] = daddslashes($sAdminName);
        $aProxyuser['adminnick'] = daddslashes($sAdminNick);
        $aProxyuser['adminpass'] = md5($sAdminPass);
        $aProxyuser['adminlang'] = daddslashes($sAdminlang);
        $aProxyuser['groupid'] = intval($iAdminGrpId);
        $aProxyuser['maxchat'] = intval($iMaxChat);
        $aProxyuser['islocked'] = '0';
        $aProxyuser['menustrs'] = '';
        
//        if ($aProxyuser['groupid'] == $iChatGpId || $aProxyuser['groupid'] == $iChatManagerGpId || $aProxyuser['groupid'] == $iChatJinliGpId){
//            $oNewCs = new db($GLOBALS['aSysNewCs']);
//            $this->oDB->doTransaction(); //开始事务
//            $oNewCs->doTransaction();
//            $bResult = $this->oDB->insert('proxyuser', $aProxyuser);
//            if ($aProxyuser['groupid'] != $iChatGpId && $aProxyuser['groupid'] != $iChatManagerGpId && $aProxyuser['groupid'] != $iChatJinliGpId){
//                if ($bResult){
//                    $this->oDB->doCommit();
//                    return 1;
//                } else {
//                    $this->oDB->doRollback();
//                    return -7;
//                }
//            }
//            $aCheckExist = $oNewCs->getOne("SELECT `userid` FROM `users` WHERE `username` = '{$aProxyuser['adminname']}'");
//            if (empty($aCheckExist)){ // 不存在
//                $aNewCsData = array();
//                $iUserType = 0;
//                if ($aProxyuser['groupid'] == $iChatGpId){
//                    $iUserType = 0;
//                } elseif($aProxyuser['groupid'] == $iChatManagerGpId){
//                    $iUserType = 2;
//                } else {
//                    $iUserType = 1;
//                }
//                $aNewCsData['usertype'] = $iUserType;
//                $aNewCsData['username'] = $aProxyuser['adminname'];
//                $aNewCsData['password'] = $aProxyuser['adminpass'];
//                $aNewCsData['maxclients'] = $aProxyuser['maxchat'];
//                $aNewCsData['isenable'] = $aProxyuser['islocked'] == 0 ? 1 : 0;
//
//                $aNewCsData['unionsid'] = "1,";
//                $bNewCsResult = $oNewCs->insert("users", $aNewCsData);
//                if ($this->oDB->errno() == 0 && $bNewCsResult){
//                    $this->oDB->doCommit();
//                    $oNewCs->doCommit();
//                    return 1;
//                } else {
//                    $this->oDB->doRollback();
//                    $oNewCs->doRollback();
//                    return  -8;
//                }
//            } else {
//                $this->oDB->doCommit();
//                return $this->oDB->insertId();
//            }
//        } else {
//            $this->oDB->insert('proxyuser', $aProxyuser);
//            return $this->oDB->insertId();
//        }
        $this->oDB->insert('proxyuser', $aProxyuser);
        return $this->oDB->insertId();
    }
    /**
     * @desc 增加商户管理员账号
     * @author rhovin 
     * @date 2017-06-13
     */
    public function addProxyUser($data){
        try {
            if (empty($data['adminuser']) || empty($data['adminnick']) || empty($data['adminpass'])) { // 参数不全
                throw new Exception("参数不全");
            }
            $oAdminTeam = new model_proxygroup();
            $aTeam = $oAdminTeam->proxygroup($data['adminteam']);
            if ($aTeam == -1) { // 非法的管理员组
                throw new Exception("非法的管理员组");
            }
            if ($this->adminNameIsExists($data['adminuser'], $data['lvtopid'])) { // 管理员名称已经存在
                throw new Exception("管理员名称已经存在");
            }
            if ($this->adminNickIsExists($data['adminnick'], $data['lvtopid'])) { // 管理员昵称存在
                  throw new Exception("管理员昵称存在");
            }
            if (!$this->checkAdminName($data['adminuser'])) { //管理员账号不合符规则
                  throw new Exception("管理员账号不合符规则");
            }
            if (!$this->checkAdminPass($data['adminpass'])) { //管理员密码不符合规则
                  throw new Exception("管理员密码不符合规则");
            }
            $aProxyuser['adminname'] = daddslashes($data['adminuser']);
            $aProxyuser['adminnick'] = daddslashes($data['adminnick']);
            $aProxyuser['adminpass'] = md5($data['adminpass']);
            $aProxyuser['adminlang'] = daddslashes($data['adminlang']);
            $aProxyuser['groupid'] = intval($data['adminteam']);
            $aProxyuser['islocked'] = '0';
            $aProxyuser['menustrs'] = '';
            $aProxyuser['lvtopid'] = $data['lvtopid'];
            $this->oDB->insert('proxyuser', $aProxyuser);
            return $this->oDB->insertId();
        } catch (Exception $e) {
            $this->_errMsg = $e->getMessage();
            return false;
        }
    }
    /**
     * 检测用户名是否合法
     * @author Tom 100912 20:24 (最后效验)
     */
    static function checkAdminName($sUserName) {
        if (preg_match("/^[0-9a-zA-Z]{3,16}$/i", $sUserName)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * 检查管理员的密码
     * @author Tom 100912 20:24 (最后效验)
     */
    static function checkAdminPass($sUserPass) {
        if (!preg_match("/^[0-9a-zA-Z]{6,16}$/i", $sUserPass) || preg_match("/^[0-9]+$/", $sUserPass)
                || preg_match("/^[a-zA-Z]+$/i", $sUserPass) || preg_match("/(.)\\1{2,}/i", $sUserPass)
        ) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * 检测管理员名称是否存在
     * @author Tom 100912 20:24 (最后效验)
     */
    public function adminNameIsExists($sNickName, $iLvtopid = 0) {
        if (!empty($iLvtopid) && is_numeric($iLvtopid)) {
            // add by ben 404-feature-2017-8-17
            $this->oDB->query("SELECT `proxyadminid` FROM `proxyuser` WHERE `adminname`='" . daddslashes($sNickName) . "' AND `lvtopid`='${iLvtopid}' AND is_del=0");
        } else {
            $this->oDB->query("SELECT `proxyadminid` FROM `proxyuser` WHERE `adminname`='" . daddslashes($sNickName) . "' AND is_del=0");
        }

        return ( $this->oDB->numRows() > 0 );
    }

    /**
     * 检测管理员昵称是否存在
     * @author Tom 100912 20:24 (最后效验)
     */
    public function adminNickIsExists($sNickName, $iLvtopid = 0) {
        if (!empty($iLvtopid) && is_numeric($iLvtopid)) {
            $this->oDB->query("SELECT `proxyadminid` FROM `proxyuser` WHERE `adminnick`='" . daddslashes($sNickName) . "' AND `lvtopid`='${iLvtopid}' AND is_del=0");
        } else {
            $this->oDB->query("SELECT `proxyadminid` FROM `proxyuser` WHERE `adminnick`='" . daddslashes($sNickName) . "' AND is_del=0");
        }

        return ( $this->oDB->numRows() > 0 );
    }

    /**
     * 修改自己的密码
     * @author Tom 100912 20:24 (最后效验)
     */
    public function changeSelfpass($iAdminid, $sAdminPass, $sNewAdminPass) {
        $iAdminid = intval($iAdminid);
        if ($iAdminid <= 0) { // 数据安检
            return FALSE;
        }
        if ($sAdminPass != $sNewAdminPass) {
            $this->oDB->query("UPDATE `proxyuser` SET `adminpass`='" . md5($sNewAdminPass) . "' 
                                                WHERE `proxyadminid`='" . $iAdminid . "' AND `adminpass`='" . md5($sAdminPass) . "' LIMIT 1");
            return $this->oDB->ar();
        } else {
            return FALSE;
        }
    }

    /**
     * 管理员登录
     * @author Tom 100912 20:24 (最后效验)
     */
    public function adminlogin($sAdminName, $sAdminPass, $iLvtopid = 0) {
        $sAdminName = daddslashes($sAdminName);
        $iLvtopid = intval($iLvtopid);
        if (empty($sAdminName) || empty($sAdminPass)) {
            return -1; // 登录数据错误
        }
        $aProxyuser = $this->oDB->getOne("SELECT * FROM `proxyuser` WHERE `lvtopid`='$iLvtopid' AND `adminname`='$sAdminName' AND `is_del`=0 LIMIT 1");
        if ($this->oDB->ar() == 0) {
            return -2; // 管理员用户不存在
        }

        if (md5(md5(strtoupper($_SESSION["validateCode"])) . $aProxyuser['adminpass']) != $sAdminPass) {
            return -2;
        }

        if ($aProxyuser["islocked"] != 0) {
            return -3; // 管理员账户被锁定
        }
        $oAdminGrp = new model_proxygroup();
        $aAdminGrps = $oAdminGrp->proxygroup($aProxyuser["groupid"]);
        if ($aAdminGrps == -1) {
            return -4; // 用户组不存在 
        }
        if ($aAdminGrps["isdisabled"] == 1) {
            return -5; // 用户组被禁用
        }
        unset($oAdminGrp, $aAdminGrps);
        $this->oDB->update('proxyuser', array('lastsessionkey' => genSessionKey(), 'lasttime' => date("Y-m-d H:i:s")), "proxyadminid=" . intval($aProxyuser['proxyadminid']));
        if ($this->oDB->errno > 0 || $this->oDB->affectedRows() < 1) { //更新用户session key值失败
            return -6;
        }
        return $aProxyuser;
    }

    /**
     * 检测管理员是否有权限
     * @author Tom 100912 20:24 (最后效验)
     */
    public function adminAccess($iAdminId, $iLvtopId, $sController, $sActioner, $iType = 0) {
        if (($sController == "default") && (in_array($sActioner, array("index", "exit" ,'kindeditorimagemanager','kindeditoruploadimage','loadimage')))) {
            return 1;
        }

        $iAdminId = intval($iAdminId);
        $sController = daddslashes($sController);
        $sActioner = daddslashes($sActioner);
        $aMenu = $this->oDB->getOne("SELECT `title`,`menuid`,`actionlog`,`isclient` FROM `proxymenu` WHERE `controller`='"
                . $sController . "' AND `actioner`='" . $sActioner . "' AND `isdisabled`='0'");
        if ($this->oDB->ar() == 0) { //菜单不存在, 或未启用
            return -100;
        }
        $iMenuId = $aMenu["menuid"]; // 当前菜单ID
        $iIsClient = $aMenu["isclient"]; // 是否为银行客户端权限
        if ($iIsClient == 1 && $iType == 0) {
            return 1;
        }
        if (empty($_SESSION['proxyadminid']) && $iType == 0) {
            return -101;
        }
        if ($this->isEdgeOut(TRUE) && $iType == 0) {
            return -102;
        }
        $aAdminAllMenus = $this->getproxyuserMenus($iAdminId);
        if (empty($aAdminAllMenus)) { // 管理员不存在, 或管理员被锁定, 或所属组不存在, 或所属组被禁用
            return -103;
        }
        /* @var $oProxyAdminLog model_proxylog */
        $oProxyAdminLog = A::singleton('model_proxylog');
        if (!in_array($iMenuId, $aAdminAllMenus)) {
            if ($aMenu['actionlog'] == 1) {
                $oProxyAdminLog->insert('试图访问 [' . $aMenu["title"] . '] 失败', '权限不足', $sController, $sActioner, 0, $iAdminId, $iLvtopId);
            }
            return -104;
        } else {
            if ($aMenu['actionlog'] == 1) {
                $oProxyAdminLog->insert('试图访问 [' . $aMenu["title"] . '] 成功', '访问功能', $sController, $sActioner, 0, $iAdminId, $iLvtopId);
            }
            return 1;
        }
    }

    /**
     * 检查用户多个权限
     * @param <int> $iAdminId
     * @param <array> $aPrivilegeList
     * @return <mix> 
     * @author Rojer
     */
    public function checkAdminPrivilege($iAdminId, $aPrivilegeList) {
        if (!is_array($aPrivilegeList)) {
            return false;
        }
        $iAdminId = intval($iAdminId);
        $aAdminAllMenus = $this->getproxyuserMenus($iAdminId);
        if (empty($aAdminAllMenus)) { // 管理员不存在, 或管理员被锁定, 或所属组不存在, 或所属组被禁用
            return false;
        }
        $tmp = '';
        foreach ($aPrivilegeList as $v) {
            if (!isset($v['controller']) || !isset($v['actioner'])) {
                return false;
            }
            $tmp[] = "(controller = '{$v['controller']}' AND actioner='{$v['actioner']}')";
        }

        if (!$aMenu = $this->oDB->getAll("SELECT `menuid`,`title`,`controller`,`actioner` FROM `adminmenu` WHERE " . implode(' OR ', $tmp) . " AND `isdisabled`='0'")) {
            return false;
        }

        foreach ($aMenu as $k => $v) {
            if (!isset($aAdminAllMenus[$v['menuid']])) {
                unset($aMenu[$k]);
            }
        }

        return $aMenu;
    }

    /**
     * 实例化一个admin
     * @author Tom 100912 20:24 (最后效验)
     */
    public function admin($iAdminId) {
        $this->oDB->query("SELECT * FROM `proxyuser` WHERE `proxyadminid`='" . intval($iAdminId) . "' LIMIT 1");
        if ($this->oDB->ar() == 0) {
            return -1;
        } else {
            return $this->oDB->fetchArray();
        }
    }

    /**
     * 获取管理员分页
     * @author Tom 100912 20:24 (最后效验)
     */
    public function & getAdminList($sFields = "*", $sCondition = "1", $iPageRecords = 25, $iCurrPage = 1) {
        $sTableName = ' `proxyuser` a left join `proxygroup` b on a.groupid = b.groupid ';
        $sFields = ' a.proxyadminid,a.lvtopid, a.adminname, a.adminnick, a.adminlang, a.islocked, b.groupname,a.onlinestatus,a.groupid ';
        return $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecords, $iCurrPage, " ORDER BY a.adminname");
    }

    /**
     * 获取销售管理员列表
     * @author Tom 100912 20:24 (最后效验)
     */
    public function getSale() {
        return $this->oDB->getAll("SELECT * FROM `proxyuser` WHERE `groupid` IN "
                        . "(SELECT `groupid` FROM `proxygroup` WHERE `issales`='1') ORDER BY adminname");
    }

    /**
     * 删除一个管理员
     * @author Tom 100912 20:24 (最后效验)
     */
    public function userdel($iUserId) {
        $iUserId = intval($iUserId);
        if ($this->admin($iUserId) == -1) {
            return -1; // 管理员ID不存在
        }
        // 001, proxyuser 表删除记录
        $iFlag1 = $this->oDB->query("DELETE FROM `proxyuser` WHERE `proxyadminid`='" . $iUserId . "'");
        // 002, 删除管理员与总代对应关系
        if (SYS_CHANNELID == 0) {
            $iFlag2 = $this->oDB->query("DELETE FROM `adminproxy` WHERE `proxyadminid`='" . $iUserId . "' ");
        } else {
            $iFlag2 = TRUE;
        }
        return ( $iFlag1 && $iFlag2 );
    }

    /**
     * 批量执行更新用户状态
     * @author Tom 100912 20:24 (最后效验)
     */
    public function batchStatusSet($aUser, $iStatus) {
        if (is_array($aUser)) {
            $sUserstr = implode(",", $aUser);
            return $this->oDB->query("UPDATE `proxyuser` SET `islocked`='" . intval($iStatus)
                            . "' WHERE `proxyadminid` IN (" . $sUserstr . ")");
        } else {
            return FALSE;
        }
    }

    /**
     * 客户端下线
     * @author Tom 100912 20:24 (最后效验)
     */
    public function offline($iAdminId = 0) {
        if ($iAdminId == 0) {
            return FALSE;
        }
        $sSql = "UPDATE `proxyuser` SET `onlinestatus`=0 WHERE `proxyadminid`='" . $iAdminId . "'";
        $this->oDB->query($sSql);
        if ($this->oDB->errno > 0 || $this->oDB->ar() < 1) {
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     *  更新管理员信息 (允许密码为空, 即: 不修改管理员密码)
     * @author Tom 100912 20:24 (最后效验)
     */
    public function adminUpdate($iAdminId, $aUser, $aMenu = array(), $iChatGpId = 0, $iChatManagerGpId = 0, $iChatJinliGpId = 0) {
        // 01, 数据过滤
        if (empty($iAdminId) || ($iAdminId == 0)) {
            return -1;
        }
        $aOldAdminArr = $this->admin($iAdminId);
        if ($aOldAdminArr == -1) {
            return -2;
        }
        if ($aUser["adminname"] == "") {
            return -3;
        }
        // 02, 数据整理
        $aNewUserArr['adminname'] = daddslashes($aUser['adminname']);
        $aNewUserArr['adminnick'] = daddslashes($aUser['adminnick']);
        $aNewUserArr['adminlang'] = daddslashes($aUser['adminlang']);
        $aNewUserArr['menustrs'] = $aMenu; // 管理员提交的,全部菜单ID (逗号分隔)
        $aNewUserArr['islocked'] = intval($aUser['islocked']);
        $aNewUserArr['onlinestatus'] = intval($aUser['onlinestatus']);
        $aNewUserArr['groupid'] = intval($aUser['groupid']); // 新组别 ID
        $aNewUserArr['maxchat'] = intval($aUser['maxchat']);
        if (! empty($aUser['paycardlist'])){
            $aNewUserArr['paycardlist'] = daddslashes($aUser['paycardlist']);
        }
        if (!empty($aUser['adminpass']) && strlen($aUser['adminpass']) > 0) { // 只有密码符合6位,并非空情况,才视为更新密码
            $aNewUserArr['adminpass'] = md5($aUser['adminpass']);
        }

        // 获取旧管理员分组ID, 并查询组别菜单ID
        // 如果更改了组别ID, 则使用新组别strs 作为差异比较对象.
        // 如果未更改组别ID, 则使用旧组别strs 作为差异比较对象.
        $oAdminGrp = new model_proxygroup();
        $aAdminGrps = $oAdminGrp->getMenuStringByGrpId(
                ($aOldAdminArr['groupid'] != $aNewUserArr['groupid']) ? $aNewUserArr['groupid'] : $aOldAdminArr['groupid'] );
        if (FALSE == $aAdminGrps) {
            return -100;
        } else {
            // 对组别中存储的 menustrs 进行过滤
            $sAdminMenuStrDiff = explode(',', $aAdminGrps['menustrs']);
            foreach ($sAdminMenuStrDiff as $k => $v) {
                if (!is_numeric($v) || empty($v) || trim($v) == '') {
                    unset($sAdminMenuStrDiff[$k]);
                }
            }
        }

        // 对用户提交的 html.checkbox.id[] 进行过滤
        // 用户的新 '特殊权限' = (用户HTML提交 CHECKBOX.ID) - [新|旧]组别菜单IDs
        foreach ($aMenu as $k => $v) {
            if (!is_numeric($v) || empty($v) || trim($v) == '') {
                unset($aMenu[$k]);
            }
        }
//        // 获取旧用户名
//        $aAdminInfo = $this->admin($iAdminId);
//        $aNewUserArr['menustrs'] = array_unique(array_diff($aMenu, $sAdminMenuStrDiff));
//        $aNewUserArr['menustrs'] = join(',', $aNewUserArr['menustrs']);
//        if ($aUser['groupid'] == $iChatGpId || $aUser['groupid'] == $iChatManagerGpId || $aUser['groupid'] == $iChatJinliGpId){
//            $oNewCs = new db($GLOBALS['aSysNewCs']);
//            $this->oDB->doTransaction(); //开始事务
//            $oNewCs->doTransaction();
//            $bResult = $this->oDB->update("proxyuser", $aNewUserArr, " `proxyadminid` = '" . $iAdminId . "' ");
//            if ($aUser['groupid'] != $iChatGpId && $aUser['groupid'] != $iChatManagerGpId && $aUser['groupid'] != $iChatJinliGpId){
//                if ($bResult){
//                    $this->oDB->doCommit();
//                    return 1;
//                } else {
//                    $this->oDB->doRollback();
//                    return -6;
//                }
//            }
//            $aNewCsData = array();
//            $iUserType = 0;
//            if ($aUser['groupid'] == $iChatGpId){
//                $iUserType = 0;
//            } elseif($aUser['groupid'] == $iChatManagerGpId){
//                $iUserType = 2;
//            } else {
//                $iUserType = 1;
//            }
//            $aNewCsData['usertype'] = $iUserType;
//            $aNewCsData['username'] = $aNewUserArr['adminname'];
//            if (! empty($aNewUserArr['adminpass']) ){
//                $aNewCsData['password'] = $aNewUserArr['adminpass'];
//            }
//            $aNewCsData['maxclients'] = $aNewUserArr['maxchat'];
//            $aNewCsData['isenable'] = $aNewUserArr['islocked'] == 0 ? 1 : 0;
//
//            // 先检查是否存在，如果存在则更改，不存在则写入
//            $aCheckExist = $oNewCs->getOne("SELECT `userid` FROM `users` WHERE `username` = '{$aAdminInfo['adminname']}'");
//            if (! empty($aCheckExist)){ // 存在
//                $bNewCsResult = $oNewCs->update("users", $aNewCsData, " `username` = '{$aAdminInfo['adminname']}'");
//            } else {
//                $aNewCsData['unionsid'] = "1,";
//                $bNewCsResult = $oNewCs->insert("users", $aNewCsData);
//            }
//            if ($this->oDB->errno() == 0 && $bNewCsResult){
//                $this->oDB->doCommit();
//                $oNewCs->doCommit();
//                return 1;
//            } else {
//                $this->oDB->doRollback();
//                $oNewCs->doRollback();
//                return  -5;
//            }
//        } else {
//            return $this->oDB->update("proxyuser", $aNewUserArr, " `proxyadminid` = '" . $iAdminId . "' ");
//        }
        // 获取旧用户名
        $aAdminInfo = $this->admin($iAdminId);
        $aNewUserArr['menustrs'] = array_unique(array_diff($aMenu, $sAdminMenuStrDiff));
        $aNewUserArr['menustrs'] = join(',', $aNewUserArr['menustrs']);
        return $this->oDB->update("proxyuser", $aNewUserArr, " `proxyadminid` = '" . $iAdminId . "' ");
    }
    /**
     * @desc 保存商户独立权限
     * @author rhovin
     * @
     */
    public function updateUserPermission($iProxyId,$sMenuStr){
        $sSql = "UPDATE proxyuser set `menustrs`= '$sMenuStr' WHERE `proxyadminid`=$iProxyId AND `lvtopid`='".$this->lvtopid."'";
        return $this->oDB->query($sSql);
    }
    /**
     * 获取管理员所有的权限
     * @author Tom 100912 20:24 (最后效验)
     */
    public function & getproxyuserMenus($iAdminId = '') {
        if ($iAdminId == '') {
            $iAdminId = daddslashes($_SESSION['proxyadminid']);
        }
        $aReturn = array();
        if ($iAdminId == 0) {
            return $aReturn;
        }
        $aGroupMenus = $this->oDB->getOne("SELECT CONCAT(a.`menustrs`,',',b.`menustrs`) AS ALLMENUS " .
                " FROM `proxyuser` a LEFT JOIN `proxygroup` b " .
                " ON a.`groupid`=b.`groupid` WHERE a.`proxyadminid`='$iAdminId' AND a.islocked=0 AND b.`isdisabled`='0' LIMIT 1");
        if (!$this->oDB->ar() || strlen($aGroupMenus['ALLMENUS']) <= 1) { // 计算逗号
            return $aReturn;
        }
        $aAdminMenus = explode(',', $aGroupMenus['ALLMENUS']);
        foreach ($aAdminMenus as $v) {
            if (!empty($v) && is_numeric($v)) {
                $aReturn[$v] = $v; // 过滤重复
            }
        }
        return $aReturn;
    }

    /**
     * 根据条件获取指定字段的结果集
     */
    public function getDefinedAdminList($sField = '', $sCondition = '') {
        $sField = empty($sField) ? '*' : $sField;
        $sCondition = empty($sCondition) ? '1' : $sCondition;
        $sSql = "SELECT " . $sField . " FROM `proxyuser` WHERE " . $sCondition;
        return $this->oDB->getAll($sSql);
    }

    /**
     * 获取用户名获取用户信息
     */
    public function getUseradminInfo($sAdminName) {
        $aResult = $this->oDB->getOne("SELECT * FROM `proxyuser` WHERE `adminname`='" . daddslashes($sAdminName) . "' LIMIT 1");
        if(empty($aResult) || $this->oDB->errno > 0){
            return array();
        }
        return $aResult;
    }
    
    
    /**
     * 根据管理员id获取管理员用户名
     * 
     * @param int $iAdminId     管理员id
     */
    public function getAdminNameById($iAdminId){
        if ($iAdminId <= 0){
            return "";
        }
        
        $sSql = "SELECT `lvtopid`,`adminname`,`transittype`,`menustrs` FROM `proxyuser` WHERE `proxyadminid` = '{$iAdminId}'";
        $aResult = $this->oDB->getOne($sSql);
        return $aResult;
    }

    /**
     * 获取管理员组别信息
     * @param type $iUserId
     */
    public function getUserGroup($iAdminId = 0) {
        $sSql = "SELECT a.*,b.`level`,b.`groupname`,b.`parentstr`,b.`menustrs` as gmenustrs,b.`parentid` FROM `proxyuser` AS a LEFT JOIN `proxygroup` AS b ON(a.`groupid`=b.`groupid`) WHERE a.`lvtopid`='" . $iAdminId . "'";
        return $this->oDB->getOne($sSql);
    }
    /**
     * @desc获取商户管理员信息
     * @param type $iUserId
     * @author rhovin
     */
    public function getMemberList($iLvtopid ,$sWhere) {
        $sSql = "SELECT a.*,b.`level`,b.`groupname`,b.`parentstr`,b.`menustrs` as gmenustrs FROM `proxyuser` AS a LEFT JOIN `proxygroup` AS b ON(a.`groupid`=b.`groupid`) WHERE a.`lvtopid`='" . $iLvtopid . "' $sWhere";
        return $this->oDB->getOne($sSql);
    }
    /**
     * 获取商户管理员组别信息
     * @param type $iUserId
     */
    public function getProxyUserGroup($iProxyAdminid = 0) {
        $sSql = "SELECT a.*,b.`level`,b.`groupname`,b.`parentstr`,b.`parentid`,b.`menustrs` as gmenustrs FROM `proxyuser` AS a LEFT JOIN `proxygroup` AS b ON(a.`groupid`=b.`groupid`) WHERE a.`proxyadminid`='" . $iProxyAdminid . "'";

        return $this->oDB->getOne($sSql);
    }

    //检测两个管理员是否是上下级关系
    public function isAdminParent($iAdminId = 0, $iParentId = 0, $bCheck = FALSE) {
        $aProxyuser = $this->getUserGroup($iAdminId); //下级用户信息
        $aParentUser = $this->getUserGroup($iParentId); //下级用户信息
        $aParentTree = explode(",", $aProxyuser['parentstr']);
        if (empty($aProxyuser) || empty($aParentUser)) {
            return FALSE;
        }
        if (!in_array($aParentUser['groupid'], $aParentTree)) {
            if ($bCheck == TRUE) {
                if ($aProxyuser['groupid'] == $aParentUser['groupid']) {//同组之前可以让下级账务下线
                    return TRUE;
                }
            }
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 设置转账管理员权限
     * @param type $iAdminId
     */
    public function setRight($iAdminId = 0, $aData = array()) {
        $this->oDB->update("proxyuser", $aData, "`proxyadminid`='" . $iAdminId . "'");
        if ($this->oDB->errno > 0) {
            return FALSE;
        }
        return TRUE;
    }
    /**
     * @desc 删除商户管理员，逻辑删除
     * @author rhovin 
     * @date 2017-06-14
     */
    public function deleteMember($iProxyadMinid,$aData = []) {
        $this->oDB->update("proxyuser", $aData, "`proxyadminid`='" . $iProxyadMinid . "' AND `lvtopid`='" . $this->lvtopid . "'");
        if ($this->oDB->errno > 0) {
            return FALSE;
        }
        return TRUE;
    }
    /**
     * @desc 锁定/解锁商户管理员
     * @author rhovin 
     * @date 2017-06-14
     */
    public function lockMember($iProxyadMinid,$aData = []) {
        $this->oDB->update("proxyuser", $aData, "`proxyadminid`='" . $iProxyadMinid . "' AND `lvtopid`='" . $this->lvtopid . "'");
        if ($this->oDB->errno > 0) {
            return FALSE;
        }
        return TRUE;
    }
    /**
     * @desc 更新某商户基本信息
     * @author rhovin
     * @date 2017-06-17
     */
    public function updateMember($aData,$proxyadminid) {
        try {
            if(empty($aData)) throw new Exception("没有需要更新的数据");
            
            if(isset($aData['adminname'])) {
                if ($this->adminNameIsExists($aData['adminname'])) { // 管理员名称已经存在
                    throw new Exception("管理员名称已经存在");
                }
                if (!$this->checkAdminName($aData['adminname'])) { //管理员账号不合符规则
                    throw new Exception("管理员账号不合符规则");
                }
            }
            if(isset($aData['adminnick'])) {
                if ($this->adminNickIsExists($aData['adminnick'])) { // 管理员昵称存在
                    throw new Exception("管理员昵称存在");
                }
            }
            
            $sTempWhereSql = " `proxyadminid` = '" . intval($proxyadminid) . "'";
            return $this->oDB->update('proxyuser', $aData, $sTempWhereSql);
        } catch (Exception $e) {
            $this->_errMsg = $e->getMessage();
            return false;
        }
    }
    
    
    /**
     * 判断当前用户浏览器的 session key 和数据库里的session key 是否相同
     * 用于判断用户是否被挤下线，如果被挤下线，则把用户的 session 清掉
     * @desc 调用在登录以后的操作
     * @access  public
     * @author  james
     * @param   boolean $bIsAdmin   //是否为管理员
     * @return  boolean     //成功返回TRUE，失败返回FALSE
     */
    public function isEdgeOut() {
        $iUserId = empty($_SESSION['proxyadminid']) ? 0 : $_SESSION['proxyadminid'];
        if (empty($iUserId)) {
            return TRUE;
        }
        //浏览器的session key
        $sessionKey = genSessionKey();
        $sSql = "SELECT `proxyadminid` FROM `proxyuser` WHERE `proxyadminid`='" . $iUserId . "' AND `lastsessionkey`='" . $sessionKey . "'";
        $aCheck = $this->oDB->getOne($sSql);
        if (!empty($aCheck)) { // 没有被踢掉
            $this->oDB->update('proxyuser', array('lasttime' => date("Y-m-d H:i:s", time())), "proxyadminid='" . $iUserId . "'");
            return FALSE;
        }
        // 清除当前的session
        session_destroy();
        return TRUE;
    }


}

?>