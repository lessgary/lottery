<?php

/**
 * desc 投注限额
 * @author rhovin 2017-12-06
 */
class controller_betlimit extends pcontroller { 

    /**
     * desc 限额列表
     * @date 2017-12-06
     */
    public function actionList() {
        $oBetModel = new model_betlimit();
        if($this->getIsPost()) {
            $result = $oBetModel->getBetGroup($this->lvtopid);
           
            $this->outPutJQgruidJson($result,count($result) , 1, 100);
        }
         $GLOBALS['oView']->display("betlimit_list.html");
        
    }
    /**
     * desc 编辑限额名称
     * @date 2017-12-12
     */
    public function actionEditBetGroup() {
    	 $aGetData = $this->post(array(
                "id" => parent::VAR_TYPE_INT, //彩种ID
                "name" => parent::VAR_TYPE_STR,
        ));
    	  $oBetModel = new model_betlimit();
    	  $result = $oBetModel->updateBetName($this->lvtopid, $aGetData['id'],['name'=>$aGetData['name']]);
    	  if($result) {
	    	   $this->ajaxMsg("1","操作成功");	
    	  } else {
	    	   $this->ajaxMsg("0","操作失败");	
    	  }
    }
    /**
     * desc 添加投注限额
     * @date 2017-12-06
     */
    public function actionAdd () {
        $aGetData = $this->get(array(
                "id" => parent::VAR_TYPE_INT, //彩种ID
                "pid" => parent::VAR_TYPE_INT,
        ));
        $oPlottery = new model_plottery();
        $aLotteryList = $oPlottery->getLotteryMethodList($this->lvtopid);
        $iLvtopLottery = $aGetData['id'] != 0 ? $aGetData['id'] : $aLotteryList[0]['id'];
        // 获取商家设置彩种信息
        /* @var $oPlottery model_plottery */
        $oPlottery = A::singleton("model_plottery");
        $aProxySet = $oPlottery->getLvTopLotteries($this->lvtopid, $iLvtopLottery);
        if(empty($aProxySet)) {
            sysMessage("商户不存在该彩种", 1);
        }
        $iLotteryId = $aProxySet['lotteryid'];
        $iIsOfficial = $aProxySet['is_official'];

        $oLottery = A::singleton("model_lottery");
        $aLottery = $oLottery->lotteryCache($iLotteryId );
        $iFrequencyType = intval($aLottery['frequencytype']); //高频或低频类型
        if ($iFrequencyType == 1) {//低频（3D、P3）奖金限额
            $fSysMaxPrize = intval(getConfigValue('runset_dplimitbonus', 100000)); //系统同单投注最高奖金限制
        } else {
            $fSysMaxPrize = intval(getConfigValue('runset_limitbonus', 100000)); //系统同单投注最高奖金限制
        }
        $oBetModel = new model_betlimit();
        if($aGetData['pid'] != 0 ) {  //如果不为0 则为编辑
        	$aBetLimit = $oBetModel->getBetLimit($this->lvtopid , $aGetData['pid']);
        	if(!empty($aBetLimit)) {
        		$aLimitBonus = [];
	        	foreach ($aBetLimit as $key => $value) {
	        		$aLimitBonus[$value['method_id']] = $value['limit_bonus'];
	        	}
        	}
        }
        /* @var $oMethod model_method */
        $oMethod = A::singleton("model_method");
        $sFiled = "M.`methodid`,M.`lotteryid`,M.`methodname`,M.`level`,M.`type`,"
                . "M.`nocount`,M.`totalmoney`,M.`is_official`,L.`cnname`,MC.`crowdname`,MC.`crowdid`";
        $sCondition = "M.`lotteryid`='" . $iLotteryId . "' and M.`pid`>'0' and M.`is_official`='".$iIsOfficial."'";
        $aMethod = $oMethod->methodGetListByCrowd($sFiled, $sCondition);
        
        // 过滤官方/信用玩法
        foreach($aMethod as $k=>$method) {
            foreach ($method['method'] as $key => $value) {
                unset($aMethod[$k]['method'][$key]['nocount']);

                $aMethod[$k]['method'][$key]['limitbonus'] = isset($aLimitBonus[$value['methodid']]) ? $aLimitBonus[$value['methodid']] : $fSysMaxPrize;
            }
            $aMethod[$k]['count'] = count($method['method']);
            if (empty($aMethod[$k]['method'])) {
                unset($aMethod[$k]);
            }
        }
        $GLOBALS['oView']->assign("alottery", $aLottery);
        $GLOBALS['oView']->assign("pid", $aGetData['pid']);
        $GLOBALS['oView']->assign("lotterylist", $aLotteryList);
        $GLOBALS['oView']->assign("lotteryid", $iLotteryId);
        $GLOBALS['oView']->assign("iLvtopLottery", $iLvtopLottery);
        $GLOBALS['oView']->assign("amethod", $aMethod);
        $GLOBALS['oView']->display("betlimit_add.html");
    }

    /**
     * desc 限额修改
     * @date 2017-12-06
     */
    public function actionSave() {
        if(isset($_POST['limitbonus']) && is_array($_POST['limitbonus'])) {
            $aLimitBonus = $_POST['limitbonus'];
            $pid = isset($_POST['pid']) && $_POST['pid'] > 0 ? intval($_POST['pid']) : '';
            $oBetModel = new model_betlimit();
            $result = $oBetModel->addBetLimit($this->lvtopid , $aLimitBonus,$pid);
            if($result) {
                $this->ajaxMsg("1","操作成功");
            } else {
                $this->ajaxMsg("0",$oBetModel->_erroMsg);
            }
        } else {
                $this->ajaxMsg("0","操作失败");
        }
    }
    /**
     * desc 删除限额
     * @date 2017-12-06
     */
    public function actionDelete() {
    	$aGetData = $this->get(array(
                "id" => parent::VAR_TYPE_INT,
        ));
    	$oBetModel = new model_betlimit();
    	$result = $oBetModel->delBetGroup($aGetData['id'] ,$this->lvtopid);
    	if($result) {
    		sysMessage("操作成功");
    	} else {
    	    sysMessage($oBetModel->_errMsg, 1);
    	}
    }
}