<?php

/**
 * @desc 域名统计
 * @author Ben
 * @date 2017-06-16
 *
 */
class model_domainreport extends basemodel {

    /**
     * 域名统计列表
     * @author Ben
     * @date 2017-06-16
     * @param $sStartTime
     * @param $sEndTime
     * @param $sDomainname
     * @param $iRows
     * @param $iPage
     * @param $iTopId
     * @param $iIsOutSide
     * @return array|bool
     */
    public function getDomainReportList($sStartTime, $sEndTime, $sDomainname, $iRows, $iPage, $iTopId, $iIsOutSide) {
        if (empty($iTopId) || !is_numeric($iTopId)) {
            return false;
        }
        $sWhere = " b.lvtopid = '${iTopId}'";
        if (!empty($sStartTime)) {
            $sWhere .= " AND `a`.`reportdate` >= '${sStartTime}'";
        }

        if (!empty($sEndTime)) {
            $sWhere  .= " AND `a`.`reportdate` <= '${sEndTime}'";
        }

        if ($iIsOutSide) {
            $sSql = "SELECT 
                    `a`.`domainname` AS `domainname`,
                    sum(`a`.`ip`) AS `ip`,
                    sum(`a`.`pv`) AS `pv`
                  FROM 
                    `domain_report` `a` 
                  LEFT JOIN
                    `domains` `b` 
                  ON 
                    `a`.`domainname` = `b`.`domain`
                  WHERE ${sWhere}
                  GROUP BY `a`.`domainname`
                  ORDER BY `ip` DESC";
            $result = $this->oDB->getAll($sSql);
            return ['results' => $result ? $result : [], 'affects' => is_array($result) ? count($result) : 0];
        } else {
            if (!empty($sDomainname)) {
                $sWhere  .= " AND `a`.`domainname` = '${sDomainname}'";
            }
            $sSqlCount = "SELECT 
                    count(1) as `num`
                  FROM 
                    `domain_report` `a` 
                  LEFT JOIN
                    `domains` `b` 
                  ON 
                    `a`.`domainname` = `b`.`domain`
                  WHERE ${sWhere}";
            $count = $this->oDB->getOne($sSqlCount);
            $index = $iRows * ($iPage-1);
            $sSql = "SELECT 
                    `a`.`domainname` AS `domainname`,
                    `a`.`ip` AS `ip`,
                    `a`.`pv` AS `pv`,
                    `a`.`reportdate`
                  FROM 
                    `domain_report` `a` 
                  LEFT JOIN
                    `domains` `b` 
                  ON 
                    `a`.`domainname` = `b`.`domain`
                  WHERE ${sWhere}
                  ORDER BY `a`.`ip` DESC LIMIT {$index},{$iRows}";
            $result = $this->oDB->getAll($sSql);
            return ['results' => $result ? $result : [], 'affects' => is_array($count)&&isset($count['num']) ? $count['num'] : 0];
        }
    }
}