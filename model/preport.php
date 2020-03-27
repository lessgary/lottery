<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/6/6
 * Time: 13:07
 */
class model_preport extends basemodel
{
    public $sTableName = "user_report";
    /**
     * 试构造函数
     *
     * @param array $aDBO
     */
    public function __construct($aDBO = array())
    {
        parent::__construct($aDBO);
    }

    /**
     * 获取用户输赢排名
     *
     * @param  string $sWhere 查询条件
     * @param  string $sOrderBy 排序字段
     * @param  string $sOrderByType 排序方式
     * @param  string $iNumOrder 参与排名个数
     * @return array
     *
     * @author mark
     *
     */
    public function getUserWinOrder($sCondition = ' ', $sOrderBy = 'totalprice', $sOrderByType = 'DESC', $iPageRecord = 0, $iCurrentPage = 10)
    {
        $sTable = " `user_report` AS ur LEFT JOIN `users` AS u ON (ur.userid = u.userid)";
        $sFields = "ur.`userid`,ur.`username`,SUM(ur.`realbets`) AS totalprice,SUM(ur.`points`) AS totalreturn,SUM(ur.`bonus`) AS totalbonus,SUM(ur.`realbets`-`points`-`bonus`) AS totallose,u.lastip AS lastip";
        if ($iPageRecord == 0) {//不分页显示
            $sCondition = empty($sCondition) ? "" : " WHERE " . $sCondition;
            $sSql = " SELECT ur.`userid`,ur.`username`,SUM(ur.`realbets`) AS totalprice,SUM(ur.`points`) AS totalreturn,SUM(ur.`bonus`) AS totalbonus,SUM(ur.`realbets`-`points`-`bonus`) AS totallose,u.lastip AS lastip " .
                " FROM `user_report` AS ur LEFT JOIN `users` AS u ON (ur.userid = u.userid)" . $sCondition .
                " GROUP BY `userid`" .
                " ORDER BY " . $sOrderBy . ' ' . $sOrderByType;
            return $this->oDB->getAll($sSql);
        } else {
            $sCondition = empty($sCondition) ? "1" : $sCondition;
            $sOrderBy = "GROUP BY `userid` ORDER BY ". $sOrderBy . ' ' . $sOrderByType;
            $sCountSql =  "SELECT count(DISTINCT(u.userid)) AS `TOMCOUNT` FROM ".$sTable."WHERE ".$sCondition;
            return $this->oDB->getPageResult($sTable, $sFields, $sCondition, $iPageRecord, $iCurrentPage, $sOrderBy, '',$sCountSql);
        }
    }

    /**
     * 获取后台用户盈亏报表数据
     *
     * @param type $iProxyId
     */
    public function getAdminReport($sCondition,$iPageRecord = 0, $iCurrentPage = 0,$iProxyId) {
        $aChildData = array();
        $aUserInfo = array();
        if ($iProxyId == 0) {//统计总代数据
            $iUserLevel = 1;
            $sSql = "SELECT `userid`,`usertype`,`lvproxyid`,`parentid`,`username`,`maxpoint`,SUM(`realpayment`) AS payment,SUM(`realwithdraw`) AS withdraw,SUM(`realbets`) AS bets,SUM(`points`) AS points,SUM(`bonus`) AS bonus,SUBSTRING_INDEX(`parenttree`,','," . $iUserLevel . ") AS `USERTREE` ";
            $sSql .= " FROM `" . $this->sTableName . "` WHERE 1  " . $sCondition;
            $sSql .= " GROUP BY `userid` ";
        } else {
            //查询当前用户的直接下级及自己
            $aUserChileInfo = $this->oDB->getAll("SELECT ut.*,u.`maxpoint` FROM `usertree` AS ut LEFT JOIN `users` AS u ON(ut.`userid`=u.`userid`)  WHERE ut.`userid`='" . $iProxyId . "' OR ut.`parentid`='" . $iProxyId . "'");
            foreach ($aUserChileInfo as $v) {
                $iTmpUserId = intval($v['userid']);
                if ($iTmpUserId == $iProxyId) {
                    $aUserInfo = $v;
                    $aUserLevel = explode(",", $aUserInfo["parenttree"]);
                    if (empty($aUserInfo["parenttree"])) {
                        $iUserLevel = 2;
                    } else {
                        $iUserLevel = count($aUserLevel) + 2;  //用户树的层级
                    }
                }
                $aChildData[$iTmpUserId]["username"] = $v["username"];
                $aChildData[$iTmpUserId]["maxpoint"] = $v["maxpoint"] * 100;
                if ($v['usertype'] == 0) {
                    $aChildData[$iTmpUserId]["groupname"] = "会员";
                } else {
                    $aChildData[$iTmpUserId]["groupname"] = $this->getGroupName($aUserInfo['usertype'],$aUserInfo['parenttree']);
                }
            }
            $sSql = "SELECT `userid`,`usertype`,`parenttree`,`lvproxyid`,`parentid`,`username`,`maxpoint`,SUM(`realpayment`) AS payment,SUM(`activity`) AS activity,SUM(`realwithdraw`) AS withdraw,SUM(`realbets`) AS bets,SUM(`points`) AS points,SUM(`bonus`) AS bonus,SUBSTRING_INDEX(`parenttree`,','," . $iUserLevel . ") AS `USERTREE` ";
            $sSql .= " FROM `" . $this->sTableName . "` WHERE (find_in_set(" . $iProxyId . ",`parenttree`) OR `userid`='" . $iProxyId . "') " . $sCondition;
            $sSql .= " GROUP BY `userid`";
        }
        $aTemp = $this->oDB->getAll($sSql);
        $aResult = array();
        $aTotal = array();
        $aProxySelf = array();
        //总计
        $aTotal["total_payment"] = 0;
        $aTotal["total_activity"] = 0;
        $aTotal["total_withdraw"] = 0;
        $aTotal["total_bets"] = 0;
        $aTotal["total_points"] = 0;
        $aTotal["total_realbets"] = 0;
        $aTotal["total_bonus"] = 0;
        $aTotal["total_profit"] = 0;
        foreach ($aTemp as $v) {
            $aTree = explode(",",  trim($v["USERTREE"]));
            $iTreeCount = count($aTree);
            if ($v["parentid"] == $iProxyId || $v["userid"] == $iProxyId) {
                $sStrUser = $v["userid"];
                $aResult[$sStrUser]["username"] = $v["username"];
                $aResult[$sStrUser]["maxpoint"] = $v["maxpoint"] * 100;
                if ($v['usertype'] == 0) {
                    $aResult[$sStrUser]["groupname"] = "会员";
                } else {
                    $aResult[$sStrUser]["groupname"] = $this->getGroupName($v['usertype'],$v['parenttree']);
                }
            } else {
                $sStrUser = $aTree[$iTreeCount - 1];
            }
            if (empty($aResult[$sStrUser])) {
                $aResult[$sStrUser]['username'] = $aChildData[$sStrUser]['username'];
                $aResult[$sStrUser]["maxpoint"] = $aChildData[$sStrUser]['maxpoint'];
                $aResult[$sStrUser]['groupname'] = $aChildData[$sStrUser]['groupname'];
            }
            if (isset($aResult[$sStrUser]["bets"])) {
                $aResult[$sStrUser]["payment"] += $v["payment"];
                $aResult[$sStrUser]["activity"] += $v["activity"];
                $aResult[$sStrUser]["withdraw"] += $v["withdraw"];
                $aResult[$sStrUser]["bets"] += $v["bets"];
                $aResult[$sStrUser]["points"] += $v["points"];
                $aResult[$sStrUser]["realbets"] = $aResult[$sStrUser]["bets"] - $aResult[$sStrUser]["points"];
                $aResult[$sStrUser]["bonus"] += $v["bonus"];
                $aResult[$sStrUser]["profit"] = $aResult[$sStrUser]["bets"] - $aResult[$sStrUser]["points"] - $aResult[$sStrUser]["bonus"];
                //总计
                $aTotal["total_payment"] += $v["payment"];
                $aTotal["total_activity"] += $v["activity"];
                $aTotal["total_withdraw"] += $v["withdraw"];
                $aTotal["total_bets"] += $v["bets"];
                $aTotal["total_points"] += $v["points"];
                $aTotal["total_bonus"] += $v["bonus"];
            } else {
                $aResult[$sStrUser]["payment"] = $v["payment"];
                $aResult[$sStrUser]["activity"] = $v["activity"];
                $aResult[$sStrUser]["withdraw"] = $v["withdraw"];
                $aResult[$sStrUser]["bets"] = $v["bets"];
                $aResult[$sStrUser]["points"] = $v["points"];
                $aResult[$sStrUser]["realbets"] = $aResult[$sStrUser]["bets"] - $aResult[$sStrUser]["points"];
                $aResult[$sStrUser]["bonus"] = $v["bonus"];
                $aResult[$sStrUser]["profit"] = $v["bets"] - $v["points"] - $v["bonus"];
                //总计
                $aTotal["total_payment"] += $v["payment"];
                $aTotal["total_activity"] += $v["activity"];
                $aTotal["total_withdraw"] += $v["withdraw"];
                $aTotal["total_bets"] += $v["bets"];
                $aTotal["total_points"] += $v["points"];
                $aTotal["total_bonus"] += $v["bonus"];
            }
        }
        $aTotal["total_realbets"] = $aTotal['total_bets'] - $aTotal['total_points'];
        $aTotal["total_profit"] = $aTotal['total_bets'] - $aTotal['total_points'] - $aTotal['total_bonus'];
        if ($iProxyId > 0 && !isset($aResult[$iProxyId]) && $aUserInfo['parentid'] != 0) {//如果当前用户没有查询到相关报表数据
            $aTmp = array();
            //当前用户信息
            $aTmp[$iProxyId]["username"] = $aUserInfo['username'];
            $aTmp[$iProxyId]["maxpoint"] = $aUserInfo['maxpoint'] * 100;
            if ($aUserInfo['usertype'] == 0) {
                $aTmp[$iProxyId]["groupname"] = "会员";
            } else {
                $aTmp[$iProxyId]["groupname"] = $this->getGroupName($aUserInfo['usertype'],$aUserInfo['parenttree']);
            }
            $aTmp[$iProxyId]["payment"] = 0;
            $aTmp[$iProxyId]["activity"] = 0;
            $aTmp[$iProxyId]["withdraw"] = 0;
            $aTmp[$iProxyId]["bets"] = 0;
            $aTmp[$iProxyId]["points"] = 0;
            $aTmp[$iProxyId]["realbets"] = 0;
            $aTmp[$iProxyId]["bonus"] = 0;
            $aTmp[$iProxyId]["profit"] = 0;
            $aResult = $aTmp + $aResult;
        }
        if ($iProxyId > 0 && $aUserInfo['parentid'] != 0) {//代理自身数据
            $aProxySelf = $aResult[$iProxyId];
        }
        unset($aResult[$iProxyId]);
        $aAllResult = array();
        if (!empty($aResult) || !empty($aProxySelf)) {//没有相关数据
            $aAllResult = array_values($aResult);
            if ($aUserInfo['parentid'] != 0) {
                array_unshift($aAllResult,$aProxySelf);
            }
            $aAllResult['userdata'] = $aTotal;
        }
        return $aAllResult;
    }
    /**
     * 获取用户分类报表
     */
    public function getUserCategory($sWhere,$iPageRecord = 0, $iCurrentPage = 0){
        $aInsertData = array();
        // 按玩法/模式分类查询用户投注次数、投注总额、总奖金
        $sSql = "SELECT p.`lotteryid`, p.`methodid`, l.`cnname`, m.`methodname`, COUNT(DISTINCT p.`userid`) AS count_uid, SUM(p.`totalprice`) AS totalprice, SUM(p.`bonus`) AS totalbonus FROM `projects` AS p LEFT JOIN `usertree` AS ut ON (p.`userid` = ut.`userid`) LEFT JOIN `lottery` AS l ON (p.`lotteryid` = l.`lotteryid`) LEFT JOIN `method` AS m ON (p.`methodid` = m.`methodid`) WHERE $sWhere AND p.`istester` = 0 AND p.`iscancel` = 0 GROUP BY p.`methodid`";
        $aAllResult = $this->oDB->getAll($sSql);
        foreach ($aAllResult as $v) {
            $key = $v['lotteryid'] . '_' . $v['methodid'];
            $aInsertData[$key] = array(
                'lotteryid'  => $v['lotteryid'],
                'methodid'   => $v['methodid'],
                'cnname'   => $v['cnname'],
                'methodname'   => $v['methodname'],
                'count_uid'   => $v['count_uid'],
                'sell'       => $v['totalprice'],
                'bonus'      => $v['totalbonus']
            );
        }
        // 按玩法/模式分类查询用户投注次数、投注总额、总奖金
        $sSql = "SELECT p.`lotteryid`, p.`methodid`, l.`cnname`, m.`methodname`, COUNT(DISTINCT p.`userid`) AS count_uid, SUM(p.`totalprice`) AS totalprice, SUM(p.`bonus`) AS totalbonus FROM `history_projects` AS p LEFT JOIN `usertree` AS ut ON (p.`userid` = ut.`userid`) LEFT JOIN `lottery` AS l ON (p.`lotteryid` = l.`lotteryid`) LEFT JOIN `method` AS m ON (p.`methodid` = m.`methodid`) WHERE $sWhere AND p.`istester` = 0 AND p.`iscancel` = 0 GROUP BY p.`methodid`";
        $aAllResult = $this->oDB->getAll($sSql);
        foreach ($aAllResult as $v) {
            $key = $v['lotteryid'] . '_' . $v['methodid'];
            if (array_key_exists($key,$aInsertData)){
                $aInsertData[$key]['sell'] = $aInsertData[$key]['sell'] + $v['totalprice'];
                $aInsertData[$key]['bonus'] = $aInsertData[$key]['bonus'] + $v['totalbonus'];
                $aInsertData[$key]['usercount'] = $aInsertData[$key]['usercount'] + $v['usercount'];
            }else{
                $aInsertData[$key] = array(
                    'lotteryid'  => $v['lotteryid'],
                    'methodid'   => $v['methodid'],
                    'cnname'   => $v['cnname'],
                    'methodname'   => $v['methodname'],
                    'count_uid'  => $v['count_uid'],
                    'sell'       => $v['totalprice'],
                    'bonus'      => $v['totalbonus']
                );
            }
        }
        /* 计算真实账号返点值 */
        $sSql = "SELECT p.`userid`,p.`lotteryid`,p.`methodid`,p.`modes`,SUM(udp.`diffmoney`) AS totalpoints, "
            . "SUM(IF(p.`userid`=udp.`userid`,`diffmoney`,0)) AS selfpoints,SUM(IF(p.`userid`<>udp.`userid`&&udp.`status`=1,`diffmoney`,0)) AS parentspoints "
            . "FROM `projects` AS p LEFT JOIN `usertree` AS ut ON (p.`userid` = ut.`userid`) LEFT JOIN `userdiffpoints` AS udp ON(p.`projectid`=udp.`projectid` AND p.`modes`=udp.`modes`) "
            . "WHERE $sWhere AND p.`istester`=0 AND p.`iscancel`=0 "
            . "GROUP BY p.`lotteryid`,p.`methodid`";
        $aAllResult = $this->oDB->getAll($sSql);
        foreach ($aAllResult as $v) {
            $key = $v['lotteryid'] . '_' . $v['methodid'];
            $aInsertData[$key]['totalpoints'] = $v['totalpoints'];
            $aInsertData[$key]['selfpoints'] = $v['selfpoints'];
            $aInsertData[$key]['parentspoints'] = $v['parentspoints'];
        }
        /* 计算真实账号返点值 */
        $sSql = "SELECT p.`userid`,p.`lotteryid`,p.`methodid`,p.`modes`,SUM(udp.`diffmoney`) AS totalpoints, "
            . "SUM(IF(p.`userid`=udp.`userid`,`diffmoney`,0)) AS selfpoints,SUM(IF(p.`userid`<>udp.`userid`&&udp.`status`=1,`diffmoney`,0)) AS parentspoints "
            . "FROM `history_projects` AS p LEFT JOIN `usertree` AS ut ON (p.`userid` = ut.`userid`) LEFT JOIN `history_userdiffpoints` AS udp ON(p.`projectid`=udp.`projectid` AND p.`modes`=udp.`modes`) "
            . "WHERE $sWhere AND p.`istester`=0 AND p.`iscancel`=0 "
            . "GROUP BY p.`userid`,p.`methodid`,p.`modes`";
        $aAllResult = $this->oDB->getAll($sSql);
        foreach ($aAllResult as $v) {
            $key = $v['lotteryid'] . '_' . $v['methodid'];
            $aInsertData[$key]['totalpoints'] = isset($aInsertData[$key]['totalpoints']) ? $aInsertData[$key]['totalpoints'] + $v['totalpoints'] : $v['totalpoints'];
            $aInsertData[$key]['selfpoints'] =  isset($aInsertData[$key]['selfpoints']) ? $aInsertData[$key]['selfpoints'] + $v['selfpoints'] : $v['selfpoints'];
            $aInsertData[$key]['parentspoints'] =  isset($aInsertData[$key]['parentspoints']) ? $aInsertData[$key]['parentspoints'] + $v['parentspoints'] : $v['parentspoints'];
        }
        foreach ($aInsertData as &$v) {
            $v['real'] = $v['sell'] - $v['selfpoints'] - $v['parentspoints'];
        }
        return json_encode(array_values($aInsertData));
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
     * 获取投注人数和次数
     * @author pierce
     * @date 2017-09-07
     * @param $iUserId
     * @return array
     */
    public function getCountUser($sWhere,$iUserId){
        return $this->oDB->getOne("SELECT COUNT(DISTINCT ur.userid) AS usercount FROM user_report AS ur LEFT JOIN usertree AS ut ON ur.userid = ut.userid  WHERE $sWhere AND `bets` > 0 AND ( ur.`userid` = '".$iUserId."' OR FIND_IN_SET('".$iUserId."', ur.parenttree))");
    }
}