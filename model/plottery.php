<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/6/23
 * Time: 14:53
 */
class model_plottery extends basemodel{

    // 商户彩种表彩种关闭
    const IS_NOT_CLOSE = 0; // 开售
    const IS_CLOSE     = 1; // 停售

    /**
     * 获取彩种列表[联合玩法]
     * @author mark
     * @param  string  $sFields
     * @param  string  $sCondition
     * @param  string  $sOrderBy
     * @param  integer $iPageRecord
     * @param  integer $iCurrentPage
     */
    public function lotteryMethodGetList($sFields = '', $sCondition = '', $sOrderBy = '', $iPageRecord = 0, $iCurrentPage = 0,$lvtopid) {
        if (empty($sFields)) { // 默认字段
            $sFields = "pls.id AS lvtop_lottery,pls.sorts AS lvtopid_sorts,pls.isclose AS lvtopid_isclose, a.*";
        }
        $sCondition = "pls.lvtopid =".$lvtopid." AND pls.lotteryid IN(SELECT lotteryid FROM method GROUP BY lotteryid HAVING (COUNT(methodid) <> SUM(isclose)) OR (COUNT(methodid) = 0))  GROUP BY  pls.`lotteryid`";
        $iPageRecord = intval($iPageRecord);
        $sTableName = "proxy_lottery_set AS pls LEFT JOIN `lottery` AS a ON(pls.lotteryid = a.lotteryid)";
        if ($iPageRecord == 0) {
            return $this->oDB->getAll("SELECT " . $sFields . "FROM " . $sTableName . " WHERE " . $sCondition . ' ' . $sOrderBy);
        }
        $iCurrentPage = intval($iCurrentPage);
        if ($iCurrentPage == 0) {
            $iCurrentPage = 1;
        }
        return $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecord, $iCurrentPage, $sOrderBy);
    }

    /**
     * 获取彩种列表,区分官方和信用玩法
     *
     * @author left
     * @date 2017/10/04
     *
     * @param int $iLvTopId     商户id
     * @param int $iPageRecord  一页显示多少条记录（后续使用）
     * @param int $iCurrentPage 当前第几页（后续使用）
     *
     * @return array
     */
    public function getLotteryMethodList($iLvTopId, $iPageRecord = 0, $iCurrentPage = 0) {

        $iLvTopId = intval($iLvTopId);
        // 获取彩种信息
        $aLotterys = $this->lotteryCache($iLvTopId);
        // 获取商户彩种表
        $aLvTopLotteries = $this->getLvTopLotteries($iLvTopId);

        $aReturn = [];
        foreach($aLvTopLotteries as $k=>$aLvTopLottery) {
            if(!empty($aLotterys[$aLvTopLottery['lotteryid']])) {
                $aReturn[$k] = $aLotterys[$aLvTopLottery['lotteryid']];
                $aReturn[$k]['lvtop_lottery'] = $aLvTopLottery['id'];
                $aReturn[$k]['lvtopid_sorts'] = $aLvTopLottery['sorts'];
                $aReturn[$k]['lvtopid_isclose'] = $aLvTopLottery['isclose'];
                $aReturn[$k]['is_official'] = $aLvTopLottery['is_official'];
                if($aLvTopLottery['is_official'] == 0) {
                    $aReturn[$k]['cnname'] = $aLotterys[$aLvTopLottery['lotteryid']]['cnname'] . model_method::CREDIT_LOTTERY_NAME;
                }
            }
        }
        return array_values($aReturn);
    }

    /**
     * 获取商户彩种列表
     *
     * @param int $iLvTopId  商户id
     * @param int $iId       表id
     *
     * @return array
     */
    public function getLvTopLotteries($iLvTopId, $iId = 0) {
        $iLvTopId = intval($iLvTopId);
        $iId = intval($iId);
        $sWhere = " `lvtopid`={$iLvTopId} ";
        if ($iId > 0) {
            $sWhere .= " AND `id`={$iId} ";
        }
        $sSql = "SELECT * FROM `proxy_lottery_set` WHERE {$sWhere} ORDER BY `sorts`";
        if ($iId > 0) {
            return $this->oDB->getOne($sSql);
        }
        return $this->oDB->getAll($sSql);
    }

    /**
     * 获取商户id
     */
    public function getUserlvtopid($id){
        return $this->oDB->getOne("SELECT lvtopid FROM proxy_lottery_set WHERE id = ".$id);
    }
    /**
     * @desc 开售/停售商户彩种
     * @author pierce
     * @date 2017-06-24
     */
    public function locklottery($iId,$aData = [],$lvtopid) {
        $this->oDB->doTransaction();
        $mResult = $this->oDB->update("proxy_lottery_set", $aData, "`id`='" . $iId . "' AND `lvtopid`='" . $lvtopid . "'");
        if ($mResult) {
            $this->updateLotteryCache($lvtopid);
            $this->oDB->doCommit();
            return TRUE;
        } else {
            $this->oDB->doRollback();
            return FALSE;
        }
        return TRUE;
    }
    
    
    /**
     * 获取彩种缓存信息
     *
     * @param int $iLvtopid        总代ID
     * @param mixed $mLotteryId    彩种ID:只传一个彩种ID，可以传多个彩种ID
     * @param int $iType           返回类型  0：信用彩种 1：官方彩种 2：以id为准的彩种 3：获取官方/信用全部彩种
     *
     * @return array
     */
    public function lotteryCache($iLvtopid, $mLotteryId = NULL, $iType = 2) {
        $iLvtopid = intval($iLvtopid);
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $sProxyLotteryKey = $oMemCache->getOne('sProxyLotteryKey');
        if (empty($sProxyLotteryKey)) {
            $this->updateLotteryCache();
            $sProxyLotteryKey = $oMemCache->getOne('sProxyLotteryKey');
        }
        $aProxyLottery = $oMemCache->getOne("aProxyLottery_{$iLvtopid}_{$sProxyLotteryKey}");
        if (empty($aProxyLottery)) {
            $this->updateLotteryCache($iLvtopid);
            $aProxyLottery = $oMemCache->getOne("aProxyLottery_{$iLvtopid}_{$sProxyLotteryKey}");
        }
        $aRestult = array();
        $bIsSingle = false; // 标识是否返回单个彩种
        /* @var $oLottery model_lottery */
        $oLottery = A::singleton('model_lottery', $GLOBALS['aSysDbServer']['report']);
        if($mLotteryId === NULL) { // 获取全部彩种
            $aLotterys = $oLottery->lotteryCache( array_column($aProxyLottery, 'lotteryid') );
            foreach($aProxyLottery as $iKey => $proxyLottery) {
                if(empty($aLotterys[$proxyLottery['lotteryid']])) { // 未获取到正确的彩种
                    continue;
                }
                $aRestult[] = $proxyLottery + $aLotterys[$proxyLottery['lotteryid']];
            }
        } else if (is_array($mLotteryId)) { // 取指定彩种数组
            $aLotterys = $oLottery->lotteryCache( $mLotteryId );
            foreach ($mLotteryId as $iLotteryId) {
                $iLotteryId = intval($iLotteryId);
                foreach ($aProxyLottery as $iKey=>$proxyLottery) {
                    if ($proxyLottery['lotteryid'] == $iLotteryId && !empty($aLotterys[$iLotteryId])) {
                        $aRestult[] = $proxyLottery + $aLotterys[$iLotteryId];
                    }
                }
            }
        } else { // 获取单个彩种
            $bIsSingle = true;
            $iLotteryId = intval($mLotteryId);
            $aLottery = $oLottery->lotteryCache($iLotteryId);
            foreach ($aProxyLottery as $iKey=>$proxyLottery) {
                if ($proxyLottery['lotteryid'] == $iLotteryId && !empty($aLottery)) {
                    $aRestult[] = $proxyLottery + $aLottery;
                }
            }
        }

        $aReturn = [];
        if ($iType == 0) { // 信用玩法
            foreach ($aRestult as $k=>$v) {
                if ($v['is_official'] == model_method::IS_NOT_OFFICIAL) {
                    if ($bIsSingle) {
                        $aReturn = $v;
                    } else {
                        $aReturn[] = $v;
                    }
                }
            }
        } elseif ($iType == 1) { // 官方玩法
            foreach ($aRestult as $k=>$v) {
                if ($v['is_official'] == model_method::IS_OFFICIAL) {
                    if ($bIsSingle) {
                        $aReturn = $v;
                    } else {
                        $aReturn[] = $v;
                    }
                }
            }
        } elseif ($iType == 2) {
            foreach ($aRestult as $k=>$v) {
                if(!isset($aReturn[$v['lotteryid']]) || (isset($aReturn[$v['lotteryid']]) && $v['is_official'] == model_method::IS_OFFICIAL)) {
                    $aReturn[$v['lotteryid']] = $v;
                }
            }
            if ($bIsSingle) {
                $iLotteryId = intval($mLotteryId);
                $aReturn = $aReturn[$iLotteryId];
            }
        } else {
            $aReturn = $aRestult;
        }

        return $aReturn;
    }
    
    /**
     * 更新彩种缓存
     */
    private function updateLotteryCache($iLvtopid = NULL) {
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $sCondition = '';
        if($iLvtopid === NULL) { // 更新全部缓存
            $sProxyLotteryKey = date('dHis') . str_pad(mt_rand(0, 9999), '4', '0', STR_PAD_LEFT); // 生成新缓存地址, 以日期开头则最多有效1个月
            $oMemCache->insert('sProxyLotteryKey', $sProxyLotteryKey, 86400); // MemCache缓存商户彩种地址
        } else {
            $sProxyLotteryKey = $oMemCache->getOne('sProxyLotteryKey');
            if (empty($sProxyLotteryKey)) { // 获取缓存地址失效, 全部重新生成缓存
                return $this->updateLotteryCache();
            }
            $sCondition = " AND `lvtopid`='" . intval($iLvtopid) . "'";
        }
        // 只查询管理后台开启的彩种
        $sSql = "SELECT * FROM `proxy_lottery_set` WHERE lotteryid IN(SELECT DISTINCT lotteryid FROM method WHERE isclose=0)" . $sCondition;
        $aResult = $this->oDB->getAll($sSql);
        $aLotteryData = array();
        if (empty($aResult)) {
            return FALSE;
        }
        // 归类
        foreach ($aResult as $aData) {
            $aLotteryData[$aData['lvtopid']][] = $aData;
        }
        // 生成缓存
        foreach($aLotteryData as $lvtopid => $lotteryData) {
            $oMemCache->insert("aProxyLottery_{$lvtopid}_{$sProxyLotteryKey}", $lotteryData, 86400); // MemCache缓存商户彩种数据
        }
        return TRUE;
    }
    
    
    /**
     * 同步/修复所有商户/总代缺失的彩种，默认关闭需要商户/总代自行开启
     * @param String $sCondition 过滤SQL条件
     * @return boolean
     */
    public function syncTopProxyzLottery($sCondition = '') {
        $sCondition = !empty($sCondition) ? " AND {$sCondition}" : '';
        $sSql = "INSERT IGNORE INTO `proxy_lottery_set`(lvtopid,`lotteryid`,`sorts`,`ishot`,`isnew`,`isclose`) "
            . "SELECT ut.`lvtopid` AS lvtopid,`lotteryid`,`sorts`,`ishot`,`isnew`,1 AS `isclose` "
            . "FROM  `lottery` AS l LEFT JOIN usertree AS ut ON ut.`parentid`=0 "
            . "WHERE NOT EXISTS(SELECT 1 FROM `proxy_lottery_set` WHERE `lvtopid`=ut.`lvtopid` AND `lotteryid`=l.`lotteryid`) "
            .  $sCondition;
        $this->oDB->query($sSql);
        if($this->oDB->errno() > 0) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 根据商户分配彩种
     *
     * @author left
     * @date 2017/10/9
     *
     * @param   string  $sExecution    操作标识 add:添加 delete:删除
     * @param   array   $aUpdateData   操作二维数组
     *  每一个一维数组数据：
     *      lotteryid   彩种id
     *      lvtopid     彩种id
     *      is_official  官方/信用玩法
     *
     * @return bool
     */
    public function updateLvTopLotteries($sExecution, $aUpdateData = []) {
        if(empty($aUpdateData)) {
            return false;
        }

        // 获取总后台彩种官方/信用
        /* @var $oMethod model_method */
        $oMethod = A::singleton('model_method', $GLOBALS['aSysDbServer']['report']);
        $aAdminLotteries = $oMethod->getOfficialMethodLotteryList();

        // 检验数据
        foreach($aUpdateData as $k=>&$v) {
            $v['lotteryid'] = intval($v['lotteryid']);
            if ($v['lotteryid'] <= 0) {
                return false;
            }

            $v['lvtopid'] = intval($v['lvtopid']);
            if ($v['lvtopid'] <= 0) {
                return false;
            }

            $v['is_official'] = intval($v['is_official']);
            if ($v['is_official'] != 1 && $v['is_official'] != 0) {
                return false;
            }
            $aTemp['lotteryid'] = $v['lotteryid'];
            $aTemp['is_official'] = $v['is_official'];
            if (!in_array($aTemp, $aAdminLotteries)) {
                return false;
            }
        }
        unset($v); // &$v 会影响到后续使用$v需要释放掉$v

        $sLotteryTable = 'proxy_lottery_set';
        $sPrizeLevelTable = 'proxy_prizelevel';

        $this->oDB->doTransaction();
        if ($sExecution === 'add') {

            // 新分配的彩种均做停售处理
            $bIsClose = self::IS_CLOSE;
            foreach($aUpdateData as $k=>$v) {
                $sSql = "INSERT IGNORE INTO `{$sLotteryTable}`(`lvtopid`, `lotteryid`, `sorts`, `is_official`, `isclose`) VALUES('{$v['lvtopid']}', '{$v['lotteryid']}', '{$v['lotteryid']}', '{$v['is_official']}', {$bIsClose})";
                $bResult = $this->oDB->query($sSql);
                if ($bResult === false) {
                    $this->oDB->doRollback();
                    return false;
                }
            }
            // 同步奖金组
            /* @var $oProxyPrizeLevel model_proxyprizelevel */
            $oProxyPrizeLevel = A::singleton('model_proxyprizelevel');
            $oProxyPrizeLevel->syncAllTopProxyPrize();
        } elseif ($sExecution === 'delete') {
            foreach($aUpdateData as $k=>$v) {
                // 删除 proxy_lottery_set 表
                $sCondition = " `lotteryid`= '{$v['lotteryid']}' AND `lvtopid`='{$v['lvtopid']}' AND `is_official`='{$v['is_official']}'";
                $bResult = $this->oDB->delete($sLotteryTable, $sCondition);
                if ($bResult === false) {
                    $this->oDB->doRollback();
                    return false;
                }
                // 删除 proxy_prizelevel 表
                // 查询所有玩法
                $sSql = "SELECT `methodid` FROM `method` WHERE `lotteryid`='{$v['lotteryid']}' AND `is_official`='{$v['is_official']}'";
                $aMthods = $this->oDB->getAll($sSql);
                $aMthods = array_column($aMthods, 'methodid');

                if(!empty($aMthods)) {
                    $sMethods = implode(',', $aMthods);
                    $sPrizeCondition = "`lvtopid`='{$v['lvtopid']}' AND `methodid` IN ({$sMethods})";
                    $bPrizeResult = $this->oDB->delete($sPrizeLevelTable, $sPrizeCondition);
                    if ($bPrizeResult === false) {
                        $this->oDB->doRollback();
                        return false;
                    }
                } else {
                    continue;
                }
            }
        } else {
            $this->oDB->doRollback();
            return false;
        }
        $this->oDB->doCommit();
        // 更新商户缓存
        $this->updateLotteryCache();
        return true;
    }

    /**
     * 根据彩种获取商户设置信息
     *
     * @author left
     * @date 2017/10/10
     *
     * @param int $iLottery 彩种id
     *
     * @return array|bool
     */
    public function getProxyLotteryByLotteryId($iLottery = 0) {
        $iLottery = intval($iLottery);
        if ($iLottery <= 0) {
            return false;
        }
        $sSql = "SELECT * FROM `proxy_lottery_set` WHERE `lotteryid`={$iLottery} AND `lvtopid`>0";
        return $this->oDB->getAll($sSql);
    }

    /**
     * 获取商户最高奖金
     *
     * @param $iMerchantId 商户id
     *
     * @return mixed
     */
    public function getLotteryMaxPrize($iMerchantId) {
        $iMerchantId = intval($iMerchantId);
        $sSql = "SELECT MAX(p.`prize`) prize,m.`lotteryid` FROM `method` m 
                 LEFT JOIN `proxy_prizelevel` p ON m.`methodid`=p.`methodid` 
                 WHERE p.`lvtopid`={$iMerchantId} GROUP BY m.`lotteryid`";
        return $this->oDB->getAll($sSql);
    }
}