<?php

/**
 * desc 奖期风控
 * @Rhovin 2017-12-07
 */
class model_lotteryrisk extends basemodel {

    public $_errMsg;
    /**
     * desc 获取投注限额组
     * @date 2017-12-07
     */
    public function getList($iLvtopId ,$iCurrPage = 1,$iPageRecords = 20,$sOrderBy = '') {
         $sWhere = " pl.lvtopid='".$iLvtopId."'" ;
        $sTableName = 'proxy_lotteryrisk pl LEFT JOIN lottery L ON pl.lotteryid=L.lotteryid';
        $sOrderBy = "ORDER BY pl.addtime DESC";        
        $result = $this->oDB->getPageResult($sTableName, $sFields = '*', $sWhere, $iPageRecords, $iCurrPage, $sOrderBy, '', $sCountSql = '');
        return $result;

    }
    /**
     * desc 获取所有总代的奖期风控数据
     * @date 2017-12-28
     */
    public function getAll($sFields = '', $sWhere = '', $sOrderBy = '', $iPageRecords = 0, $iCurrPage = 0) {
        if (empty($sFields)) { // 默认字段
            $sFields = "*";
        }
        if (empty($sWhere)) {
            $sWhere = "1";
        }
        $sTableName = 'proxy_lotteryrisk pl LEFT JOIN lottery L ON pl.lotteryid=L.lotteryid';
        $result = $this->oDB->getPageResult($sTableName, $sFields , $sWhere, $iPageRecords, $iCurrPage, $sOrderBy, '', $sCountSql = '');
        return $result;

    }
    /**
     * desc 添加奖期风控数据
     * @date 2017-12-11
     */
    public function addBetData($aData) {
        $iInserId = $this->oDB->insert('proxy_lotteryrisk', $aData);
         if($this->oDB->ar() < 1) {
             return false;
         }
         return $iInserId;
    }
    /**
     * desc 将期风控状态修改
     * @date 2017-12-12
     */ 
    public function updateSatus($iLvtopId, $id, $aData) {
        return $this->oDB->update('proxy_lotteryrisk', $aData, "`id` = '" . intval($id) . "' AND `lvtopid`= '".$iLvtopId."'");
    }

    /**
     * desc 添加奖期风控数据
     * @date 2017-12-11
     */
    public function createLotteryRisk($iLotteryId = 0, $iIssue = 0, $sDate = '') {
        if (empty($iLotteryId) || empty($iIssue) || empty($sDate)) {
            return -1;
        }
        //查询是否已经存在数据，如有则删除后,重新生成。
        $sSqlHaveDate = "SELECT * FROM `proxy_lotteryrisk` WHERE `joindate`='$sDate' AND `lotteryid`='$iLotteryId' AND `issue`='$iIssue'" . ' LIMIT 1';
        $aHaveData = $this->oDB->getOne($sSqlHaveDate);
        if (!empty($aHaveData)) {
            $this->oDB->delete('proxy_lotteryrisk', "`joindate`='$sDate' AND `lotteryid`='$iLotteryId' AND `issue`='$iIssue'");
        }
        $aInsertData = array();
        // 统计下注详情
        $sBetSql = "SELECT `lotteryid`,count(distinct(`userid`)) AS bet_users,issue,`lvtopid`,COUNT(1) AS bet_count,SUM(`totalprice`) AS totalprice,SUM(`bonus`) AS totalbonus "
              . "FROM `projects` WHERE `lotteryid`='$iLotteryId' AND `issue`='$iIssue' AND `istester`=0 AND `iscancel`=0 "
              . "GROUP BY `lvtopid` having totalbonus > 0";
        $aBetResult = $this->oDB->getAll($sBetSql);
         // 统计中奖详情
        $sBonuSql = "SELECT `lotteryid`,count(distinct(`userid`)) AS bonu_users,`issue`,`lvtopid`,COUNT(1) AS bonu_count "
              . "FROM `projects` WHERE `lotteryid`='$iLotteryId' AND `issue`='$iIssue' AND `istester`=0 AND `iscancel`=0 AND `isgetprize`=1 "
              . "GROUP BY `lvtopid`";
        $aBonuResult = $this->oDB->getAll($sBonuSql);
        if(!empty($aBetResult)) {
            foreach ($aBetResult as $v) {
                $key = $v['lotteryid'] . '_' . $v['issue'].'_'.$v['lvtopid'] ;
                $aBetData[$key] = array(
                    'issue'       => $v['issue'],
                    'lvtopid'     => $v['lvtopid'],
                    'bet_users'   => $v['bet_users'],
                    'bet_count'   => $v['bet_count'],
                    'bet_money'   => $v['totalprice'],
                    'bonu_money'  => $v['totalbonus']
                );
            }
            foreach ($aBonuResult as $v) {
                $key = $v['lotteryid'] . '_' . $v['issue'].'_'.$v['lvtopid'] ;
                $aInsertData[$key] = array(
                    'bonu_count'   => $v['bonu_count'],
                    'bonu_users'   => $v['bonu_users'],
                );
            }
            $aLvtopId = [];
            foreach ($aBetData as $key => $value) {
                $aLvtopId [] = $value['lvtopid'] ;
                $aBetData[$key]['bonu_users'] = isset($aInsertData[$key]['bonu_users']) ? $aInsertData[$key]['bonu_users'] : 0;
                $aBetData[$key]['bonu_count'] = isset($aInsertData[$key]['bonu_count']) ? $aInsertData[$key]['bonu_count'] : 0;
                $aBetData[$key]['bonu_users_percent'] = bcdiv($aBetData[$key]['bonu_users'],$value['bet_users'],2)*100;
                $aBetData[$key]['loss_percent'] = bcdiv(bcsub($value['bonu_money'],$value['bet_money'],2),$value['bet_money'],2);
                $aBetData[$key]['bonu_percent'] = bcdiv($aBetData[$key]['bonu_count'],$value['bet_count'],2)*100;
           }
           $sLvtopid = implode(',',$aLvtopId);
           $sConfigSql = "SELECT lvtopid,configkey,configvalue FROM proxyconfig WHERE lvtopid in(".$sLvtopid.") AND configkey in('bonu_user_percent','bonu_order_percent','bonu_loss_percent')";
           $aConfigData = $this->oDB->getAll($sConfigSql);
           $aLvtopConfig = [];
           foreach ($aConfigData as $key => $value) {
                $aLvtopConfig[$value['lvtopid']][$value['configkey']] = $value['configvalue'];
           }
           $aAddData = [];
           foreach ($aBetData as $key => $value) {
                if($value['loss_percent'] > $aLvtopConfig[$value['lvtopid']]['bonu_loss_percent'] 
                    && $value['bonu_users_percent'] > $aLvtopConfig[$value['lvtopid']]['bonu_user_percent']
                    && $value['bonu_percent'] > $aLvtopConfig[$value['lvtopid']]['bonu_order_percent']
                ) {
                    $aAddData['issue'] = $value['issue'];
                    $aAddData['lvtopid'] = $value['lvtopid'];
                    $aAddData['lotteryid'] = $iLotteryId;
                    $aAddData['bet_users'] = $value['bet_users'];
                    $aAddData['bet_count'] = $value['bet_count'];
                    $aAddData['bet_money'] = $value['bet_money'];
                    $aAddData['bonu_money'] = $value['bonu_money'];
                    $aAddData['bonu_users'] = $value['bonu_users'];
                    $aAddData['bonu_count'] = $value['bonu_count'];
                    $aAddData['joindate'] = $sDate;
                    $aAddData['addtime'] = date("Y-m-d H:i:s" , time());
                    $this->addBetData($aAddData);
                }
            }
           return TRUE;
        } else {
            return [];
        }
    }
    /**
     * desc 中奖详情
     * @date 2017-12-27
     */
    public function getBonuInfo($iLvtopId, $iLotteryId, $issue) {
        $sSql = "SELECT u.username,u.registertime,u.lastip, p.projectid,p.bonus,p.totalprice,p.`code` ,p.writetime,p.methodid,p.lotteryid,p.lvtopid,m.methodname FROM projects p LEFT JOIN users u ON u.userid=p.userid LEFT JOIN method m ON p.methodid=m.methodid WHERE p.issue='".$issue."' AND p.isgetprize=1 AND p.istester=0 AND p.lvtopid='".$iLvtopId."' AND p.lotteryid='".$iLotteryId."'";
        $aData = $this->oDB->getAll($sSql);
        return $aData;
    }
    
    
    /**
     * desc 检查用户投注风控信息
     * @date 2017-12-27
     * @param int $iUserId 用户ID
     * @param int $iLvtopId 总代ID
     * @return boolean TRUE未在风控中|FALSE在风控中
     */
    public function checkLotteryRisk( $iUserId , $iLvtopId ) {
        $sTmpSql = "SELECT count(1) AS bonu_count FROM `proxy_lotteryrisk` AS lr LEFT JOIN `%s` AS p USING(`lotteryid`,`issue`) "
                 . "WHERE lr.`lvtopid`='{$iLvtopId}' AND lr.`status`=0 "
                 . "AND p.`userid`='{$iUserId}' AND p.`isgetprize`=1";
        $sSql = sprintf($sTmpSql, 'projects'); // 关联查询projects
        $aResult = $this->oDB->getOne($sSql);
        if(!empty($aResult) && $aResult['bonu_count'] > 0) {
            return FALSE;
        }
        $sSql = sprintf($sTmpSql, 'history_projects'); // 关联查询history_projects
        $aResult = $this->oDB->getOne($sSql);
        if(!empty($aResult) && $aResult['bonu_count'] > 0) {
            return FALSE;
        }
        return TRUE;
    }
    
    
    /**
     * desc 获取总代风控数据
     * @date 2017-12-29
     */
    public function getCountRiskDataByLvtopId($iLvtopId) {
        $sSql = "SELECT count(*) AS count_risk FROM proxy_lotteryrisk WHERE status=0 AND lvtopid='".$iLvtopId."'";
        return $this->oDB->getOne($sSql);
    }
    
}
