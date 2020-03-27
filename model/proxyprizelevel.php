<?php

/**
 * 奖级模型
 *
 */
class model_proxyprizelevel extends basemodel {

    public $_errMsg; //错误消息
    function __construct($aDBO = array()) {
        parent::__construct($aDBO);
    }

    /**
     * 获取奖组信息列表
     *
     * @param string $sFields
     * @param string $sCondition
     * @param string $sOrderBy
     * @param integer $iPageRecord
     * @param integer $iCurrentPage
     * @return array
     * @author mark
     */
    function prizelevelGetList($sFields = '', $sCondition = '', $sOrderBy = '', $iPageRecord = 0, $iCurrentPage = 0) {
        if (empty($sFields)) {
            $sFields = "A.*";
        }
        if (empty($sCondition)) {
            $sCondition = " 1 ";
        }
        $iPageRecord = is_numeric($iPageRecord) ? intval($iPageRecord) : 0;
        if ($iPageRecord <= 0) {
            $iPageRecord = 0;
        }
        $sTableName = "`proxy_prizelevel` AS A ";
        if ($iPageRecord == 0) {
            if (!empty($sOrderBy)) {
                $sOrderBy = " ORDER BY " . $sOrderBy;
            }
            return $this->oDB->getAll("SELECT " . $sFields . "FROM " . $sTableName . " WHERE " . $sCondition . $sOrderBy);
        }
        return $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecord, $iCurrentPage, $sOrderBy);
    }
    /**
     * desc 更新返点等级
     *  @author rhovin 2017-07-20
     *
     */
    function updateUserpoint($aData, $iPgId , $lvtopid) {
        $aUserPoint = $aData['userpoint'];
        $aPrize = $aData['prize'];
        $aLastProfit = $aData['lastprofit'];
         try {
            $this->oDB->doTransaction();
            $uSql = "SELECT pgtype FROM users WHERE userid = '$lvtopid'";
            $aPgetype = $this->oDB->getOne($uSql);
            $pSql = "SELECT `prizegroupid` FROM prizegroup WHERE lotteryid=1 AND type='".$aPgetype['pgtype']."'";
            $aPgroup = $this->oDB->getOne($pSql);
            $sSql = "SELECT p.userpoint,p.prize,p.methodid FROM `method` m LEFT JOIN proxy_prizelevel p USING(methodid) WHERE m.lotteryid=1 AND m.`pid`=0 AND m.`methodname`='前三直选' AND p.level=1 AND p.`lvtopid`='$lvtopid' AND p.`prizegroupid`='" .$aPgroup['prizegroupid']. "'";
            $aTempPoint = $this->oDB->getOne($sSql);
            foreach ($aUserPoint as $iMethodId => $aLeval) {
                if(!empty($aLeval)) {
                    foreach ($aLeval as $level=> $userpoint) {
                        if(isset($aPrize[$aTempPoint['methodid']][$level]) && $aPrize[$aTempPoint['methodid']][$level] != 1800) {
                            throw new Exception("保存失败,前三直选最低奖金不能修改");
                        }
                        if($userpoint < 0) {
                            throw new Exception("保存失败,最高返点不能小于0");
                        }
                        $prize =addslashes($aPrize[$iMethodId][$level]);
                        $lastprofit =addslashes($aLastProfit[$iMethodId][$level]);
                        if($prize < 0) {
                            throw new Exception("保存失败,最低奖金不能小于0");
                        }
                         if($lastprofit <= 0) {
                            throw new Exception("保存失败,公司抽水不能小于等于0");
                        }
                        $sSql = "UPDATE `proxy_prizelevel` SET `userpoint`='${userpoint}',`prize`='${prize}' WHERE `level`='${level}' AND `methodid`='${iMethodId}' AND `lvtopid`='${lvtopid}' AND `prizegroupid`='${iPgId}'";
                       $this->oDB->query($sSql);
                       if ($this->oDB->errno() > 0) {
                           throw new Exception("更新失败");
                       }
                    }
                }else
                    throw new Exception("没有需要更新的数据");
            }
            $oUser = new model_puser();
            $fMaxPoint = $oUser->getCQSSCQSpoint($lvtopid);
            $oProxyConfig = new model_proxyconfig();
            $pcMaxPoint = $oProxyConfig->getConfigs($lvtopid,"registered_pc_proxy_point");//pc注册用户最大返点
            $appMaxPoint = $oProxyConfig->getConfigs($lvtopid,"registered_app_proxy_point");//app注册用户最大返点
            if($fMaxPoint < $pcMaxPoint) {
                throw new Exception("重庆时时彩前三直选最高返点不能低于当前网站配置的注册用户最高返点");
            }
            if($fMaxPoint < $appMaxPoint) {
                throw new Exception("重庆时时彩前三直选最高返点不能低于当前APP配置的注册用户最高返点");
            }

            $this->oDB->doCommit();
            return true;
         } catch (Exception $e) {
            $this->_errMsg = $e->getMessage();
            $this->oDB->doRollback();
            return false;
         }
        
    }
    
    
    /**
     * 同步/修复所有商户缺失的奖金组
     * @return boolean
     */
    public function syncAllTopProxyPrize() {
        $sSql = "INSERT IGNORE INTO `proxy_prizelevel`(lvtopid,`prizegroupid`,`methodid`,`level`,`prize`,`userpoint`) "
            . "SELECT u.`lvtopid`,`prizegroupid`,`methodid`,`level`,`prize`,`userpoint` "
            . "FROM `prizelevel` AS pl LEFT JOIN prizegroup AS pg USING(`prizegroupid`) "
            . "LEFT JOIN `proxy_lottery_set` AS pls ON pg.`lotteryid`=pls.`lotteryid` AND pls.`lvtopid`<>0 "
            . "LEFT JOIN users AS u ON u.`userid`=u.`lvtopid` AND pls.`lvtopid`=u.`lvtopid` "
            . "WHERE pg.`type` IN(u.`pgtype`,4) AND pl.`isclose`=0 "
            . "AND NOT EXISTS(SELECT 1 FROM `proxy_prizelevel` WHERE `lvtopid`=u.`lvtopid` "
            . "AND pl.`prizegroupid`=`prizegroupid` AND pl.`methodid`=`methodid` AND pl.`level`=`level` AND `isclose`=0) "
            . "ORDER BY u.`lvtopid`,`prizegroupid`,`methodid`,`level`";
        $this->oDB->query($sSql);
        if($this->oDB->errno() > 0) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 获取商户对应的奖组信息列表，启用缓存
     *
     * @date     2017-09-14
     * @author   james liang
     *
     * @param   int  $iLvtopId        商户id
     * @param   int  $methodGroupId   玩法组id
     * @return  array  全部奖组信息
     */
    public function getPrizeListByLvtopId( $iLvtopId, $methodGroupId=0 ){
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $aPrize = $oMemCache->getOne('aPrizeList_' . $iLvtopId);
        $sql = "select * from `proxy_prizelevel` where `lvtopid`='{$iLvtopId}' and `isclose`='0'";
        if (empty($aPrize)) {
            $aPrize = $this->oDB->getAll($sql);
            $oMemCache->insert('aPrizeList_' . $iLvtopId, $aPrize, 86400); // 缓存一天
        }

        if ($methodGroupId != 0){
            $tmp = [];
            foreach ($aPrize as $list){
                if ($methodGroupId == $list['methodid']){
                    $tmp[] = $list;
                }
            }
            $aPrize = $tmp;
        }
        return $aPrize;
    }

}

?>
