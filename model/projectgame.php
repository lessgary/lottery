<?php

/**
 * desc 投注记录
 * @Rhovin 2018-05-29
 */
class model_projectgame extends basemodel
{


    /**
     * @date 2018-05-30
     * @param  $sWhere
     * @param  $iCurrPage
     * @param  $iPageRecords
     * @author  robert
     * @return  array
     */
    public function getList($sWhere, $iCurrPage = 1, $iPageRecords = 20)
    {
        $sFields = "p.id,p.username,g.name game_name,p.bets,p.realbets,p.winning_amount,p.profit,p.start_time,p.end_time,game_period";
        $sTableName = 'project_game p LEFT JOIN vendor_games g ON g.game_id = p.game_id LEFT JOIN usertree ut on p.userid = ut.userid ';
        $sOrderBy = "ORDER BY p.start_time DESC";
        $result = $this->oDB->getPageResult($sTableName, $sFields, $sWhere, $iPageRecords, $iCurrPage, $sOrderBy, '', $sCountSql = '');
        return $result;

    }


    /**
     * @date 2018-05-30
     * @param  $aData
     * @author  robert
     * @return  array
     */
    public function addBetData($aData)
    {
        $iInserId = $this->oDB->insert('project_game', $aData);
        if ($this->oDB->ar() < 1) {
            return false;
        }
        return $iInserId;
    }

    /**
     * @date 2017-05-30
     * @param  $id
     * @param  $aData
     * @author  robert
     * @return  array
     */
    public function updateSatus($id, $aData)
    {
        return $this->oDB->update('project_game', $aData, "`id` = '" . intval($id) . "'");
    }


    /**
     * @desc 总计
     * @author robert
     * @param $sWhere //查询条件
     * @return array
     */
    public function BettingRecordStatistics($sWhere)
    {

        //拼接查询条件
        $sql = "SELECT COUNT( DISTINCT p.userid ) overall,SUM( p.`bets` ) bets,SUM(p.`points` ) points,SUM(p.`realbets` ) realbets,SUM(  p.`profit` ) profit FROM `project_game` p LEFT JOIN vendor_games g ON g.id = p.game_id LEFT JOIN usertree ut on p.userid = ut.userid  WHERE  ";
        $sql .= empty($sWhere) ? "1" : $sWhere;
        return $this->oDB->getOne($sql);
    }


}
