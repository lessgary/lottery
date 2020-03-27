<?php

/**
 * @desc 功能: 控制器 - 即时稽核
 * 
 * @author    rhovin
 * @package   proxyweb
 * @date 2017-07-05
 */
class controller_userbetscheck extends pcontroller {
	/**
	 * @desc 即时稽核存款列表
	 * @author rhovin
	 * @date 2017-07-05
	 */
	public function actionList() {
		if($this->getIsPost()) {
			$aGetData = $this->post(array(
				"username"=>parent::VAR_TYPE_STR,
				"status"=>parent::VAR_TYPE_INT,
				"withdrawid"=>parent::VAR_TYPE_INT,
				"withdrawamount"=>parent::VAR_TYPE_FLOAT,
			));
			$oUser = new model_puser();
			$aUserId = $oUser->getIdByUsername($this->lvtopid,$aGetData['username']);
			$userid = isset($aUserId['userid']) ? $aUserId['userid'] : 0;
            $oWithdraw = new model_withdraw();
            $aRealbet = $oWithdraw->getRealBets($userid,$this->lvtopid);
            $totalBets = isset($aRealbet['realbets']) ? $aRealbet['realbets'] : 0;
			$oUserBets= new model_userbetscheck();
			$sAndWhere = "";
			if($aGetData['status'] == 0) {
				$sAndWhere .= " AND isclear=0";//未结算
			}
			if($aGetData['status'] > 0 && $aGetData['withdrawid'] > 0) {
				$sAndWhere .= " AND isclear>=1";//1预备/锁定, 2已出款, 3取消出款
				$sAndWhere .= " AND withdraw_id='".$aGetData['withdrawid']."'";//未结算
			}
			$oPaySet = new model_payset();
			$xzFee = $oPaySet->getBetsChargeRate($userid , $this->lvtopid);
			$aUserBets = $oUserBets->findAll($userid ,$aGetData['username'], $totalBets, $this->lvtopid,$sAndWhere);
			if($aGetData['withdrawamount'] > 0) {
				//每笔出款手续费
				$xzMoney = !empty($aUserBets) ? $aUserBets[0]['total_needbets_reduce'] :0.000;
	            $kcFee = $oPaySet->getFeeById($this->lvtopid,$userid,$aGetData['withdrawamount'],$oUserBets->betsStatus);
			} else { //手续费比例或者每笔扣多少金额
	            $aPaySetInfo = $oPaySet->findOnePaySet($userid , $this->lvtopid);
	            if(!empty($aPaySetInfo)) {
		            if($aPaySetInfo['withdraw_chargetype'] == 0) {
		           		$sxfee = $aPaySetInfo['withdraw_charge']."%";
			        }else{
			            $sxfee = $aPaySetInfo['withdraw_charge']."/每笔";
			        }
	            }
			}
			$kcFee = isset($kcFee) ? $kcFee :'0.00';
			$sxfee = isset($sxfee) ? $sxfee :'0.00';
			$xzFee = $xzFee !='NaN' ? $xzFee*100 :'0.00';
			//优化金额，保留后两位
			foreach ($aUserBets as $k => &$v){
			    $v['amount'] = numberFormat2($v['amount']);
			    $v['ext_amount'] = numberFormat2($v['ext_amount']);
			    $v['betsvalue'] = numberFormat2($v['betsvalue']);
			    $v['ext_bets'] = numberFormat2($v['ext_bets']);
			    $v['need_bets'] = numberFormat2($v['need_bets']);
			    $v['reduce_bets'] = numberFormat2($v['reduce_bets']);
            }
			$this->outPutJQgruidJson($aUserBets,count($aUserBets),1,100,["sxfee"=>$sxfee,"xzFee"=>$xzFee,"kcfee"=>$kcFee]);
		}
		$GLOBALS['oView']->display('userbetscheck_list.html');
	}
	/**
	 * desc 清空打码量
	 * @author rhovin
	 * @date 2017-07-06
	 */
	public function actionFlushBets() {
		 $aGetData = $this->post(array(
                "id" => parent::VAR_TYPE_INT,
            ));
		//实例当前模型
		$oUserBet= new model_userbetscheck();
		$oUserBet->lvtopid = $this->lvtopid;
		$oUserBet = $oUserBet->findModel($aGetData['id']);
		if($oUserBet->lvtopid != $this->lvtopid)  $this->ajaxMsg(0,"非法操作");
		$oUserBet->ext_bets = 0;
		$oUserBet->need_bets = 0;
		$bResult = $oUserBet->flushBets($aGetData['id']);
		if($bResult){
			$this->ajaxMsg(1,"操作成功");
		} else {
			$this->ajaxMsg(0,"操作失败");
		}
	}
}
