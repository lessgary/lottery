<?php

/**
 * desc 奖期风控
 * @author rhovin 2017-12-06
 */
class controller_lotteryrisk extends pcontroller { 

    /**
     * desc 奖期风控列表
     * @date 2017-12-06
     */
    public function actionList() {
        $oRiskModel = new model_lotteryrisk();
        //$a = $oRiskModel->createLotteryRisk(17,'1712281058','2017-12-28');
        if($this->getIsPost()) {
             $aGetData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
            ));
            $aData = $oRiskModel->getList($this->lvtopid,$aGetData['page'],$aGetData['rows']);
            $this->outPutJQgruidJson($aData['results'], $aData['affects'], $aGetData['page'], $aGetData['rows']);
        } else {
            $GLOBALS['oView']->display("lotteryrisk_list.html");
        }
    }
    /**
     * desc 通过审核
     * @date 2017-12-12
     */
    public function actionUpdateStatus () {
    	 $aGetData = $this->post(array(
                "id" => parent::VAR_TYPE_INT, 
                "status" => parent::VAR_TYPE_INT,
        ));
    	  $oRiskModel = new model_lotteryrisk();
    	  $result = $oRiskModel->updateSatus($this->lvtopid, $aGetData['id'],['status'=>$aGetData['status']]);
    	  if($result) {
	    	   $this->ajaxMsg("1","操作成功");	
    	  } else {
	    	   $this->ajaxMsg("0","操作失败");	
    	  }
    }
    /**
     * desc 中奖注单详情
     * @date 2017-12-27
     */
    public function actionBonuInfo() {
        $aGetData = $this->get(array(
                "lotteryid" => parent::VAR_TYPE_INT, 
                "issue" => parent::VAR_TYPE_STR,
        ));
        $oRiskModel = new model_lotteryrisk();
        if($this->getIsPost()) {
            $aData = $oRiskModel->getBonuInfo($this->lvtopid, $aGetData['lotteryid'] , $aGetData['issue']);
            foreach ($aData as $key => &$value) {
                $value['projectid'] =  myHash($value['projectid'], "ENCODE", 'P');
            }
            $this->outPutJQgruidJson($aData, count($aData), 1, 100);          
        }else{
            $GLOBALS['oView']->display("lotteryrisk_bonuinfo.html");
        }
    }
    
}