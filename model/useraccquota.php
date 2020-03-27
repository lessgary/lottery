<?php


/**
 * desc 用户配额模型
 * @author rhovin 2017-07-14
 */
class model_useraccquota extends basemodel {

	/**
     * @desc 设置用户默认配额
     * @author rhovin 2017-07-14
     */
    public function setDefaultQuota($lvtopid,$iUserId,$iProxyLevel) {
        $iProxyLevel = intval($iProxyLevel);
        //读取默认配额
        $sSql = "SELECT * FROM proxy_accquota_set WHERE lvtopid='".$lvtopid."' AND `proxy_level`='".$iProxyLevel."'";
        $aDefault = $this->oDB->getAll($sSql);
        if(!empty($aDefault)) {
            $aData = [] ;
            foreach ($aDefault as $key => $value) {
                $aData[$key] = [
                    "lvtopid"=>$lvtopid,
                    "userid"=>$iUserId,
                    "point"=>$value['point'],
                    "total_quota"=>$value['def_quota'],
                    "left_quota"=>$value['def_quota'],
                    "updatetime"=>date("Y-m-d H:i:s")
                ];
            }
            //写入默认配额
            if(!empty($aData)) {
                return $this->insertQuota($aData);
            }
        }
        return true;
    }
    /**
     * @desc 批量修改用户配额
     * @author rhovin 2017-07-14
     */
    public function insertQuota($aData) {
        if (!empty($aData)) {
            $sSql = " REPLACE INTO user_accquota (`lvtopid`,`userid`,`point`,`total_quota`,`left_quota`,`updatetime`) VALUES ";
            foreach ($aData as $key => $value) {
                $sSql .= " ('".$value['lvtopid']."','".$value['userid']."','".$value['point']."','".$value['total_quota']."','".$value['left_quota']."','".$value['updatetime']."'),";
            }
            $sSql = substr($sSql, 1,strlen($sSql)-2);
            $this->oDB->query($sSql);
            if ($this->oDB->errno() > 0) { // 修改失败
                return FALSE;
            }else {
                return TRUE ;
            }
        }
    }
    /** 
	 * @desc 获取单个用户配额列表
	 * @author rhovin
	 * @date 2017-07-14
	 */
        public function getUserQuota($iUserId,$point ,$lvtopid) {
		$sSql = "SELECT * FROM `user_accquota` WHERE `userid`='".$iUserId."' AND `point`='".$point."' AND `lvtopid`='".$lvtopid."' ";
		return $this->oDB->getOne($sSql);
	}

    /**
     * 获取单个用户配额列表
     *
     * Todo：一个userid就可以了
     *
     * @author left
     * @date 2017/07/18
     *
     * @param   int   $iUserId 用户id
     * @param   int   $lvtopid 商户id
     *
     * @return array  单个用户所有返点对应的配额
     */
	public function getUserQuotaes($iUserId, $lvtopid) {
        $sSql = "SELECT * FROM `user_accquota` WHERE `userid`='".$iUserId."' AND `lvtopid`='".$lvtopid."' AND `left_quota` >= 0 ";
        return $this->oDB->getAll($sSql);
    }

    /**
     * 上级给下级分配配额，走配额账变表
     *
     * $aData参数形式：
     *      [
     *          [0.078] => 100
     *          [0.077] => 100
     *          [0.077] => 100
     *      ]
     *
     * @param int   $iUserId     下级id
     * @param int   $iParentId   上级id
     * @param int   $iLvTopId    商户id
     * @param array $aData       需要的账变数据
     *
     * @return bool|mix
     */
    public function setAccountOrder($iUserId, $iParentId, $iLvTopId, $aData=[]) {
        if (empty($aData)) {
            return false;
        }

        // 边拼接数据，边检查上级配额是否足够
        $oAccountorder = new model_accountorders();
        $aParentData = [];
        $aChildData = [];
        $i = 0;

        foreach ($aData as $k => $v) {
            if (empty($v)) {
                continue;
            }

            // 如果是总代，不走配额，兼容商户后台
            if ($iParentId != $iLvTopId) {
                $aParentData[$i]['userid']     = $iParentId;
                $aParentData[$i]['amount']     = $v;
                $aParentData[$i]['type']       = ACCOUNTORDER_TYPE_FFPE;
                $aParentData[$i]['relativeid'] = $iParentId;
                $aParentData[$i]['point']      = round($k * 100, 1);
                $aParentData[$i]['iLvtopId']   = $iLvTopId;
                $aParentData[$i]['admin']      = true;
            }

            $aChildData[$i]['userid']      = $iUserId;
            $aChildData[$i]['amount']      = $v;
            $aChildData[$i]['type']        = ACCOUNTORDER_TYPE_JSPE;
            $aChildData[$i]['relativeid']  = $iParentId;
            $aChildData[$i]['point']       = round($k * 100, 1);
            $aChildData[$i]['iLvtopId']    = $iLvTopId;
            $aChildData[$i]['admin']      = true;
            $i++;
        }
        //开启事务
        $this->oDB->doTransaction();
        foreach ($aChildData as $k => $v) {
            if ($iParentId != $iLvTopId) {
                $mParentRestlt = $oAccountorder->addOrders($aParentData[$k]);
                if ($mParentRestlt !== TRUE) {
                    $this->oDB->doRollback();
                    return $mParentRestlt;
                }
            }
            $mChildResult = $oAccountorder->addOrders($v);
            if ($mChildResult !== TRUE) {
                $this->oDB->doRollback();
                return $mChildResult;
            }
        }
        $this->oDB->commit();
        return true;
    }


    /**
     * 获取用户可以显示开户的返点
     *
     * @author left
     * @date 2017/7/21
     *
     * @param   int       $iUserId          用户id
     * @param   int       $iLvtopid         商户id
     * @param   int       $fSelfPoint       自身返点
     *
     * @return array    失败返回空数组,成功输出降序的返点
     */
    public function getShowPoint($iUserId, $iLvtopid, $fSelfPoint) {

        $aReturn = [];
        $iUserId = intval($iUserId);
        $iLvtopid = intval($iLvtopid);
        $fSelfPoint = floatval($fSelfPoint);

        // 获取商户推广返点
        //同时返点也走默认配置
        /* @var $oProxyconfig model_proxyconfig */
        $oProxyconfig = A::singleton('model_proxyconfig');
        $fLastPoint = $oProxyconfig->getConfigs($iLvtopid, 'registered_promotion_point');
        $fPromotionPoint = floatval($fLastPoint);
        // 获取最小点差
        $fMinPoint = floatval( getConfigValue("runset_mincommissiongap", 0.004));
        $sSame = $oProxyconfig->getConfigs($iLvtopid, 'registered_samegrade');
        if ($sSame == 'yes') {
            $fMinPoint = 0;
        }
        // 下级允许开的最高返点
        $fChildMaxPoint = $fSelfPoint - $fMinPoint;

        // 获取用户本身返点配额
        $aUserQuotaes = $this->getUserQuotaes($iUserId, $iLvtopid);
        // 1、推广返点 >= 下级允许开的返点
        if ($fPromotionPoint > $fChildMaxPoint) {
            $fChildMaxPoint = round($fChildMaxPoint * 100, 1);
            for ($i = $fChildMaxPoint; $i >= 0; $i -= 0.1) {
                $aReturn[] = number_format(round($i, 1), 1);
            }
        } else {
            //2、推广返点 < 下级允许开的返点
            foreach ($aUserQuotaes as $k=>$v) {
                if ($v['point'] <= $fChildMaxPoint && $v['left_quota'] > 0) {
                    $aReturn[] = number_format(round($v['point'] * 100, 1), 1);
                }
            }
            $fPromotionPoint = round($fPromotionPoint * 100, 1);
            for ($i = $fPromotionPoint-0.1; $i >= 0; $i -= 0.1) {
                $aReturn[] = number_format(round($i, 1), 1);
            }
            $aReturn = array_unique($aReturn);
            rsort($aReturn);
        }
        return $aReturn;
    }

    /**
     * 添加配额账变到配额账变记录表
     * @author  james liang
     * @date    2017-9-4
     *
     * @param  array  $k => $v   $k对应表的字段  $v 带转义处理
     * @return boolean  写入结果
     */
    public function insertRecord($aData = array()) {
        if (!is_array($aData)){
            return false;
        }
        $aStr = [];
        $aK   = [];
        foreach ($aData as $k => $v){
            if ($k != 'id'){
                $aK[] = $k;
            }
            $aStr[] = daddslashes($v);
        }
        $sK = implode("`, `", $aK);
        $str = implode("', '", $aStr);
        $sSql = "insert into `accountorder` (`{$sK}`) values('{$str}') ";
        $this->oDB->query($sSql);
        if ($this->oDB->errno() > 0) {
            return FALSE;
        }else {
            return TRUE ;
        }
    }

//    /**
//     * 获取代理用户code
//     * @param $iLvtopid
//     * @param $iUserId
//     * @return array
//     */
//    public function getUserCode($iLvtopid,$iUserId){
//        $sSql = "SELECT * FROM `user_register_links` WHERE `lvtopid` = '".$iLvtopid."' AND `userid` = '".$iUserId."'";
//        $aReturn = $this->oDB->getAll($sSql);
//        if (!empty($aReturn)) {
//            foreach ($aReturn as &$v) {
//                $v['point'] = ($v['user_point']*100)."%";
//            }
//        }
//        return $aReturn;
//    }
}
