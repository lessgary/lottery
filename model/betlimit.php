<?php

/**
 * desc 投注限额
 * @Rhovin 2017-12-07
 */
class model_betlimit extends basemodel {

    public $_errMsg;
    /**
     * desc 获取投注限额组
     * @date 2017-12-07
     */
    public function getBetGroup($iLvtopId) {
         $sSql = "SELECT * FROM proxy_betlimit_group WHERE lvtopid='".$iLvtopId."'" ;
         return  $this->oDB->getAll($sSql);

    }
    /**
     * desc 添加投注限额分组
     * @date 2017-12-11
     */
    public function addBetGroup($aData) {
        $iInserId = $this->oDB->insert('proxy_betlimit_group', $aData);
         if($this->oDB->ar() < 1) {
             return false;
         }
         return $iInserId;
    }
    /**
     * desc 修改限额名称
     * @date 2017-12-12
     */ 
    public function updateBetName($iLvtopId, $id, $aData) {
    	return $this->oDB->update('proxy_betlimit_group', $aData, "`id` = '" . intval($id) . "' AND `lvtopid`= '".$iLvtopId."'");
    }
    /**
     * desc 删除限额分组
     * @date 2017-12-12
     */
    public function delBetGroup($id, $iLvtopId) {
        $this->oDB->doTransaction();
        try {
            $oLayerModel = new model_userlayer();
            $result = $oLayerModel->getLayerByBetGroupId($id, $iLvtopId);
            if(empty($result)) {
            	 $delRes = $this->oDB->delete('proxy_betlimit_group', "`id` = '" . intval($id) . "' AND `lvtopid`= '".$iLvtopId."'");
            	 if($delRes) {
	            	 $delRes = $this->oDB->delete('proxy_betlimit', "`pid` = '" . intval($id) . "' AND `lvtopid`= '".$iLvtopId."'");
	            	 if($delRes) {
 						   $this->oDB->doCommit();	
	            	 	   return TRUE;
	            	 } else {
	            	 	throw new Exception("删除失败");
	            	 }
            	 } else {
            	 	throw new Exception("删除失败");
            	 }
            } else {
            	throw new Exception("该限额正被层级使用,不能删除");
            }
        } catch (Exception $e) {
             $this->_errMsg = $e->getMessage();
            $this->oDB->doRollback();
            return FALSE;
        }
        
    }
    /**
     * desc 获取限额列表
     * @date 2017-12-11
     */
    public function getBetLimit($iLvtopId , $pid ) {
        $sSql = "SELECT * FROM proxy_betlimit WHERE lvtopid='".$iLvtopId."' AND pid='".$pid."'" ;
        return $this->oDB->getAll($sSql);
    }
    /**
     * desc 添加投注限额
     * @date 2017-12-11
     */
    public function addBetLimit($iLvtopId, $aLimitBonus , $pid ='') {
         $this->oDB->doTransaction();
         try {
             if($pid == '' ) {
                 $sSql = "SELECT count(*) AS num FROM proxy_betlimit_group WHERE lvtopid='".$iLvtopId."'";
                 $result = $this->oDB->getOne($sSql);
                 $num = !empty($result['num']) ? $result['num']+1 : 0;
                 $aBetGroup = ['name'=>'新增限额'.intval($num),'lvtopid'=>$iLvtopId];
                 $pid = $this->addBetGroup($aBetGroup);
                 if(!$pid) {
                     throw new Exception("操作失败");
                 }
            }
            if(isset($pid) && $pid != false) {
                 $aData = [];
                 $sSql = " REPLACE INTO proxy_betlimit (`lvtopid`,`pid`,`method_id`,`limit_bonus`) VALUES ";
                foreach ($aLimitBonus as $key => $value) {
                    $sSql .= " ('".$iLvtopId."','".$pid."','".intval($key)."','".$value."'),";
                }
               $sSql = substr($sSql, 1,strlen($sSql)-2);
               $this->oDB->query($sSql);
                if ($this->oDB->errno() > 0) { // 修改失败
                     throw new Exception("操作失败");
                }else {
                     $this->oDB->doCommit();
                     return TRUE;
                }
            } else {
                throw new Exception("操作失败");
            } 
           
         } catch (Exception $e) {
            $this->_errMsg = $e->getMessage();
            $this->oDB->doRollback();
            return FALSE;
         }
    }
    
}