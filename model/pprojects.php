<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/6/16
 * Time: 17:00
 */
class model_pprojects extends basemodel{
    /**
     * 游戏记录查询
     * @author SAUL
	 * @date   090811
	 *
     * @param 	string    $sFields		查询的内容，默认 *
     * @param 	string    $sCondition	查询条件 在sql语句里的where部分
     * @param 	string    $sOrderBy		排序 默认由小到大
     * @param 	integer   $iPageRecords	每页显示的条数，默认每页25条
     * @param 	integer   $iCurrPage	当前的页码，默认第 1 页
     * @return array
     */
    function  projectGetResult( $sFields = "", $sCondition = "", $sOrderBy = "", $iPageRecords = 25, $iCurrPage = 1) {
        if ($sOrderBy == "DESC"){
            $sTableA = "`projects` AS P  LEFT JOIN `usertree` AS UT ON (P.`userid`=UT.`userid`) LEFT JOIN `method` as ME ON ME.`methodid` = P.`methodid`";
            $sTableB = "`history_projects` AS P  LEFT JOIN `usertree` AS UT ON (P.`userid`=UT.`userid`)  LEFT JOIN `method` as ME ON ME.`methodid` = P.`methodid`";
        }else{
            $sTableB = "`projects` AS P  LEFT JOIN `usertree` AS UT ON (P.`userid`=UT.`userid`)  LEFT JOIN `method` as ME ON ME.`methodid` = P.`methodid`";
            $sTableA = "`history_projects` AS P  LEFT JOIN `usertree` AS UT ON (P.`userid`=UT.`userid`)  LEFT JOIN `method` as ME ON ME.`methodid` = P.`methodid`";
        }
        if (empty($sFields)) {
            $sFields = "P.*,UT.`username`,ME.`is_official`";
        } else {
            $sFields = daddslashes($sFields);
        }
        $iSumA = $this->oDB->getOne("SELECT SUM(P.totalprice) AS totalePrice,SUM(P.bonus) AS totaleBonus FROM ".$sTableA." WHERE ".$sCondition);
        $iSumB = $this->oDB->getOne("SELECT SUM(P.totalprice) AS totalePrice,SUM(P.bonus) AS totaleBonus FROM ".$sTableB." WHERE ".$sCondition);
        $sOrderBy = empty($sOrderBy) ? " " : " Order BY P.`writetime`" . $sOrderBy;
        $sCountSqlA = " SELECT COUNT(*) AS TOMCOUNT FROM " . $sTableA . " WHERE " . $sCondition;
        $sCountSqlB = " SELECT COUNT(*) AS TOMCOUNT FROM " . $sTableB . " WHERE " . $sCondition;
        $aCountB = $this->oDB->getOne($sCountSqlB);
        $aCountA = $this->oDB->getOne($sCountSqlA);
        if ($aCountA['TOMCOUNT'] == 0) {
            $aResult = $this->oDB->getPageResult($sTableB, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '',$sCountSqlB);
            $aResult['sumPrice'] = $iSumA['totalePrice'] + $iSumB['totalePrice'];
            $aResult['sumBonus'] = $iSumA['totaleBonus'] + $iSumB['totaleBonus'];
        } else if ($aCountB['TOMCOUNT'] == 0) {
            $aResult = $this->oDB->getPageResult($sTableA, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '',$sCountSqlA);
            $aResult['sumPrice'] = $iSumA['totalePrice'] + $iSumB['totalePrice'];
            $aResult['sumBonus'] = $iSumA['totaleBonus'] + $iSumB['totaleBonus'];
        } else if ($aCountA['TOMCOUNT'] > 0 && $aCountB['TOMCOUNT'] > 0) {
            $ofset = $iPageRecords*$iCurrPage;
            if ($aCountA['TOMCOUNT'] >= $ofset) {
                $aResultA = $this->oDB->getPageResult($sTableA, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '',$sCountSqlA);
                $aResult = [
                    'affects' => $aCountA['TOMCOUNT'] + $aCountB['TOMCOUNT'],
                    'sumPrice' => $iSumA['totalePrice'] + $iSumB['totalePrice'],
                    'sumBonus' => $iSumA['totaleBonus'] + $iSumB['totaleBonus'],
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
                        $not[] = strval($item['projectid']);
                    }
                    $not = implode(',', $not);
                    $sCondition = $sCondition . ' AND `projectid` NOT IN (' . $not . ')';
                    $iCurrPage = $iCurrPage - ceil($aCountA['TOMCOUNT']/$iPageRecords);
                    $iPageRecords = $iPageRecords-$limit;
                    $aResultB = $this->oDB->getPageResult($sTableB, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '',$sCountSqlB);
                    $aResult = [
                        'affects' => $aCountA['TOMCOUNT'] + $aCountB['TOMCOUNT'],
                        'sumPrice' => $iSumA['totalePrice'] + $iSumB['totalePrice'],
                        'sumBonus' => $iSumA['totaleBonus'] + $iSumB['totaleBonus'],
                        'results' => array_merge($aResultA['results'], $aResultARest, $aResultB['results'])
                    ];
                } else {
                    $limit = $iPageRecords - $iRest;
                    $sSql = "select {$sFields} from {$sTableB} where {$sCondition} {$sOrderBy} limit {$limit}";
                    $aResultARest = $this->oDB->getAll($sSql);
                    $not = [];
                    foreach($aResultARest as $item) {
                        $not[] = strval($item['projectid']);
                    }
                    $not = implode(',', $not);
                    $sCondition = $sCondition . ' AND `projectid` NOT IN (' . $not . ')';
                    $iCurrPage = $iCurrPage - ceil($aCountA['TOMCOUNT']/$iPageRecords);
                    $aResultB = $this->oDB->getPageResult($sTableB, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '',$sCountSqlB);
                    $aResult = [
                        'affects' => $aCountA['TOMCOUNT'] + $aCountB['TOMCOUNT'],
                        'sumPrice' => $iSumA['totalePrice'] + $iSumB['totalePrice'],
                        'sumBonus' => $iSumA['totaleBonus'] + $iSumB['totaleBonus'],
                        'results' => $aResultB['results']
                    ];
                }
            }
        }
        return $aResult;
    }
}