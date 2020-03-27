<?php 

/**
 * @desc 用户层级管理
 * @author rhovin
 * @date 2017-06-03
 *
 */
class model_userlayer extends basemodel{

	public function __construct(){
		
		parent::__construct();
	}
	/**
	 * @desc 获取用户层级列表
	 * @author rhovin 
	 * @date 2017-06-03
	 */
	public function getLayerList($lvtopid){
		$lvtopid = intval($lvtopid);
		//统计层下面的用户，存在layerid相同但lvtopid不相同的情况，所以需join  usertree指明lvtopid条件
		$sSql = "SELECT * ,(SELECT count(u.`userid`) FROM `users` u LEFT JOIN `usertree` ut ON ut.`userid` = u.`userid` WHERE u.`layerid`=ul.`layerid` and ut.`lvtopid`=ul.`lvtopid`) ucount  from `user_layer` ul WHERE `lvtopid` ='" . $lvtopid . "' ORDER BY `layerid` ASC;";
		$aArr = $this->oDB->getAll($sSql);
		if (0 == $this->oDB->ar()) {
            return -1;
        } else {
            return $aArr;
        }
	}
	/**
	 * @desc 仅读取层级列表的数据
	 * @author rhovin
	 * @date 2017-06-21
	 */
	public function getOnliyLayerList($lvtopid) {
		$sSql = " SELECT `layerid`, `name` from user_layer WHERE lvtopid='".$lvtopid."'";
		$aArr = $this->oDB->getAll($sSql);
		if (0 == $this->oDB->ar()) {
            return [];
        } else {
            return $aArr;
        }
	}
	/**
	 * @desc 添加用户层级
	 * @author rhovin
	 * @date 2017-06-06
	 */
	public function addLayer($aData){
		 $a = $this->oDB->insert('user_layer', $aData);
		 if($this->oDB->ar() < 1) {
		 	return false;
		 }
		 return true;
	}
	/**
	 * @desc 根据id查询当前层级
	 * @author rhovin
	 * @date 2017-06-06
	 */
	public function getLayerById($lvtopid ,$layerid){
		$sql = "SELECT `id`,`layerid`,`name`,`rechargetimes`,`loadmoney`,`loadmax`,`withdrawaltimes`,`totalwithdrawal`,`remark`,`lvtopid`,`bet_group_id` FROM `user_layer`
				WHERE lvtopid='".$lvtopid."'  AND layerid='".$layerid."' ";
		$aArr = $this->oDB->getOne($sql);
		if (0 == $this->oDB->ar()) {
            return false;
        } else {
            return $aArr;
        }
	}
	/**
	 * @desc 查询当前最大layerid
	 * @author rhovin
	 * @date 2017-06-06
	 */
	public function getMaxLayerId($lvtopid){
		$sql = "SELECT max(`layerid`) as layerid FROM `user_layer`
				WHERE lvtopid='".$lvtopid."'";
		$aArr = $this->oDB->getOne($sql);
		if (0 == $this->oDB->ar()) {
            return false;
        } else {
            return $aArr['layerid'];
        }
	}
	/**
	 * @desc 获取当前层级的用户列表
	 * @author rhovin 
	 * @date 2017-06-03
	 */
	public function getLayerLockUser($lvtopid,$layerid){
		$lvtopid = intval($lvtopid);
		$sql = " SELECT u.`userid`,u.`username`,u.`registertime`,u.`lasttime`,u.`rechargetimes`,u.`loadmoney`,u.`loadmax`,u.`withdrawaltimes`,u.`totalwithdrawal` FROM `users` u LEFT JOIN `usertree` ut on u.userid=ut.userid WHERE ut.lvtopid='" . $lvtopid . "'
				 AND u.layerid='" . $layerid . "' AND u.islocklayer=1";
		$aArr = $this->oDB->getAll($sql);
		if (0 == $this->oDB->ar()) {
            return false;
        } else {
            return $aArr;
        }
	}
	/**
	 * @desc 获取当前层级的用户列表
	 * @author rhovin 
	 * @date 2017-06-03
	 */
	public function getUserListByLayerId($lvtopid,$layerid){
		$lvtopid = intval($lvtopid);
		$sql = " SELECT u.`userid`,u.`username`,u.`registertime`,u.`lasttime`,u.`rechargetimes`,u.`loadmoney`,u.`loadmax`,u.`withdrawaltimes`,u.`totalwithdrawal` FROM `users` u LEFT JOIN `usertree` ut on u.userid=ut.userid WHERE ut.lvtopid='" . $lvtopid . "'
				 AND u.layerid='" . $layerid . "' AND u.islocklayer=0";
		$aArr = $this->oDB->getAll($sql);
		if (0 == $this->oDB->ar()) {
            return false;
        } else {
            return $aArr;
        }
	}
	/**
	 * @desc 分页获取某层级用户列表
	 * @author rhovin
	 * @date 2017-06-21
	 */
	public function getLayerUserPageList($iCurrPage,$iPageRecords,$sWhere,$sOrderBy='') {
		$sTableName = " `usertree` AS ut  "
                . " LEFT JOIN `users` AS u ON u.`userid` = ut.`userid` "
               /* . " LEFT JOIN `user_layer` AS ul ON u.`layerid`=ul.`layerid` "*/
                . " LEFT JOIN `userfund` AS uf ON ut.`userid`=uf.`userid` ";
        $sFields = "DISTINCT(u.`userid`),ut.`parentid`,u.`layerid`,u.`islocklayer`,u.`username`,u.`registertime`,if(u.`lasttime`<='1970-01-01 00:00:00',u.`registertime`,u.`lasttime`) as `lasttime`,u.`rechargetimes`,u.`loadmoney`,u.`loadmax`,u.`withdrawaltimes`,u.`totalwithdrawal`,uf.`channelbalance`";
		$sCondition = '1 AND ut.`isdeleted`=0 ';
		$sCondition .= $sWhere;
		$result = $this->oDB->getPageResult($sTableName, $sFields, $sCondition, $iPageRecords, $iCurrPage, $sOrderBy, '', '');
		return $result;
	}
	/**
	 * @desc 更新一层会员到另一层
	 * @author rhovin
	 * @date 2017-06-07
	 */
	public function upUserlayer($aUserid,$where) {
		$sUsersId = implode(',', $aUserid);
		$sSql = "update `users` set `layerid`='".$where['layerid']."' WHERE `userid` in ({$sUsersId}) AND `loadmoney`>='".$where['loadmoney']."' AND `loadmax` >= '".$where['loadmax']."' AND `totalwithdrawal`>='".$where['totalwithdrawal']."' AND `withdrawaltimes`>='".$where['withdrawaltimes']."' AND `rechargetimes`>='".$where['rechargetimes']."' AND `islocklayer`=0  ";
		$this->oDB->query($sSql);
        if ($this->oDB->errno() > 0) { // 修改失败
            return false ;
        }else{
        	return true ;
        }
	}
	/**
	 * @desc 层回归
	 * @author rhovin
	 * @date 2017-06-07
	 */
	public function upLayerByUserid($aUserid ,$lvtopid) {
		$sUsersId = implode(',', $aUserid);
		$sSql = "update `users` set `layerid`=0 WHERE `userid` in ({$sUsersId}) AND `islocklayer`=0 AND lvtopid='".$lvtopid."'";
		$this->oDB->query($sSql);
        if ($this->oDB->errno() > 0) { // 修改失败
            return false ;
        }else{
        	return true ;
        }
	}
	/**
	 * @desc 设置层级支付ID
	 * @author rhovin
	 * @date 2017-06-07
	 */
	public function upLayerPayid($layerid ,$iPayId,$lvtopid) {
		$sSql = "update `user_layer` set `p_paysetid`='".$iPayId."' WHERE lvtopid='".$lvtopid."' AND layerid='".$layerid."' ";
		$this->oDB->query($sSql);
        if ($this->oDB->errno() > 0) { // 修改失败
            return false ;
        }else{
        	return true ;
        }
	}
	/**
	 * @desc 设置层级投注限额ID
	 * @author rhovin
	 * @date 2017-12-12
	 */
	public function upLayerBetGroupId($layerid ,$betGroupid,$lvtopid) {
		$sSql = "update `user_layer` set `bet_group_id`='".$betGroupid."' WHERE lvtopid='".$lvtopid."' AND layerid='".$layerid."' ";
		$this->oDB->query($sSql);
        if ($this->oDB->errno() > 0) { // 修改失败
            return false ;
        }else{
        	return true ;
        }
	}
	/**
	 * @desc 更新某个用户的层级
	 * @author rhovin
	 * @date 2017-06-21
	 */
	public function upUserlayerByUserid($iUserid,$iLayerid) {
		$sSql = "update `users` set `layerid`='".$iLayerid."' WHERE `userid` in ({$iUserid}) AND `islocklayer`=0 ";
		$this->oDB->query($sSql);
        if ($this->oDB->errno() > 0) { // 修改失败
            return false ;
        }else{
        	return true ;
        }
	}
	/**
	 * @desc 删除层级回归所有用户 包括锁定的
	 * @author rhovin
	 * @date 2017-06-07
	 */
	public function upAllUserlayerByUserid($aUserid) {
		$sUsersId = implode(',', $aUserid);
		$sSql = "update `users` set `layerid`=0 WHERE `userid` in ({$sUsersId}) ";
		$this->oDB->query($sSql);
        if ($this->oDB->errno() > 0) { // 修改失败
            return false ;
        }else{
        	return true ;
        }
	}
	/**
	 * @desc 更新层级
	 * @author rhovin
	 * @date 2017-06-21
	 */
	public function updateLayer($aParams,$layerid,$lvtopid){
		$sTempWhereSql = " `layerid` = '" . intval($layerid) . "' AND lvtopid='".$lvtopid."'";
        return $this->oDB->update('user_layer', $aParams, $sTempWhereSql);
	}
	/**
	 * @desc 删除层级
	 * @author rhovin
	 * @date 2017-06-21
	 */
	public function delLayer($lvtopid,$layerid){
		return $this->oDB->query("DELETE FROM `user_layer` WHERE `layerid`='" . $layerid . "' AND `lvtopid`='" . $lvtopid . "'");
	}

    /**
     * 根据层级获取支付设定层级
     * @author pierce
     * @date 2017-07-10
     * @param $layerid
     * @param $lvtopid
     * @return array
     */
	public function getPaySetIdByLayerId($layerid,$lvtopid){
        $sql = "SELECT `p_paysetid` FROM `user_layer` 
				WHERE `lvtopid`='".$lvtopid."' AND `layerid` = '".$layerid."'";
        $aArr = $this->oDB->getOne($sql);
        if (0 == $this->oDB->ar()) {
            return $aArr;
        } else {
            return $aArr;
        }
    }
    /**
     * 根据层级获取支付设定层级
     * @author Rhovin
     * @date 2017-12-12
     * @return array
     */
	public function getLayerByBetGroupId($betGroupid,$lvtopid){
        $sql = "SELECT * FROM `user_layer` 
				WHERE `lvtopid`='".$lvtopid."' AND `bet_group_id` = '".$betGroupid."'";
        return $this->oDB->getOne($sql);
    }
}
