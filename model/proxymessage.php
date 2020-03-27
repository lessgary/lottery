<?php
// TODO: 重要, 必须与数据库中 adminmenu.title=阅读全部消息 的ID匹配

/**
 * 信息模型类顺序
 * 1 继承重写父类方法
 * 2 新创建的方法
 * 3 私有方法
 * Class model_proxymessage
 * @author ken 2017
 */
class model_proxymessage extends basemodel
{
    /**
     * 根据商户
     * @author ken
     * @from rewrite parent
     * @param string $sReturn
     * @param string $sSelect
     * @return array|string
     */
    public function getMessageTypeByAdminMenus ($sReturn = 'opts', $sSelect = '')
    {
        // 1, 获取全部消息类型
        $aAllMsgType = $this->getAllMessageType();
        $aTypeArray = array();
        foreach ($aAllMsgType AS $v) {
            $aTypeArray[] = $v['menuid'];
        }

        // 2, 获取管理员菜单权限
        //$oAdminUser = new model_adminuser();
        //$aAdminMenus = $oAdminUser->getAdminUserMenus($_SESSION['adminname']);

        // 3, 计算权限交集, 超管则可以阅读所有消息类型, 重要!
        /*if (!in_array(MESSAGE_CAN_READ_ALL, $aAdminMenus)) { // 非超级管理员, 只生成其可读的消息类型 options [数组交集]
            //$aTypeArray = array_intersect( $aAdminMenus, $aTypeArray );
        }*/
        $aTypeArray = array_unique($aTypeArray); // 唯一值过滤
        if ($sReturn != 'opts') { // 如果需要数组, 则直接返回
            return $aTypeArray;
        }
        $sReturn = '';

        //4, 生成 options 字符
        $aAllMsgTypeNew = array();
        foreach ($aAllMsgType AS $v) {
            $aAllMsgTypeNew[$v['menuid']] = $v['title'];
        }
        unset($aAllMsgType); // 就近释放
        foreach ($aTypeArray AS $v) {
            if ($sSelect != $v) {
                $sReturn .= '<OPTION VALUE="' . $v . '">' . $aAllMsgTypeNew[$v] . '</OPTION>';
            } else {
                $sReturn .= '<OPTION SELECTED VALUE="' . $v . '">' . $aAllMsgTypeNew[$v] . '</OPTION>';
            }
        }
        return $sReturn;
    }

    //@todo 重写父类1

    /**
     * @from admin 商户只能发送信息给自己下的商户
     * 管理员发送消息至用户
     * @param $aDatas
     * @param  $sLvtopid ;
     * @return bool|int
     * @author ken 2017
     * @rewite true
     */
    public function InsertMessageFromAdmin ($aDatas)
    {
        //发送站内信
        $sLvtopid = isset($aDatas['lvtopid']) ? $aDatas['lvtopid'] : '';
        $aMsgcontent = $this->_arrageData($aDatas);
        if ($aMsgcontent < 0) {
            return -1000;
        }
        $sSendRange = isset($aDatas['send_range']) ? $aDatas['send_range'] : '';
        if (empty($sSendRange)) {
            return -1009;
        }
        if ($sSendRange == 'islevel') { // 选择层级发送
            $iAllLevel = isset($aDatas['all_level']) ? $aDatas['all_level'] : '';
            $aLevel = isset($aDatas['level']) ? $aDatas['level'] : '';
            if (empty($iAllLevel) && !is_array($aLevel) && empty($aLevel)) {
                return -1000;
            }
            // 如果是0 翻转键名和值
            if ($iAllLevel == 1) { // 全选
                $aUserId = $this->oDB->getAll(" SELECT users.userid  FROM `users` WHERE users.lvtopid = '{$sLvtopid}' ");// 二维数组
                foreach ($aUserId as $kk => $vv) {
                    $aMessageToUserIds[] = $aUserId[$kk]['userid'];
                }
                unset($aUserId);
                if (empty($aMessageToUserIds) || !is_array($aMessageToUserIds)) {
                    return -1001;
                }
               if (TRUE == $this->_sendMsgToUser($aMessageToUserIds,$aMsgcontent)){
                    return 1;
               }
            }else{
                foreach ($aLevel as $v){
                    $aNewLevel[] = "'".$v."'";
                }
                $sNewLevel = implode(',',$aNewLevel);
                $sSql = " SELECT users.userid FROM users WHERE users.layerid IN ({$sNewLevel}) AND users.lvtopid = '{$sLvtopid}' ";
                $iUseridsss = $this->oDB->getAll($sSql);
                if (is_array($iUseridsss) && !empty($iUseridsss[0]['userid']))
                foreach ($iUseridsss as $kk => $vv){
                    $aMessageToUserIds[] = $iUseridsss[$kk]['userid'];
                }
                if (empty($aMessageToUserIds) || !is_array($aMessageToUserIds)) {
                    return -1001;
                }
                if (TRUE == $this->_sendMsgToUser($aMessageToUserIds,$aMsgcontent)){
                    return 1;
                }
            }
        }
        
        if ($sSendRange == 'ismember') {
             $receivename = isset($aDatas['receivename']) ? $aDatas['receivename'] : '';
             $iType = isset($aDatas['type']) ? $aDatas['type'] : '';
             if (empty($receivename) && empty($iType) && !is_array($iType)) {
                 return -1002;//发送用户为空
             }
             // 1 发送给多人且只发送给多人无下级
            if (empty($iType) && !is_array($iType)) {
                $aReceiveName = $this->_filterRecName($receivename);
                if (is_array($aReceiveName)) {
                    foreach ($aReceiveName as $value) {
                        $aUserids[] = $this->oDB->getOne(" SELECT userid FROM users WHERE username = '{$value}' AND lvtopid='{$sLvtopid}' ");
                    }
                    if (empty($aUserids[0])) {
                        return -2001;
                    }
                    foreach ($aUserids as $kkk => $vvv) {
                        $aUserIds[] = $aUserids[$kkk]['userid'];
                    }
                    unset($aUserids);//todo 就近释放
                    if (count($aReceiveName) != count(array_filter($aUserIds))) {
                        return -1003;// 输入的用户有误
                    }
                }
                // 发送
                if (TRUE == $this->_sendMsgToUser($aUserIds, $aMsgcontent)) {
                    return 1;
                }
            }
            // 2 选择有用户上下级
            if (isset($iType) && is_array($iType) && !empty($iType)) {
                if (in_array(1, $iType) && !in_array(2, $iType) && !in_array(3, $iType) && !in_array(4,$iType)) {
                    return -1004; // 群发选项错误
                }
            }

            //2, 获取接收者ID
            $aUserNameArray = $this->_filterRecName($receivename);
            if (!is_array($aUserNameArray)) {
                return -1005;
            }
            $sUserSelfIds = join(',', $aUserNameArray);
            // 获取匹配用户名的 userid, parenttree
            $aUserSelfarr = $this->oDB->getAll("SELECT a.`userid`,b.`parenttree` FROM `users` a " .
                " LEFT JOIN `usertree` b ON a.`userid`=b.`userid` WHERE a.`username` IN ($sUserSelfIds) AND  a.lvtopid  = {$sLvtopid} ");
            if ($this->oDB->ar() == 0) {
                return -1006;
            }
            $sUserSelfIds = '';
            $sUserSelfparents = '';
            foreach ($aUserSelfarr as $v) {
                $sUserSelfIds .= $v['userid'] . ',';
                $sUserSelfparents .= $v['parenttree'] == '' ? '' : $v['parenttree'] . ',';
            }
            if (substr($sUserSelfIds, -1, 1) == ',') {
                $sUserSelfIds = substr($sUserSelfIds, 0, -1);
                $sUserSelfparents = substr($sUserSelfparents, 0, -1);
            }
            $sMessageToUserIds = $sUserSelfIds . ','; // init

            if (isset($aDatas['type']) && is_array($aDatas['type']) && in_array(2, $aDatas['type'])) { // 所有下级
                $sTemp = '';
                foreach ($aUserSelfarr as $v) {
                    $sTemp .= " `parenttree` REGEXP '^" . $v['userid'] . ",|," . $v['userid'] . ",|," . $v['userid'] . "$|^" . $v['userid'] . "\$' OR";
                }
                if (substr($sTemp, -2, 2) == 'OR') {
                    $sTemp = substr($sTemp, 0, -2);
                }
                if ($sTemp != '') {
                    $aRes = $this->oDB->getAll(" SELECT `userid` FROM `usertree` WHERE 1 AND $sTemp ");
                    if (empty($aRes) && (in_array(1,$iType) && in_array(2,$iType))) {//该层级下没有用户
                        return -1007;
                    }
                    if ($this->oDB->ar()) {
                        foreach ($aRes as $v) {
                            $sMessageToUserIds .= $v['userid'] . ',';
                        }
                    }
                    unset($aRes);
                }
            }

            if (isset($iType) && is_array($iType) && in_array(3, $iType)) { // 直接下级
                $aRes = $this->oDB->getAll(" SELECT `userid` FROM `usertree` WHERE 1 AND `parentid` IN ($sUserSelfIds) ");
                if (empty($aRes) && (in_array(3,$iType))) {
                    return -1008;
                }
                if ($this->oDB->ar()) {
                    foreach ($aRes as $v) {
                        $sMessageToUserIds .= $v['userid'] . ',';
                    }
                }
                unset($aRes);
            }

            if (isset($iType) && is_array($iType) && in_array(4, $iType)) { // 所有上级
                $sMessageToUserIds .= $sUserSelfparents;
            }

            $aMessageToUserIds = array_unique(explode(',', $sMessageToUserIds)); // 要发送消息的用户id数组(唯一ID)
            if (isset($iType) && is_array($iType) && in_array(1, $iType)) { // 本人不接收
                $aUserSelfarr = explode(',', $sUserSelfIds);
                if (is_array($aMessageToUserIds) && !empty($aUserSelfarr)) {
                    foreach ($aMessageToUserIds as $k => $v) {
                        if (in_array($v, $aUserSelfarr) || trim($v) == '') {
                            unset($aMessageToUserIds[$k]);
                        }else{
                            $aUserNameArray[$k] = daddslashes(trim($v));
                        }
                    }
                }
            }
            if (TRUE == $this->_sendMsgToUser($aMessageToUserIds,$aMsgcontent)){
                return 1;
            }
        }//end of member
    }

    /**
     * 整理发送的站内信内容
     * @author ken 2017
     * @param $aDatas
     * @return mixed
     */
    private function _arrageData ($aDatas)
    {
        $aMsgcontent['lvtopid'] = isset($aDatas['lvtopid']) ? $aDatas['lvtopid'] : '';
        if ($aMsgcontent['lvtopid'] == '') {
            return -100;
        }

        $aMsgcontent['senderid'] = isset($aDatas['proxyadminid']) ? $aDatas['proxyadminid'] : ''; // 发送者ID
        if ($aMsgcontent['senderid'] == '') {
            return -102;
        }
        $aMsgcontent['msgtypeid'] = isset($aDatas['mt']) ? $aDatas['mt'] : '';  // 消息类型 ken
        if ($aMsgcontent['msgtypeid'] == '') {
            return -103;
        }
        $aMsgcontent['sendergroup'] = 1;                    // 发送者所属组 0=用户组, 1=管理组 //@TODO 暂时默认管理组
        $aMsgcontent['subject'] = daddslashes($aDatas['subject']); // 消息标题
        $aMsgcontent['content'] = daddslashes($aDatas['content']); // 消息内容
        $aMsgcontent['channelid'] = 0;
        $aMsgcontent['sendtime'] = date('Y-m-d H:i:s');
        return $aMsgcontent;
    }
    
    /**
     * 整理过滤用户名 并返回过滤后的用户
     *
     * @author ken 2017
     * @param $sReceivename
     * @return mixed
     */
    private function _filterRecName($sReceivename)
    {
        if (empty($sReceivename)) {
            return FALSE;
        }
          $sSep = array(" ", "　", ",", "，"); // 半角,全角逗号, 空格
            $aUserNameArray = explode(',', trim(str_replace($sSep, ',', $sReceivename)));
            unset($sSep);
            foreach ($aUserNameArray as $k => $v) {
                if (trim($v) == '') {// 去掉空格为空
                    unset($aUserNameArray[$k]);
                } else {
                    $aUserNameArray[$k] = daddslashes(trim($v));
                }
            }
            $aUserNameArray = array_filter($aUserNameArray);
            if (count($aUserNameArray) == 0) {
                return FALSE;
            }
        return $aUserNameArray;
    }
    
    /**
     *
     * @param string $aMessageToUserIds
     * @param string $iInsertId
     * @return bool|mysqli_result|null
     */
    private function _sendMsgToUser($aMessageToUserIds = '',$aMsgcontent = '')
    {
        if (empty($aMessageToUserIds)) {
            return FALSE;
        }
        if (0 == ($iInsertId = $this->oDB->insert('msgcontent', $aMsgcontent))) {
            return FALSE; // 消息内容插入失败
        }
            $sSqlInsert = "INSERT INTO `msglist`(`msgid`,`receiverid`,`receivergroup`) VALUES ";
            if (is_numeric($aMessageToUserIds)) { // 层级发送
                $sSqlInsert .= " ('$iInsertId','$aMessageToUserIds','0')";
            } else if (is_array($aMessageToUserIds)) {
                foreach ($aMessageToUserIds as $v) {
                    if (trim($v) != '' && is_numeric($v)) {
                        $sSqlInsert .= " ('$iInsertId','$v','0'),";
                    }
                }
                if (substr($sSqlInsert, -1, 1) == ',') {
                    $sSqlInsert = substr($sSqlInsert, 0, -1) . ';';
                }
            }
            $aResult = $this->oDB->query($sSqlInsert);
            if (!empty($aResult) && ($this->oDB->errno() <= 0)) {
                return TRUE;
            }else{
                return FALSE;
            }
    }


    /**
     * todo 重写父类方法
     * 返回全部的消息类型
     * @author ken 2017
     * @return array
     */
    public function getAllMessageType ()
    {
        return $this->oDB->getAll("SELECT * FROM `msgtype` ");
    }

    /**
     * 发送信息给用户
     * @param array $aData
     * @return bool|mixed
     * @author ken 2017
     */
    public function sendMsgToUser ($aData = array())
    {
        $aNewSendData['msgtypeid'] = isset($aData['msgtypeid']) ? intval($aData['msgtypeid']) : 1;// 消息类型
        $aNewSendData['senderid'] = isset($aData['senderid']) ? intval($aData['senderid']) : 0;// ken 修改 2017
        $aNewSendData['subject'] = isset($aData['subject']) ? daddslashes($aData['subject']) : '';// 消息内容
        $aNewSendData['content'] = isset($aData['content']) ? daddslashes($aData['content']) : ''; // 消息正文
        $aNewSendData['channelid'] = isset($aData['channelid']) ? intval($aData['channelid']) : 0;
        $aNewSendData['sendtime'] = isset($aData['sendtime']) ? daddslashes($aData['sendtime']) : date('Y-m-d H:i:s');
        $iInsertId = $this->oDB->insert('msgcontent', $aNewSendData);
        // @TODO DEBUG
        return $iInsertId;
    }

    /**
     * todo 重写父类方法
     * 获取消息列表
     * @author ken 2017
     * @param string $sFields
     * @param string $sCondition
     * @param int $iPageRecords
     * @param int $iCurrPage
     * @return array
     */
    public function & getMessageList ($sFields = "*", $sCondition = "1", $iPageRecords = 25, $iCurrPage = 1,$sOrderBy)
    {
        $sTableName = " `msglist` AS a LEFT JOIN `msgcontent` AS b ON (a.msgid=b.id) ";
        $sTableName .= " LEFT JOIN `msgtype` AS c ON(c.id=b.msgtypeid)  ";
        $sTableName .= " LEFT JOIN `users` AS e ON(a.receiverid=e.userid) ";
        $sFields = " a.*, e.username AS receivename, b.sendergroup,  b.content,";
        $sFields .= " b.msgtypeid, b.lvtopid, b.senderid, b.sendtime , b.subject ";
        if (empty($sOrderBy)) {
        
        }else {
            $sOrderBy = " ORDER BY " .$sOrderBy;
        }
        return $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy);
    }

    // @TODO 获取用户层级 未找到原model 如果找到则替换掉
    public function getUserLayer ($lvtopid = null)
    {
        // @TODO 根据商户的 lvtopid '商户ID' 去读取数据
        /**
         *     public function & getMessageList($sFields = "*", $sCondition = "1", $iPageRecords = 25, $iCurrPage = 1) {
         * $sTableName = ' `msglist` l LEFT JOIN `msgcontent` c ON l.`msgid`=c.`id` LEFT JOIN `msgtype` t ON t.`id`=c.`msgtypeid` ' .
         * ' LEFT JOIN `users` ua ON c.`senderid`=ua.`userid` LEFT JOIN `users` ub ON l.`receiverid`=ub.`userid` ';
         * $sFields = ' l.*,c.*, ua.username as sendername, ub.username as receivename ';
         * return $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecords, $iCurrPage, ' ORDER BY `entry` desc ');
         * }
         */
        // @TODO 先设定默认 商户ID 为1 作为调试 后期再进行更改
        if (!empty($lvtopid)) {
            $sTableName = 'user_layer';
            $sWhere = 'lvtopid =' . $lvtopid;
            $sSql = "SELECT user_layer.id,user_layer.layerid, user_layer.name FROM {$sTableName} WHERE {$sWhere}";
            $aResult = $this->oDB->getAll($sSql);
            return $aResult;
        } else {
            return "商户ID不能为空，请至少选择一个商户ID";
        }
    }

    /**
     * 根据id 删除站内信
     * @param $id
     * @param $iLvtopid
     * @return int
     * @author by ken 2017
     */
    public function delMessageById ($id,$iLvtopid = '')
    {
        $sNowTime = date('Y-m-d H:i:s');
        $sSql = "UPDATE msglist SET msglist.deltime = '$sNowTime' WHERE msglist.entry = {$id}";
        $this->oDB->query($sSql);
        return $this->oDB->ar();
    }

    /**
     * 根据id读取站内信内容
     * @param $id
     * @return array
     * @author by ken 2017
     */
    public function readMessageById ($id)
    {
        $sSql = "SELECT msgcontent.id,msgcontent.content,msgcontent.sendtime,msgcontent.subject,msgcontent.content,msgcontent.msgtypeid,msgcontent.senderid FROM msgcontent WHERE msgcontent.id = {$id}";
        $rst = $this->oDB->getOne($sSql);
        return $rst;
    }

    /**
     * 根据id获取站内信
     * @param $id
     * @return array
     */
    public function getMessageById ($id)
    {
        $sSql = "SELECT msglist.readtime FROM msglist WHERE msglist.msgid='{$id}'";
        $rst = $this->oDB->getOne($sSql);
        return $rst;
    }

    /**
     * 将站内信设置为已读
     * @param $id
     * @return int|string
     * @TODO DEBUG 可以考虑用原有的方法
     */
    public function setMsgReadedById ($id)
    {
        $sNowTime = date('Y-m-d H:i:s');
        $sSql = "UPDATE msglist SET msglist.readtime = '$sNowTime' WHERE msglist.msgid = {$id}";
        $this->oDB->query($sSql);
        return $this->oDB->ar();
    }

    /**
     * 根据id插入数据
     * @param $id
     * @return int
     * @author by ken 2017
     */
    public function sendMsgById ($id, $receiverid)
    {
        $sSqlInsert = "INSERT INTO `msglist`(`msgid`,`receiverid`,`receivergroup`) VALUES ('{$id}','{$receiverid}',0)";
        $this->oDB->query($sSqlInsert);
        return $this->oDB->ar();
    }

    /**
     * 根据接收的用户名查找用户
     * @param $recivename
     * @return array
     * @author ken 2017
     */
    public function getUserNameByReciveName ($recivename)
    {
        $sSql = "SELECT users.username, users.usertype ,users.userid FROM users WHERE users.username = '{$recivename}'";
        $aResult = $this->oDB->getOne($sSql);
        return $aResult;
    }

    /**
     * 根据用户id 获取其所有下级
     * @param $userid
     * @return array|string
     */
    public function getUseridInparenttreeByUserId ($userid)
    {
        $sSql = "SELECT usertree.userid FROM usertree WHERE FIND_IN_SET($userid, parenttree)";
        $aResult = $this->oDB->getAll($sSql);
        return $aResult;
    }


    /**
     * 根据商户id 获取管理员
     * @param $lvtopid
     * @return array
     * @author by ken 2017
     */
    public function getAdminUserByLvTopId ($lvtopid)
    {
        $sSql = "SELECT * FROM proxyuser WHERE proxyuser.lvtopid = '{$lvtopid}'";
        $aResult = $this->oDB->getAll($sSql);
        return $aResult;
    }

    /**
     * 获取用户可见的消息类型 (主要用于管理员后台,发布消息时,读取消息类型)
     * @author Tom 100916 19:52 (最后效验)
     * @param  string $sReturn
     * @param  string $sSelect
     * @return mix
     */
    public function getUserCanReadMessageType ($sReturn = 'opts', $sSelect = '')
    {
        //1, 获取全部消息类型
        $aAllMsgType = $this->oDB->getAll("SELECT * FROM `msgtype` WHERE `msgtypegid` = 0 ");
        if ($sReturn != 'opts') { // 如果需要数组, 则直接返回
            return $aAllMsgType;
        }
        $sReturn = '';

        //2, 生成 options 字符
        foreach ($aAllMsgType AS $v) {
            if ($sSelect != $v['id']) {
                $sReturn .= '<OPTION VALUE="' . $v['id'] . '">' . $v['title'] . '</OPTION>';
            } else {
                $sReturn .= '<OPTION SELECTED VALUE="' . $v['id'] . '">' . $v['title'] . '</OPTION>';
            }
        }
        return $sReturn;
    }

    /**
     * 获取一条管理员消息
     * @param int $iMessageId
     * @param $iLvtopid
     * @return array|int
     * @author ken
     */
    public function getOneAdminMessage ($iMessageId = 0, $iLvtopid = '')
    {
        $iMessageId = intval($iMessageId);
        if ($iMessageId == 0) {
            return -1;
        }
        $sWhere = " AND `entry` = '$iMessageId' AND  c.lvtopid = '{$iLvtopid}' ";
        //2, 判断当前管理员可以操作的消息类型权限
        $aAdminMenus = $this->getMessageTypeByAdminMenus('arr');
        $sAdminMenus = '';
        if (is_array($aAdminMenus) && !empty($aAdminMenus)) {
            foreach ($aAdminMenus AS $v) {
                if (is_numeric($v)) {
                    $sAdminMenus .= $v . ',';
                }
            }
        }
        if (substr($sAdminMenus, -1, 1) == ',') {
            $sAdminMenus = substr($sAdminMenus, 0, -1);
        }
        if ($sAdminMenus == '') {
            $sWhere .= " AND 0 ";
        } else {
            $sWhere .= " AND t.`menuid` in ($sAdminMenus) ";
        }
        $res = $this->oDB->getOne("SELECT l.*,c.*,t.*,ua.username as sendername, ub.username as receivename " .
            " FROM `msglist` l LEFT JOIN `msgcontent` c ON l.`msgid`=c.`id` " .
            " LEFT JOIN `msgtype` t ON t.`id`=c.`msgtypeid` " .
            " LEFT JOIN `users` ua ON c.`senderid`=ua.`userid` " .
            " LEFT JOIN `users` ub ON l.`receiverid`=ub.`userid` " .
            " WHERE 1 $sWhere");
        if (empty($res)) {
            return -1;
        }

        if (isset($res['readtime']) && $res['readtime'] == 0 &&
            isset($res['receivergroup']) && $res['receivergroup'] == 1
        ) { // 如果未被阅读, 并且消息发送给管理组, 则更新阅读时间
            $this->setIsReaded($res['entry']);
            $res['readtime'] = date('Y-m-d H:i:s');
        }
        return $res;
    }

    /**
     * 将站内信设置为已读
     * @param $iMessageId
     * @param string $iCurrentTime
     * @return int
     * @author ken
     */
    public function setIsReaded ($iMessageId, $iCurrentTime = '')
    {
        $iCurrentTime = $iCurrentTime == '' ? date('Y-m-d H:i:s') : daddslashes($iCurrentTime);
        $this->oDB->query("UPDATE `msglist` SET `readtime` = '$iCurrentTime' WHERE `msgid`='$iMessageId' ");
        return $this->oDB->ar();
    }
}