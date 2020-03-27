<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/6/26
 * Time: 10:11
 */
/* * ***************************[ 宏定义账变ID对应类型关系 ]********************* */
define("ORDER_TYPE_CZCG", 1);   // 账户充值           pid=0   + 游戏币 (我要充值)
define("ORDER_TYPE_TXDJ", 2);   // 提现申请           pid=0   - 游戏币
define("ORDER_TYPE_TXJD", 3);   // 提现失败           pid=0   + 游戏币
define("ORDER_TYPE_TXCG", 4);   // 提现成功           pid=0   - 游戏币
define("ORDER_TYPE_XYCZ", 5);   // 信用充值           pid=0   + 游戏币
define("ORDER_TYPE_XYKJ", 6);   // 信用扣减           pid=0   - 游戏币
define("ORDER_TYPE_JRYX", 7);   // 投注扣款       pid=0   - 游戏币
define("ORDER_TYPE_YXKK", 8);   // 真实扣款        pid=0   - 游戏币
define("ORDER_TYPE_ZHKK", 9);   // 追号扣款        pid=0   - 游戏币
define("ORDER_TYPE_DQZHFK", 10);  // 追号返款    pid=0   + 游戏币
define("ORDER_TYPE_XSFD", 11);   // 游戏返点        pid=0   + 游戏币
define("ORDER_TYPE_JJPS", 12);   // 奖金派送        pid=0   + 游戏币
define("ORDER_TYPE_CDFK", 13);   // 撤单返款        pid=0   + 游戏币
define("ORDER_TYPE_CDFKSP", 99);   // 撤单返款        pid=0   + 游戏币[已经真实扣款后的返款]
define("ORDER_TYPE_CDSXF", 14);   // 撤单手续费      pid=0   - 游戏币
define("ORDER_TYPE_CXFD", 15);   // 撤销返点        pid=0   - 游戏币
define("ORDER_TYPE_CXPJ", 16);   // 撤消派奖        pid=0   - 游戏币
define("ORDER_TYPE_XEKC", 17);   // 小额扣除           pid=0   - 游戏币
define("ORDER_TYPE_XEJS", 18);   // 小额接收           pid=0   + 游戏币
define("ORDER_TYPE_TSJEQL", 19);   // 特殊金额清理    pid=0   - 游戏币
define("ORDER_TYPE_TSJEZL", 20);   // 特殊金额整理    pid=0   + 游戏币
define("ORDER_TYPE_LPCZ", 21);   // 理赔充值           pid=0   + 游戏币
define("ORDER_TYPE_XTKJ", 22);   // 系统扣减           pid=0   - 游戏币
define("ORDER_TYPE_CZKF", 30);   // 充值扣费        pid=0   - 游戏币
define("ORDER_TYPE_SJCZ", 31);   // 上级充值        pid=0   + 游戏币
define("ORDER_TYPE_HDHB", 32);   // 活动礼金        pid=0   + 游戏币
define("ORDER_TYPE_ZZZC", 33);   // 转账转出        pid=0   - 游戏币
define("ORDER_TYPE_ZZZR", 34);   // 转账转入        pid=0   + 游戏币
class model_porders extends basemodel {
    /**
     * 构造函数
     * @access        public
     * @return        void
     */
    function __construct($aDBO = array()) {
        parent::__construct($aDBO);
    }

    public static $aOrderTypes = array(
        ORDER_TYPE_CZCG => '',
        ORDER_TYPE_CXFD
    );


    /**
     * 查看账变，可以自定义查询条件[带分页效果][后台调用]
     * @author  Shawn
     * @param   string  $sFields      // 要查询的内容，表别名:usertree=>ut,orders=>o,ordertype=>ot
     * @param   string  $sCondition   // 附加的查询条件，以AND 开始
     * @param   int     $iPageRecords // 每页显示的条数
     * @param   int     $iCurrPage    // 当前页
     * @return  array
     * 最后效验: 2011-01-10 05:09 By Shawn
     */
    public function & getAdminOrderList($sFields = "*", $sCondition = "1",$sOrderBy, $iPageRecords = 25, $iCurrPage = 1, $sHistory = "") {
        if ($sOrderBy == "DESC"){
            $sTableA = "`orders` AS o " .
                " LEFT JOIN `usertree` AS ut ON ut.`userid`=o.`fromuserid`" .
                " LEFT JOIN `projects` AS P ON (o.`projectid`=P.`projectid`) LEFT JOIN lottery AS l ON(o.lotteryid = l.lotteryid) LEFT JOIN method AS m ON(o.methodid = m.methodid)";
            $sTableB = "`history_orders` AS o " .
                " LEFT JOIN `usertree` AS ut ON ut.`userid`=o.`fromuserid`" .
                " LEFT JOIN `history_projects` AS P ON (o.`projectid`=P.`projectid`) LEFT JOIN lottery AS l ON(o.lotteryid = l.lotteryid) LEFT JOIN method AS m ON(o.methodid = m.methodid)";
        }else{
            $sTableA = "`history_orders` AS o " .
                " LEFT JOIN `usertree` AS ut ON ut.`userid`=o.`fromuserid`" .
                " LEFT JOIN `history_projects` AS P ON (o.`projectid`=P.`projectid`) LEFT JOIN lottery AS l ON(o.lotteryid = l.lotteryid) LEFT JOIN method AS m ON(o.methodid = m.methodid)";
            $sTableB = "`orders` AS o " .
                " LEFT JOIN `usertree` AS ut ON ut.`userid`=o.`fromuserid`" .
                " LEFT JOIN `projects` AS P ON (o.`projectid`=P.`projectid`) LEFT JOIN lottery AS l ON(o.lotteryid = l.lotteryid) LEFT JOIN method AS m ON(o.methodid = m.methodid)";
        }
        $sFields = "ut.`userid`,ut.`username`,l.`cnname`,m.`methodname`,o.`entry`,o.`title`,o.`amount`,o.`preavailable`,o.`availablebalance`,
        o.`projectid`,o.`touserid`,o.`description`,o.`uniquekey`,o.`ordertypeid`,o.`modes`,P.`issue`, " .
            " o.`times`,o.`transferstatus`, o.`adminname`,o.`clientip`,o.`lotteryid`,o.`methodid`,P.`codetype` ";
        $sOrderBy = empty($sOrderBy) ? " " : " Order BY o.`entry`" . $sOrderBy;
        
        $sCountSqlA = " SELECT COUNT(*) AS TOMCOUNT FROM " . $sTableA . " WHERE " . $sCondition;
        $sCountSqlB = " SELECT COUNT(*) AS TOMCOUNT FROM " . $sTableB . " WHERE " . $sCondition;
        $aCountB = $this->oDB->getOne($sCountSqlB);
        $aCountA = $this->oDB->getOne($sCountSqlA);

        /* 修复分页 add by ben start */
        if ($aCountA['TOMCOUNT'] == 0) {
            $aResult = $this->oDB->getPageResult($sTableB, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '',$sCountSqlB);
        } else if ($aCountB['TOMCOUNT'] == 0) {
            $aResult = $this->oDB->getPageResult($sTableA, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '',$sCountSqlA);
        } else if ($aCountA['TOMCOUNT'] > 0 && $aCountB['TOMCOUNT'] > 0) {
            $ofset = $iPageRecords*$iCurrPage;
            if ($aCountA['TOMCOUNT'] >= $ofset) {
                $aResultA = $this->oDB->getPageResult($sTableA, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '',$sCountSqlA);
                $aResult = [
                    'affects' => $aCountA['TOMCOUNT'] + $aCountB['TOMCOUNT'],
                    'results' => $aResultA['results']
                ];

            } else {
                $iRest = $aCountA['TOMCOUNT']%$iPageRecords;
                $limit = $ofset - $aCountA['TOMCOUNT'];
                if ($limit < $iPageRecords) {
                    $sSql = "select {$sFields} from {$sTableB} where {$sCondition} {$sOrderBy} limit {$limit}";
                    $aResultA = $this->oDB->getPageResult($sTableA, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '',$sCountSqlA);
                    $aResultARest = $this->oDB->getAll($sSql);
                    $not = [];
                    foreach($aResultARest as $item) {
                        $not[] = strval($item['entry']);
                    }
                    $not = implode(',', $not);
                    $sCondition = $sCondition . ' AND `entry` NOT IN (' . $not . ')';
                    $iCurrPage = $iCurrPage - ceil($aCountA['TOMCOUNT']/$iPageRecords);
                    $iPageRecords = $iPageRecords-$limit;
                    $aResultB = $this->oDB->getPageResult($sTableB, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '',$sCountSqlB);
                    $aResult = [
                        'affects' => $aCountA['TOMCOUNT'] + $aCountB['TOMCOUNT'],
                        'results' => array_merge($aResultA['results'], $aResultARest, $aResultB['results'])
                    ];
                } else {
                    $limit = $iPageRecords - $iRest;
                    $sSql = "select {$sFields} from {$sTableB} where {$sCondition} {$sOrderBy} limit {$limit}";
                    $aResultARest = $this->oDB->getAll($sSql);
                    $not = [];
                    foreach($aResultARest as $item) {
                        $not[] = strval($item['entry']);
                    }
                    $not = implode(',', $not);
                    $sCondition = $sCondition . ' AND `entry` NOT IN (' . $not . ')';
                    $iCurrPage = $iCurrPage - ceil($aCountA['TOMCOUNT']/$iPageRecords);
                    $aResultB = $this->oDB->getPageResult($sTableB, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '',$sCountSqlB);
                    $aResult = [
                        'affects' => $aCountA['TOMCOUNT'] + $aCountB['TOMCOUNT'],
                        'results' => $aResultB['results']
                    ];
                }
            }
        }
        /* 修复分页 add by ben end */
        return $aResult;
    }
    /**
     * 返回账变对资金的影响,是加钱还是 扣钱
     */
    public function getOrdersTypeInOut($iOrderId = 0) {
        $aArrIn = array(1, 3, 5, 10, 11, 12, 13, 18, 20, 21, 99, 31, 32,34);
        $aArrOut = array(2, 4, 6, 7, 8, 9, 14, 15, 16, 17, 19, 22, 30,33);
        if (in_array($iOrderId, $aArrIn)) {
            return 1;
        }
        if (in_array($iOrderId, $aArrOut)) {
            return 0;
        }
        return 'undefind';
    }
    /**
     * 查询所有的账变类型
     * @author   Daniel
     * @param    $sReturnType   arr | opts
     * @param    $mSelected     arr | int | string
     * @param     $sAndWhere     string
     * @return   mix // 返回结果集数组,或 html.select.options
     */
    public function getOrderType($sReturnType = 'arr', $mSelected = '', $sAndWhere = '') {
        $sSql = "SELECT * FROM `ordertype` WHERE 1 ";
        $sSql .= $sAndWhere;
        $aReturn = $this->oDB->getDataCached($sSql);
        unset($sSql);
        unset($sAndWhere);
        if ($sReturnType == 'arr') {
            return $aReturn;
        }
        // 返回 html.select.options
        $sReturn = '';
        $aSelect = array();
        if (is_int($mSelected) && $aSelect != -1) {
            foreach ($aReturn as $v) {
                $sSel = $mSelected == $v['id'] ? 'SELECTED' : '';
                $sReturn .= "<OPTION $sSel value=\"" . $v['id'] . "\">" . $v['cntitle'] . "</OPTION>";
            }
            return $sReturn;
        }
        if (is_string($mSelected)) {
            if (strstr($mSelected, ',')) {
                $aSelect = explode(',', $mSelected);
            } else {
                $aSelect[0] = intval($mSelected);
            }
        }
        if (is_array($mSelected)) {
            $aSelect = $mSelected;
        }

        foreach ($aReturn as $v) {
            $sSel = in_array($v['id'], $aSelect) ? 'SELECTED' : '';
            $sReturn .= "<OPTION $sSel value=\"" . $v['id'] . "\">" . $v['cntitle'] . "</OPTION>";
        }
        return $sReturn;
    }
}