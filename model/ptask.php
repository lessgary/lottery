<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/16
 * Time: 10:55
 */

class model_ptask extends basemodel {
    /**
     * 追号列表查询
     *
     * @param integer $iUserId
     * @param string $sField
     * @param string $sCondtion
     * @param string $sOrderBy
     * @param integer $iPageRecord
     * @param integer $iCurrPage
     * @return array
     */
    function taskgetList( $sField = "", $sCondtion = "", $sOrderBy = "", $iPageRecord = 25, $iCurrPage = 1) {
        $aArr = array("affects" => 0, "results" => array());
        $sTableName = "`tasks` AS T "
            . "LEFT JOIN `usertree` AS UT ON (T.`userid`=UT.`userid`) ";
        if (empty($sField)) {
            $sField = "T.*,UT.`username`";
        } else {
            $sField = daddslashes($sField);
        }
        $iPageRecord = isset($iPageRecord) && is_numeric($iPageRecord) ? intval($iPageRecord) : 0;
        $sOrderBy = empty($sOrderBy) ? " " : " Order BY " . $sOrderBy;
        if ($iPageRecord == 0) {
            return $this->oDB->getAll("SELECT " . $sField . " FROM " . $sTableName . " where " . $sCondtion . $sOrderBy);
        }
        $iCurrPage = isset($iCurrPage) && is_numeric($iCurrPage) ? intval($iCurrPage) : 1;
        //获取总数SQL
        $sCountTableName = "`tasks` AS T LEFT JOIN `usertree` AS UT ON (T.`userid`=UT.`userid`) ";
        $sCountSql = " SELECT COUNT(*) AS TOMCOUNT FROM " . $sCountTableName . " WHERE " . $sCondtion;
        return $this->oDB->getPageResult($sTableName, $sField, $sCondtion, $iPageRecord, $iCurrPage, $sOrderBy, '', $sCountSql);
    }
}