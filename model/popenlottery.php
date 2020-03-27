<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/6/17
 * Time: 15:59
 */
class model_openlottery extends basemodel{
    /**
     * 获取开奖记录
     *
     * @param        string        $sFields                查询字段
     * @param        string        $sCondition                查询条件
     * @param        string        $sOrderBy                排序方式
     * @param        int        $iPageRecord                页面记录数
     * @param        int        $iCurrentPage                当前页面
     * @return        array                                开奖记录
     *
     */
    public function getOpenLottery($sFields = '', $sCondition = '', $sOrderBy = '', $iPageRecord = 0, $iCurrentPage = 0) {
        $sTableName = "issueinfo AS ii LEFT JOIN lottery AS l USING (lotteryid) LEFT JOIN proxy_lottery_set AS pls USING (lotteryid)";
        $sFields = "l.`cnname`,ii.`lotteryid`,ii.`code`,ii.`issue`,ii.`saleend`,ii.`verifytime`,ii.`statuscode`, pls.isclose";
        if ($iPageRecord == 0) {//不分页显示
            $sCondition = empty($sCondition) ? "" : " WHERE " . $sCondition;
            $sSql = "SELECT $sFields FROM $sTableName $sCondition $sOrderBy";
            return $this->oDB->getAll($sSql);
        } else {
            $sCondition = empty($sCondition) ? "1" : $sCondition;
            $sCountSql = "SELECT count(*) AS TOMCOUNT FROM ".$sTableName." WHERE " . $sCondition ." GROUP BY ii.`saleend`";
            $iAllCount = $this->oDB->getAll($sCountSql);
            $sCountSql = "SELECT ".count($iAllCount)." AS TOMCOUNT";
            return $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecord, $iCurrentPage, $sOrderBy, '', $sCountSql);
        }
    }
}