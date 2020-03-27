<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/6/8
 * Time: 18:15
 */
class model_psale extends basemodel
{

    /**
     * 构造函数
     */
    function __construct($aDBO = array())
    {
        parent::__construct($aDBO);
    }

    /**
     * 获取彩种盈亏报表数据
     *
     * @param        string $sCondition 查询条件
     * @param        string $sOrderBy 排序方式
     * @param        int $iPageRecord 页面记录数
     * @param        int $iCurrentPage 当前页面
     * @return        array 彩种盈亏报表数据
     *
     */
    public function getSingleSale($sCondition = '', $sOrderBy = '', $iPageRecord = 0, $iCurrentPage = 0)
    {
        $sFields = "l.cnname AS cnname,l.lotteryid AS lotteryid,m.methodname AS methodname,count(DISTINCT us.`userid`)    AS usercount,SUM(us.`sell`) AS sell,SUM(us.`totalpoints`) AS totalpoints, SUM(us.`sell`-us.`totalpoints`) AS realsell,SUM(us.`bonus`) AS bonus,SUM(us.`sell`-us.`totalpoints`-us.`bonus`) AS settlement";
        $sTable = " user_sales AS us LEFT JOIN lottery AS l ON (us.lotteryid = l.lotteryid) LEFT JOIN method AS m ON (us.methodid = m.methodid)";
        if ($iPageRecord == 0) {//不分页显示
            $sCondition = empty($sCondition) ? "" : " WHERE " . $sCondition;
            $sSql = "SELECT $sFields FROM $sTable $sCondition $sOrderBy";
            return $this->oDB->getAll($sSql);
        } else {
            $sCondition = empty($sCondition) ? "1" : $sCondition;
            $sCountSql = "SELECT count(*) AS TOMCOUNT FROM " . $sTable . " WHERE " . $sCondition . " GROUP BY us.lotteryid, us.methodid";
            $iAllCount = $this->oDB->getAll($sCountSql);
            $sCountSql = "SELECT " . count($iAllCount) . " AS TOMCOUNT";
            return $this->oDB->getPageResult($sTable, $sFields, $sCondition, $iPageRecord, $iCurrentPage, $sOrderBy, '', $sCountSql);
        }
    }

    /**
     * 获取单期盈亏报表数据
     *
     * @param        string        $sFields                查询字段
     * @param        string        $sCondition                查询条件
     * @param        string        $sOrderBy                排序方式
     * @param        int        $iPageRecord                页面记录数
     * @param        int        $iCurrentPage                当前页面
     * @param   int     $iModes                        元角模式
     * @return        array                                单期盈亏报表数据
     *
     */
    public function getSingleSales($sFields = '', $sCondition = '', $sOrderBy = '', $iPageRecord = 0, $iCurrentPage = 0, $iModes = 0) {
        $sOrderBy = $sOrderBy == '' ? $sOrderBy : $sOrderBy.' , sl.id DESC';
        $ext = " IFNULL(SUM(sl.sell),0) AS fsell,IFNULL(SUM(sl.bonus),0) AS fbonus,IFNULL(SUM(sl.totalpoints),0) AS ftotalpoints, ";
        $sFields = empty($sFields) ? " sl.*,(sl.sell-sl.bonus-sl.totalpoints) AS saleresult, ".$ext."
                   isfo.saleend,isfo.writetime,isfo.code,l.cnname,COUNT(DISTINCT userid) AS usercount " : addslashes($sFields);
        $sTable = " user_sales AS sl LEFT JOIN lottery AS l ON (sl.lotteryid = l.lotteryid)
                    LEFT JOIN issueinfo AS isfo ON(sl.issue = isfo.issue AND sl.lotteryid = isfo.lotteryid)";
        if ($iPageRecord == 0) {//不分页显示
            $sCondition = empty($sCondition) ? "" : " WHERE " . $sCondition;
            $sSql = "SELECT $sFields FROM $sTable $sCondition  GROUP BY sl.lotteryid,sl.issue $sOrderBy";
            return $this->oDB->getAll($sSql);
        } else {
            $sCondition = empty($sCondition) ? "1" : $sCondition;
            $sCountSql = "SELECT count(*) AS TOMCOUNT FROM " . $sTable . " WHERE " . $sCondition ." GROUP BY sl.lotteryid,sl.issue";
            $iAllCount = $this->oDB->getAll($sCountSql);
            $sCountSql = "SELECT ".count($iAllCount)." AS TOMCOUNT";
            if ($iModes == -1) {
                $sCondition .= " GROUP BY sl.lotteryid,sl.issue "; //统计全部模式盈亏值
            }else{
                $sCondition .= " GROUP BY sl.lotteryid,sl.issue "; //统计全部模式盈亏值
            }
            $temp = $this->oDB->getPageResult($sTable, $sFields, $sCondition, $iPageRecord, $iCurrentPage, $sOrderBy, '', $sCountSql);
            foreach ($temp['results'] as $key => $val) {
                $temp['results'][$key]['sell'] = $temp['results'][$key]['fsell'];
                $temp['results'][$key]['bonus'] = $temp['results'][$key]['fbonus'];
                $temp['results'][$key]['totalpoints'] = $temp['results'][$key]['ftotalpoints'];
                $temp['results'][$key]['saleresult'] = ($temp['results'][$key]['fsell'] - $temp['results'][$key]['fbonus']-$temp['results'][$key]['ftotalpoints']);
            }
            return $temp;
        }
    }
}
