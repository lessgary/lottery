<?php

class controller_operation extends pcontroller
{
    /**
     * 运营总报表
     *
     * @author ken 2017-07-10
     *
     */
    public function actionProfit ()
    {
        $oProperation = new model_poperation();
        $oUsers = new model_puser();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "username" => parent::VAR_TYPE_STR,
                "sTime" => parent::VAR_TYPE_DATETIME,
                "eTime" => parent::VAR_TYPE_DATETIME,
            ));
            $aGetData['sTime'] = !empty($aGetData['sTime']) ? $aGetData['sTime'] : date("Y-m-d " . $this->sReportTime, time());
            $aGetData['eTime'] = !empty($aGetData['eTime']) ? $aGetData['eTime'] : date("Y-m-d " . $this->sReportTime, time() + 86400);
            if (isset($aGetData["username"]) && $aGetData["username"] != "") {
                $aUserInfo = $oUsers->getUser($aGetData["username"],$this->lvtopid);
                if (empty($aUserInfo)){
                    $this->outPutJQgruidJson([],0);
                    return false;
                }else{
                    $iUserId = $aUserInfo['userid'];
                }
            }else{
                $iUserId = $this->lvtopid;
            }
            $aResult = $oProperation->getOperationDataList($aGetData,$this->lvtopid,$iUserId);
            //格式化金额
            foreach ($aResult as $k => &$v){
                $v['payment'] = numberFormat2($v['payment']);
                $v['withdraw'] = numberFormat2($v['withdraw']);
                $v['activity'] = numberFormat2($v['activity']);
                $v['deduction'] = numberFormat2($v['deduction'],4);
                $v['bets'] = numberFormat2($v['bets'],4);
                $v['availablebalance'] = numberFormat2($v['availablebalance']);
                $v['bonus'] = numberFormat2($v['bonus'],4);
                $v['points'] = numberFormat2($v['points'],4);
            }
            $this->outPutJQgruidJson($aResult,count($aResult));
        }
        $aTotal = $oProperation->getTotalList($this->lvtopid);
        //格式化金额
        $aTotal['total_payment'] = numberFormat2($aTotal['total_payment']);
        $aTotal['total_withdraw'] = numberFormat2($aTotal['total_withdraw']);
        $aTotal['total_activity'] = numberFormat2($aTotal['total_activity']);
        $aTotal['total_availablebalance'] = numberFormat2($aTotal['total_availablebalance']);

        $sTotal = json_encode([$aTotal]);
        $GLOBALS['oView']->assign('startDate', date('Y-m-d '));
        $GLOBALS['oView']->assign('endDate', date('Y-m-d ', strtotime('+1 days')));
        $GLOBALS["oView"]->assign("today", $this->sToday);
        $GLOBALS["oView"]->assign("aTotal", $sTotal);
        $GLOBALS['oView']->display("operation_list.html");
    }

    
    /**
     * 运营总报表导出
     */
    function actionExportReport(){
        $_GET = json_decode($_GET['getData'],TRUE);
        $oUserModel = new model_poperation();
        $aGetData = $this->get(array(
            "page" => parent::VAR_TYPE_INT,
            "rows" => parent::VAR_TYPE_INT,
            "username" => parent::VAR_TYPE_STR,
            "sidx" => parent::VAR_TYPE_STR,
            "sord" => parent::VAR_TYPE_STR,
            "sTime" => parent::VAR_TYPE_DATETIME,
            "eTime" => parent::VAR_TYPE_DATETIME,
            "searchname" => parent::VAR_TYPE_STR,
        ));
       
    
        if (!empty($aGetData['searchname'])) {
            $aGetData['username'] = $aGetData['searchname'];
        }
        $aGetData['lvtopid']=$this->lvtopid;
        $aExtRst = $oUserModel->getOperationDataList('1',$aGetData,$aGetData['page'],$aGetData['rows'],'ext');
        $expTitle  = "Operation_Report";
        $expCellName  = [
            ['username','用户名'],
            ['groupname','用户组'],
            ['availablebalance','账号余额'],
            ['count_realbets','有效投注'],
            ['count_activity','活动优惠'],
            ['count_bonus','总奖金'],
            ['count_deduction','系统扣减'],
            ['count_payment','真实充值'],
            ['count_withdraw','真实提现'],
            ['count_points','总返点'],
            ['count_bets_user','有效会员'],
            ['count_new_user','新增用户'],
            ['total_win','总盈利']
        ];
        $aTableData = [];
        foreach ($aExtRst as $key => $value) {
            $aTableData[$key]['username'] = $value['username'];
            $aTableData[$key]['groupname'] = $value['groupname'];
            $aTableData[$key]['availablebalance'] = $value['availablebalance'];
            $aTableData[$key]['count_realbets'] = $value['count_realbets'];
            $aTableData[$key]['count_activity'] = $value['count_activity'];
            $aTableData[$key]['count_bonus'] = $value['count_bonus'];
            $aTableData[$key]['count_deduction'] = $value['count_deduction'];
            $aTableData[$key]['count_payment'] = $value['count_payment'];
            $aTableData[$key]['count_withdraw'] = $value['count_withdraw'];
            $aTableData[$key]['count_points'] = $value['count_points'];
            $aTableData[$key]['count_bets_user'] = $value['count_bets_user'];
            $aTableData[$key]['count_new_user'] = $value['count_new_user'];
            $aTableData[$key]['total_win'] = $value['total_win'];
        }
        ExportExcel($expTitle,$expCellName,$aTableData);
    }
    
    /**
     * 整理数据
     * @param $aGetData
     * @return array
     */
    private function _arrage($aGetData)
    {
        $aData = [];
        $aData['lvtopid'] = $this->lvtopid;
        $aData['username'] = isset($aGetData['username']) ? $aGetData['username'] : '';
        $aData['searchname'] = isset($aGetData['searchname']) ? $aGetData['searchname'] : '';
        $aData['rows'] = isset($aGetData['rows']) ? $aGetData['rows'] : 10;
        $aData['page'] = isset($aGetData['page']) ? $aGetData['page'] : 1;
        $aData['sTime'] = isset($aGetData['sTime']) ? $aGetData['sTime'] : 0;
        $aData['eTime'] = isset($aGetData['eTime']) ? $aGetData['eTime'] : 0;
        return $aData;
    }
}