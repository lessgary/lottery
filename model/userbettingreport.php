<?php

/**
 * Created by PhpStorm.
 * User: robert
 * Date: 2018/5/25
 * Time: 15:38
 */
class model_userbettingreport extends basemodel
{
    public function __construct()
    {
        parent::__construct();
    }

    public $sTableName = "user_betting_report";

    /**
     * @desc 平台报表列表
     * @author robert
     * @param $sWhere //查询条件
     * @param $sOrderBy //排序字段
     * @param $sOrderByType //排序方式
     * @param $sGroupBy //报表展示方式默认是总表userid
     * @param $iRows //每页展示多少条
     * @param $iPage //第几页
     * @return String {"rows":[{"cell":{"userid":"6","usertype":"0","username":"asd123"}}],"records":"7","page":"1","total":120}
     */
    public function BettingReportList2($sWhere,$iUserId, $sOrderBy = 'id', $sOrderByType = 'DESC', $sGroupBy = "userid", $iRows = 25, $iPage = 1)
    {
        //拼接查询条件
        $sFields = "`vendor_id`,`userid`,`username`,`usertype`,`game_type`,`parenttree`,`day`,`usertype`,`parentid`,`lvproxyid`,SUM(bets) bets,SUM(bettimes) bettimes,SUM(realbets) realbets,SUM(points) points,SUM(bonus) bonus,	count(1) count";
        $sTable = " `" . $this->sTableName . "`";
        $sWhere = empty($sWhere) ? "1" : $sWhere;
        $sOrderBy = "GROUP BY $sGroupBy ORDER BY " . $sOrderBy . ' ' . $sOrderByType;
        $sCountSql = "SELECT count(DISTINCT($sGroupBy)) AS `TOMCOUNT` FROM " . $sTable . "WHERE " . $sWhere;

        $aUserChileInfo = $this->oDB->getAll("SELECT userid FROM `usertree` WHERE `userid`='" . $iUserId . "' OR `parentid`='" . $iUserId . "'");

        var_dump($aUserChileInfo);exit();
        return $this->oDB->getPageResult($sTable, $sFields, $sWhere, $iRows, $iPage, $sOrderBy, '', $sCountSql);
    }




    /**
     * @desc 平台报表列表
     * @author robert
     * @param $sWhere //查询条件
     * @param $iUserId //userid
     * @param $sOrderBy //排序字段
     * @param $sOrderByType //排序方式
     * @param $sGroupBy //报表展示方式默认是总表userid
     * @return array
     */
    public function BettingReportList($sWhere,$iUserId = 0, $sOrderBy = 'id', $sOrderByType = 'DESC', $sGroupBy = "userid")
    {
        //如果查询是总表
        if ($sGroupBy == 'userid'){
            $aUserChileInfo = $this->oDB->getAll("SELECT * FROM `usertree` WHERE `userid`='" . $iUserId . "' OR `parentid`='" . $iUserId . "'");
            foreach ($aUserChileInfo as $v) {
                $iTmpUserId = intval($v['userid']);
                if ($iTmpUserId == $iUserId) {
                    $aUserInfo = $v;
                    $aUserLevel = explode(",", $aUserInfo["parenttree"]);
                    if (empty($aUserInfo["parenttree"])) {
                        $iUserLevel = 2;
                    } else {
                        $iUserLevel = count($aUserLevel) + 2;  //用户树的层级
                    }
                }
                $aChildData[$iTmpUserId]["username"] = $v["username"];
                if ($v['usertype'] == 0) {
                    $aChildData[$iTmpUserId]["groupname"] = "会员";
                } else {
                    $aChildData[$iTmpUserId]["groupname"] = $this->getGroupName($v['usertype'],$v['parenttree']);
                }
            }
        }

        //拼接查询条件
        $sFields = "`vendor_id`,`userid`,`username`,`usertype`,`game_type`,`parenttree`,`day`,`parentid`,`lvproxyid`,SUM(bets) bets,SUM(bettimes) bettimes,SUM(realbets) realbets,SUM(points) points,SUM(bonus) bonus,	count(1) count,SUBSTRING_INDEX(`parenttree`, ',', '".$iUserLevel."') AS `USERTREE`";
        $sTable = " `" . $this->sTableName . "`";
        $sWhere = empty($sWhere) ? "1" : $sWhere;
        $sOrderBy = "GROUP BY $sGroupBy ORDER BY " . $sOrderBy . ' ' . $sOrderByType;
        $sSql="SELECT $sFields FROM $sTable WHERE $sWhere AND ( find_in_set('".$iUserId."', `parenttree`) OR `userid` = '".$iUserId."' ) AND `istester` = 0 ".$sOrderBy;
        $aTemp = $this->oDB->getAll($sSql);
        //如果是总表模式
        if ($sGroupBy == 'userid') {
            $aAllResult = $this->UserDataAssembly($aTemp,$iUserId,$aChildData,$aUserInfo,$sGroupBy);
        }else{
            $aAllResult = $aTemp;
        }
        return $aAllResult;
    }



    /**
     * @desc 总计
     * @author robert
     * @param $sWhere //查询条件
     * @return array
     */
    public function BettingReporStatistics($sWhere,$iUserId)
    {
        //拼接查询条件
        $sql = "SELECT count( DISTINCT userid ) overall,sum( `bets` ) bets,sum( `points` ) points,sum( `realbets` ) realbets,sum( `bonus` ) bonus FROM `user_betting_report` WHERE ";
        $sql .= empty($sWhere) ? "1" : $sWhere."AND ( find_in_set('".$iUserId."', `parenttree`) OR `userid` = '".$iUserId."' ) AND `istester` = 0 ";
        return $this->oDB->getOne($sql);
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
     * @desc 数据组装
     * @author robert
     * @param $iUserId //用户id
     * @return array
     */
    private function UserDataAssembly($aTemp,$iUserId,$aChildData,$aUserInfo,$sGroupBy){
        $aResult = array();
        $aProxySelf = array();
        foreach ($aTemp as $v) {
            $aTree = explode(",",  trim($v["USERTREE"]));
            $iTreeCount = count($aTree);
            if ($v["parentid"] == $iUserId || $v["userid"] == $iUserId) {//如果用户是总代或者以及代理
                $sStrUser = $v["userid"];
                $aResult[$sStrUser]["username"] = $v["username"];
                $aResult[$sStrUser]["day"] = $v["day"];
                if ($v['usertype'] == 0) {
                    $aResult[$sStrUser]["groupname"] = "会员";
                } else {//获取用代理层级
                    $aResult[$sStrUser]["groupname"] = $this->getGroupName($v['usertype'],$v['USERTREE']);
                }
            } else {
                $sStrUser = $aTree[$iTreeCount - 1]; //上级id
            }

            if (empty($aResult[$sStrUser])) {
                $aResult[$sStrUser]['username'] = $aChildData[$sStrUser]['username'];
                $aResult[$sStrUser]['groupname'] = $aChildData[$sStrUser]['groupname'];
            }
            if (isset($aResult[$sStrUser]["bets"])) {
                $aResult[$sStrUser]["bets"] += $v["bets"];
                $aResult[$sStrUser]["points"] += $v["points"];
                $aResult[$sStrUser]["realbets"] += $v["realbets"];
                $aResult[$sStrUser]["bonus"] += $v["bonus"];
                $aResult[$sStrUser]["bettimes"] += $v["bettimes"];
                $aResult[$sStrUser]["count"] += $v["count"];
            } else {
                $aResult[$sStrUser]["bets"] = $v["bets"];
                $aResult[$sStrUser]["points"] = $v["points"];
                $aResult[$sStrUser]["realbets"] = $v["realbets"];
                $aResult[$sStrUser]["bonus"] = $v["bonus"];
                $aResult[$sStrUser]["bettimes"] = $v["bettimes"];
                $aResult[$sStrUser]["count"] = $v["count"];

            }
        }
        if ($iUserId > 0 && !isset($aResult[$iUserId]) && $aUserInfo['parentid'] != 0) {//如果当前用户没有查询到相关报表数据
            $aTmp = array();
            //当前用户信息
            $aTmp[$iUserId]["username"] = $aUserInfo['username'];
            if ($aUserInfo['usertype'] == 0) {
                $aTmp[$iUserId]["groupname"] = "会员";
            } else {
                $aTmp[$iUserId]["groupname"] = $this->getGroupName($aUserInfo['usertype'],$aUserInfo['parenttree']);
            }
            $aTmp[$iUserId]["bets"] = 0;
            $aTmp[$iUserId]["points"] = 0;
            $aTmp[$iUserId]["realbets"] = 0;
            $aTmp[$iUserId]["bonus"] = 0;
            $aTmp[$iUserId]["bettimes"] = 0;
            $aTmp[$iUserId]["count"] = 0;
            $aResult = $aTmp + $aResult;
        }
        if ($iUserId > 0 && $aUserInfo['parentid'] != 0) {//代理自身数据
            $aProxySelf = $aResult[$iUserId];
        }
        unset($aResult[$iUserId]);
        $aAllResult = array();
        if (!empty($aResult) || !empty($aProxySelf)) {//没有相关数据
            $aAllResult = array_values($aResult);
            if ($iUserId > 0 && !isset($aResult[$iUserId]) && $aUserInfo['parentid'] != 0){
                array_unshift($aAllResult,$aProxySelf);
            }
        }
        return $aAllResult;
    }


}