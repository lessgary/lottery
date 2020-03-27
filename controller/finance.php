<?php

/**
 * @desc 功能: 控制器 - 用户管理
 * 
 * @author    rhovin
 * @package   proxyweb
 * @date 2017-09-20
 */
class controller_finance extends pcontroller {
    /**
     * desc 财务报表总列表
     * @author rhovin 2017-09-20
     */
    function actionIndex() {
        $oFinanceModel = new model_finance();
        $oFinanceModel->iLvtopId = $this->lvtopid;
        if($this->getIsPost()) {
            $aGetData = $this->post(array(
                "startdt" => parent::VAR_TYPE_DATE,
                "enddt" => parent::VAR_TYPE_DATE,
                ));
            if($aGetData['startdt'] == '' || $aGetData['enddt'] == '') {
                $this->ajaxMsg(0,"查询时间不能为空");
            }
            if(ceil((strtotime($aGetData['enddt'])-strtotime($aGetData['startdt']))/86400) >30){
                $this->ajaxMsg(0,"最多查询近30天内的数据");
            }
            $aAllData = $oFinanceModel->iniDateList($aGetData['startdt'],$aGetData['enddt'])  //以时间序列为基础合并各个表数据  合并数据没有先后顺序
                      ->arrayMergeArray($oFinanceModel->getCompanyMoney())   // 公司入款
                      ->arrayMergeArray($oFinanceModel->getFastPayMoney())     //三方入款
                      ->arrayMergeArray($oFinanceModel->getManualPayMoney()) //人工存款存入
                      ->arrayMergeArray($oFinanceModel->getOrdinaryPay())    //人工普通存入
                      ->arrayMergeArray($oFinanceModel->getManualDrawMoney())//人工提款
                      ->arrayMergeArray($oFinanceModel->getActivityMoney())  //活动优惠
                      ->arrayMergeArray($oFinanceModel->getFastWithdraw())     //三方提现
                      ->arrayMergeArray($oFinanceModel->getBankWithdraw())     //银行卡提现
                      ->arrayMergeArray($oFinanceModel->getPreport())      //平台返点
                      ->arrayMergeArray($oFinanceModel->getFeeUser())      //提现扣取手续费人数
                      ->selectAll(true);
            if(!empty($aAllData['mergeData']) && !empty($aAllData['total'])) {
                $this->outPutJQgruidJson($aAllData['mergeData'],count($aAllData['mergeData']) , 1, 100,$aAllData['total']);    
            } else {
                $this->ajaxMsg(0,"未查到相关数据");
            }
        } 
        $sTimes = array("sdate" => parent::VAR_TYPE_DATE , "edate" => parent::VAR_TYPE_DATE);
        $sSdate = !empty($this->get($sTimes)["sdate"]) ? date("Y-m-d ") : date("Y-m-d" , strtotime("-7 days"));//判断是用get参数还是给默认的
        $sEdate = !empty($this->get($sTimes)["edate"]) ? date("Y-m-d") : date("Y-m-d");//判断是用get参数还是给默认的
        $GLOBALS['oView']->assign("sdate", $sSdate);
        $GLOBALS['oView']->assign("edate", $sEdate);
        $GLOBALS['oView']->display('finance_index.html');
    }

    /**
     * desc 人工存提统计用户资金列表
     * @author rhovin 2017-09-25
     */
    public function actionManuaInfo() {
        if($this->getIsPost()) {
            $aGetData = $this->post(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                "optype" => parent::VAR_TYPE_INT,
                "order_type" => parent::VAR_TYPE_INT,
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
            ));
            $oFinanceModel = new model_finance();
            $oFinanceModel->iLvtopId = $this->lvtopid;    
            $data = $oFinanceModel->getManuaInfo($aGetData, $aGetData['page'], $aGetData['rows']);
             $i = ($aGetData['page']-1)*$aGetData['rows'];
            foreach ($data['results'] as $key => &$value) {
                $i++ ;
                $value['number'] = $i;
            }
            $this->outPutJQgruidJson($data['results'],$data['affects'] , $aGetData['page'], $aGetData['rows']);  

        } else {
            $aGetData = $this->get(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                "optype" => parent::VAR_TYPE_INT,
                "order_type" => parent::VAR_TYPE_INT,
            ));
            $GLOBALS['oView']->assign("dateKey", $aGetData['dateKey']);
            $GLOBALS['oView']->assign("optype", $aGetData['optype']);
            $GLOBALS['oView']->assign("order_type", $aGetData['order_type']);
            $GLOBALS['oView']->display('finance_manuainfo.html');    
        }
    }

    /**
     * desc 人工存提用户资金列表明细
     * @author rhovin 2017-09-26
     */
    public function actionManuaIndex() {
         if($this->getIsPost()) {
            $aGetData = $this->post(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                "optype" => parent::VAR_TYPE_INT,
                "order_type" => parent::VAR_TYPE_INT,
                "userid" => parent::VAR_TYPE_INT,
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
            ));
            $oFinanceModel = new model_finance();
            $oFinanceModel->iLvtopId = $this->lvtopid;    
            $aResult = $oFinanceModel->getManuaInfoList($aGetData);
            $oUser = new model_puser();
            foreach ($aResult['results'] as $k => $v) {
                $aTopInfo = $oUser->getUserTreeInfo($v['lvproxyid'],['username']);
                $aParent= $oUser->getUserTreeInfo($v['parentid'],['username']);
                $aResult['results'][$k]['topproxy'] = $aTopInfo['username'];
                $aResult['results'][$k]['parentname'] = $aParent['username'];
            }
            $this->outPutJQgruidJson($aResult['results'],$aResult['affects'] , $aGetData['page'], $aGetData['rows']);  

        } else {
            $aGetData = $this->get(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                "optype" => parent::VAR_TYPE_INT,
                "order_type" => parent::VAR_TYPE_INT,
                "userid" => parent::VAR_TYPE_INT,
            ));
            $GLOBALS['oView']->assign("dateKey", $aGetData['dateKey']);
            $GLOBALS['oView']->assign("optype", $aGetData['optype']);
            $GLOBALS['oView']->assign("order_type", $aGetData['order_type']);
            $GLOBALS['oView']->assign("userid", $aGetData['userid']);
            $GLOBALS['oView']->display('finance_manuaindex.html');    
        }   
    }
    /**
     * desc 用户取款资金统计
     * @author rhovin 2017-09-26
     */
    public function actionWithdraw() {
        if($this->getIsAjax()) {
            $aGetData = $this->get(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                "withdraw_type" => parent::VAR_TYPE_INT,
                ));
            $aPostData = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
            ));
            $aPostData['page'] = isset($aPostData['page']) ? $aPostData['page'] : 1;
            $aPostData['rows'] = isset($aPostData['rows']) ? $aPostData['rows'] : 30;
            $oFinanceModel = new model_finance();
            $oFinanceModel->iLvtopId = $this->lvtopid;
            $sWhere = 1;
            $sWhere .= " AND DATE_FORMAT(uw.`finishtime` ,'%Y-%m-%d')='${aGetData['dateKey']}'";
            $sWhere .=" AND uw.`status`=2 AND uw.`withdraw_type`='${aGetData['withdraw_type']}' ";
            $orderby= "GROUP BY uw.`userid`";
            $data = $oFinanceModel->getUserWithdraw($sWhere, $aPostData['page'], $aPostData['rows'],$orderby);
            $i = ($aPostData['page']-1)*$aPostData['rows'];
            foreach ($data['results'] as $key => &$value) {
                $i++ ;
                $value['number'] = $i;
                }
            $this->outPutJQgruidJson($data['results'],$data['affects'] , $aPostData['page'], $aPostData['rows']);
        } else {
            $aGetData = $this->get(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                ));
            $GLOBALS['oView']->assign("dateKey", $aGetData['dateKey']);
            $GLOBALS['oView']->assign("withdraw_type", $_GET['withdraw_type']);
            $GLOBALS['oView']->display('finance_withdraw.html');
        }
    }
    /**
     * desc 用户取款列表明细
     * @author rhovin 2017-09-26
     */
    public function actionWithdrawIndex() {
         if($this->getIsAjax()) {
            $aGetData = $this->get(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                "userid" => parent::VAR_TYPE_INT,
                "withdraw_type" => parent::VAR_TYPE_INT,
            ));
             $aPostData = $this->post(array(
                 "page" => parent::VAR_TYPE_INT,
                 "rows" => parent::VAR_TYPE_INT,
             ));
             $aPostData['page'] = isset($aPostData['page']) ? $aPostData['page'] : 1;
             $aPostData['rows'] = isset($aPostData['rows']) ? $aPostData['rows'] : 30;
            $oFinanceModel = new model_finance();
            $oFinanceModel->iLvtopId = $this->lvtopid;   
            $sWhere = 1;
            $sWhere .= " AND DATE_FORMAT(uw.`finishtime` ,'%Y-%m-%d')='${aGetData['dateKey']}'"; 
            $sWhere .=" AND uw.`userid`=${aGetData['userid']} AND uw.`withdraw_type`=${aGetData['withdraw_type']}";
            $aResult = $oFinanceModel->getWithdrawList($sWhere,$aPostData['page'], $aPostData['rows']);
            $oUser = new model_puser();
            foreach ($aResult['results'] as $k => $v) {
                $aTopInfo = $oUser->getUserTreeInfo($v['lvproxyid'],['username']);
                $aParent= $oUser->getUserTreeInfo($v['parentid'],['username']);
                $aResult['results'][$k]['topproxy'] = $aTopInfo['username'];
                $aResult['results'][$k]['parentname'] = $aParent['username'];
            }
            $this->outPutJQgruidJson($aResult['results'],$aResult['affects'] , $aPostData['page'], $aPostData['rows']);
        } else {
            $aGetData = $this->get(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                "userid" => parent::VAR_TYPE_INT,
            ));
            $GLOBALS['oView']->assign("dateKey", $aGetData['dateKey']);
            $GLOBALS['oView']->assign("userid", $aGetData['userid']);
            $GLOBALS['oView']->display('finance_withdrawindex.html');
        }   
    }
    /**
     * desc 统计某天用户提款手续费和行政费
     * @author rhovin 2017-09-27
     */
    public function actionWithdrawFee() {
        if($this->getIsPost()) {
            $aGetData = $this->post(array(
                "dateKey" => parent::VAR_TYPE_DATE,
            ));
            $oFinanceModel = new model_finance();
            $oFinanceModel->iLvtopId = $this->lvtopid;    
            $aResult = $oFinanceModel->getUserWithdrawFeeCount($aGetData['dateKey']);
            $this->ajaxMsg(1,"查询成功",$aResult);
        }
    }
    /**
     * desc 用户提现手续费详情
     * @author rhovin 2017-09-27
     */
    public function actionWithdrawInfo() {
      if($this->getIsPost()) {
              $aGetData = $this->post(array(
                  "dateKey" => parent::VAR_TYPE_DATE,
                  "type"=>parent::VAR_TYPE_INT, //1 手续费  2 行政费
                  "page" => parent::VAR_TYPE_INT,
                  "rows" => parent::VAR_TYPE_INT,
              ));
              $oFinanceModel = new model_finance();
              $oFinanceModel->iLvtopId = $this->lvtopid;   
              $sWhere = 1;
              $sWhere .= " AND DATE_FORMAT(uw.`finishtime` ,'%Y-%m-%d')='${aGetData['dateKey']}'";
              if($aGetData['type'] == 1){
                  $sWhere .=" AND uw.charge > 0" ;
              } else {
                  $sWhere .=" AND uw.admin_fee > 0" ;
              }
              $sWhere .=" AND uw.`status`=2 "; 
               $orderby= "GROUP BY uw.`userid`";
              $data = $oFinanceModel->getUserWithdraw($sWhere, $aGetData['page'], $aGetData['rows'],$orderby);
               $i = ($aGetData['page']-1)*$aGetData['rows'];
              foreach ($data['results'] as $key => &$value) {
                  $i++ ;
                  $value['number'] = $i;
                  if($aGetData['type'] == 2){ //如果是行政费和手续费使用同一模板
                      $value['charge'] = $value['admin_fee'];
                  }
                }
              $this->outPutJQgruidJson($data['results'],$data['affects'] , $aGetData['page'], $aGetData['rows']);  
          } else {
              $aGetData = $this->get(array(
                  "dateKey" => parent::VAR_TYPE_DATE,
                  "type"=>parent::VAR_TYPE_INT,
              ));
              $GLOBALS['oView']->assign("dateKey", $aGetData['dateKey']);
              $GLOBALS['oView']->assign("type", $aGetData['type']);
              $GLOBALS['oView']->display('finance_withdrawinfo.html');  
          }
    }
    /**
     * desc 手续费明细
     * @author rhovin 2017-09-27
     */
    public function actionWithdrawFeeIndex() {
        if($this->getIsPost()) {
            $aGetData = $this->post(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                "userid" => parent::VAR_TYPE_INT,
                "type" => parent::VAR_TYPE_INT,
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
            ));
            $oFinanceModel = new model_finance();
            $oFinanceModel->iLvtopId = $this->lvtopid;   
            $sWhere = 1;
            $sWhere .= " AND DATE_FORMAT(uw.`finishtime` ,'%Y-%m-%d')='${aGetData['dateKey']}'"; 
            $sWhere .=" AND uw.`userid`=${aGetData['userid']}";
            if($aGetData['type'] == 1) {
                $sWhere .=" AND uw.charge > 0" ;
            } else {
                $sWhere .=" AND uw.admin_fee > 0" ;
            }
            $aResult = $oFinanceModel->getWithdrawList($sWhere,$aGetData['page'], $aGetData['rows']);
            $oUser = new model_puser();
            foreach ($aResult['results'] as $k => $v) {
                $aTopInfo = $oUser->getUserTreeInfo($v['lvproxyid'],['username']);
                $aParent= $oUser->getUserTreeInfo($v['parentid'],['username']);
                $aResult['results'][$k]['topproxy'] = $aTopInfo['username'];
                $aResult['results'][$k]['parentname'] = $aParent['username'];
                if($aGetData['type'] ==1 ){
                    $aResult['results'][$k]['fee'] = $v['charge'];
                }else {
                    $aResult['results'][$k]['fee'] = $v['admin_fee'];
                }
            }
            $this->outPutJQgruidJson($aResult['results'],$aResult['affects'] , $aGetData['page'], $aGetData['rows']);  
        } else {
            $aGetData = $this->get(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                "userid" => parent::VAR_TYPE_INT,
                "type" => parent::VAR_TYPE_INT,
            ));
            $GLOBALS['oView']->assign("dateKey", $aGetData['dateKey']);
            $GLOBALS['oView']->assign("userid", $aGetData['userid']);
            $GLOBALS['oView']->assign("type", $aGetData['type']);
            $GLOBALS['oView']->display('finance_withdrawfeeindex.html');    
        }   
    }


    /**
     * 公司入款统计
     * @author pierce
     * @date 2017-09-25
     */
    function actionCompanyPayment() {
        $oFinanceModel = new model_finance();
        $oFinanceModel->iLvtopId = $this->lvtopid;
        if($this->getIsPost()) {
            $aGetData = $this->post(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                "layerid" => parent::VAR_TYPE_STR,
            ));
            $sWhere = "`lvtopid` = '".$this->lvtopid."' AND `status` = 2";
            if (isset($aGetData['dateKey']) && $aGetData['dateKey'] != '') {
                $sWhere .= " AND DATE_FORMAT(`finishtime` ,'%Y-%m-%d') ='" . $aGetData['dateKey'] . "'";
            }
            if (isset($aGetData['layerid']) && $aGetData['layerid'] != '') {
                $sWhere .= " AND `layerid` IN '" . $aGetData['layerid'] . "'";
            }
            $aCompanyPayment = $oFinanceModel->getCompanyPayment($sWhere);
            $this->ajaxMsg(1,"查询成功",$aCompanyPayment);
        }
    }

    /**
     * @desc 获取公司入款分类数据
     * @author pierce
     * @date 2017-09-25
     */
    function actionCompanyCategory() {
        $oFinanceModel = new model_finance();
        $oFinanceModel->iLvtopId = $this->lvtopid;
        if($this->getIsAjax()) {
            $aGetData = $this->get(array(
                "inserdate" => parent::VAR_TYPE_DATE,
                "layerid" => parent::VAR_TYPE_STR,
                "category" => parent::VAR_TYPE_INT,
            ));
            $sWhere = "udc.`lvtopid` = '".$this->lvtopid."' AND udc.`status` = 2";
            if (isset($aGetData['inserdate']) && $aGetData['inserdate'] != '') {
                $sWhere .= " AND DATE_FORMAT(udc.`finishtime` ,'%Y-%m-%d') ='" . $aGetData['inserdate'] . "'";
            }
            if (isset($aGetData['layerid']) && $aGetData['layerid'] != '') {
                $sWhere .= " AND udc.`layerid` IN '" . $aGetData['layerid'] . "'";
            }
            //category是公司入款类型：1.银行卡入款，2.微信，3.支付宝, 4.qq钱包
            if (isset($aGetData['category']) && $aGetData['category'] == 1) {
                $sWhere .= " AND udc.`bankid` < 100";
            }elseif (isset($aGetData['category']) && $aGetData['category'] == 2) {
                $sWhere .= " AND udc.`bankid` = 101";
            }elseif (isset($aGetData['category']) && $aGetData['category'] == 3) {
                $sWhere .= " AND udc.`bankid` = 102";
            }elseif (isset($aGetData['category']) && $aGetData['category'] == 4) {
                $sWhere .= " AND udc.`bankid` = 103";
            }
            $aCompanyCategory = $oFinanceModel->getCompanyCategory($sWhere);
            if (empty($aCompanyCategory)){
                $this->outPutJQgruidJson(array(),0);
            }
            foreach ($aCompanyCategory as $key => &$v) {
                $v['order'] = $key + 1;
            }
            if (!empty($aCompanyCategory) && !empty($aGetData)) {
                $this->outPutJQgruidJson($aCompanyCategory,count($aCompanyCategory));
            }
        }else {
            $GLOBALS['oView']->display('finance_companycategory.html');
        }
    }

    /**
     * 公司入款账号入款记录
     * @author pierce
     * @date 2017-09-26
     */
    function actionCompanyPaymentInfo() {
        $oFinanceModel = new model_finance();
        $oFinanceModel->iLvtopId = $this->lvtopid;
        if($this->getIsAjax()) {
            $aGetData = $this->get(array(
                "inserdate" => parent::VAR_TYPE_DATE,
                "layerid" => parent::VAR_TYPE_STR,
                "company_payacc_id" => parent::VAR_TYPE_INT,
                "category" => parent::VAR_TYPE_INT
            ));
            $sWhere = "`lvtopid` = '".$this->lvtopid."' AND `status` = 2";
            if (isset($aGetData['inserdate']) && $aGetData['inserdate'] != '') {
                $sWhere .= " AND DATE_FORMAT(`finishtime` ,'%Y-%m-%d') ='" . $aGetData['inserdate'] . "'";
            }
            if (isset($aGetData['layerid']) && $aGetData['layerid'] != '') {
                $sWhere .= " AND `layerid` IN '" . $aGetData['layerid'] . "'";
            }
            if (isset($aGetData['company_payacc_id']) && $aGetData['company_payacc_id'] > 0) {
                $sWhere .= " AND `company_payacc_id` = '" . $aGetData['company_payacc_id'] . "'";
            }

            //category是公司入款类型：1.银行卡入款，2.微信，3.支付宝
            if ($aGetData['category'] == 1) {
                $sWhere .= " AND `bankid` < 100";
            }elseif ($aGetData['category'] == 2) {
                $sWhere .= " AND `bankid` = 101";
            }elseif ($aGetData['category'] == 3) {
                $sWhere .= " AND `bankid` = 102";
            }

            $aCompanyPaymentInfo = $oFinanceModel->getPaymentListByPayAccId($sWhere);
            if (empty($aCompanyPaymentInfo)) {
                $this->outPutJQgruidJson(array(),0);
            }
            foreach ($aCompanyPaymentInfo as $key => &$v) {
                $v['order'] = $key + 1;
            }
            if (!empty($aCompanyPaymentInfo) && !empty($aGetData)) {
                $this->outPutJQgruidJson($aCompanyPaymentInfo,count($aCompanyPaymentInfo));
            }
        }else {
            $aPayacc = $oFinanceModel->getBankInfoByPayAccId($_GET['company_payacc_id'],$this->lvtopid);
            if ($aPayacc['bankid'] < 100) {
                $aPayacc['category'] = "银行卡入款";
            }else if ($aPayacc['bankid'] == 101) {
                $aPayacc['category'] = "微信充值";
            }else if ($aPayacc['bankid'] == 102) {
                $aPayacc['category'] = "支付宝充值";
            }
            $sPayaccInfo = "公司入款/".$aPayacc['category']."/".$aPayacc['bankname']."/".$aPayacc['nickname']."/".$aPayacc['payee'];
            $GLOBALS['oView']->assign("sPayaccInfo", $sPayaccInfo);
            $GLOBALS['oView']->display('finance_companypaymentinfo.html');
        }
    }
    /**
     * 第三方入款统计
     * @author pierce
     * @date 2017-09-26
     */
    function actionFastpayPayment() {
        $oFinanceModel = new model_finance();
        $oFinanceModel->iLvtopId = $this->lvtopid;
        if($this->getIsGet()) {
            $aGetData = $this->get(array(
                "inserdate" => parent::VAR_TYPE_DATE,
                "layerid" => parent::VAR_TYPE_STR,
            ));
            $sWhere = "udf.`lvtopid` = '".$this->lvtopid."' AND udf.`status` IN (2,4)";
            if (isset($aGetData['inserdate']) && $aGetData['inserdate'] != '') {
                $sWhere .= " AND DATE_FORMAT(udf.`finishtime` ,'%Y-%m-%d') ='" . $aGetData['inserdate'] . "'";
            }
            if (isset($aGetData['layerid']) && $aGetData['layerid'] != '') {
                $sWhere .= " AND udf.`layerid` IN '" . $aGetData['layerid'] . "'";
            }
            $aFastpayPayment = $oFinanceModel->getFastpayPayment($sWhere);
        }
        $GLOBALS['oView']->assign("aFastpayPayment", $aFastpayPayment);
        $GLOBALS['oView']->display('finance_fastpaypayment.html');
    }

    /**
     * 第三方分类统计
     * @author pierce
     * @date 2017-09-27
     */
    function actionFastpayCategory()
    {
        $oFinanceModel = new model_finance();
        $oFinanceModel->iLvtopId = $this->lvtopid;
        if ($this->getIsAjax()) {
            $aGetData = $this->get(array(
                "inserdate" => parent::VAR_TYPE_DATE,
                "layerid" => parent::VAR_TYPE_STR,
                "company_fastpayacc_id" => parent::VAR_TYPE_INT,
                "paytypeid" => parent::VAR_TYPE_INT,
            ));
            $sWhere = "`lvtopid` = '" . $this->lvtopid . "' AND `status` IN (2,4)";
            if (isset($aGetData['inserdate']) && $aGetData['inserdate'] != '') {
                $sWhere .= " AND DATE_FORMAT(`finishtime` ,'%Y-%m-%d') ='" . $aGetData['inserdate'] . "'";
            }
            if (isset($aGetData['layerid']) && $aGetData['layerid'] != '') {
                $sWhere .= " AND `layerid` IN '" . $aGetData['layerid'] . "'";
            }
            if (isset($aGetData['company_fastpayacc_id']) && $aGetData['company_fastpayacc_id'] > 0) {
                $sWhere .= " AND `company_fastpayacc_id` = '" . $aGetData['company_fastpayacc_id'] . "'";
            }
            if (isset($aGetData['paytypeid']) && $aGetData['paytypeid'] > 0) {
                $sWhere .= " AND `paytypeid` = '" . $aGetData['paytypeid'] . "'";
            }
            $aFastpayPaymentInfo = $oFinanceModel->getFastpayPaymentList($sWhere);
            if (empty($aFastpayPaymentInfo)) {
                $this->outPutJQgruidJson(array(), 0);
            }
            foreach ($aFastpayPaymentInfo as $key => &$v) {
                $v['order'] = $key + 1;
            }
            if (!empty($aFastpayPaymentInfo) && !empty($aGetData)) {
                $this->outPutJQgruidJson($aFastpayPaymentInfo, count($aFastpayPaymentInfo));
            }
        } else {
            $sPayaccInfo = "第三方支付/".$_GET['nickname'];
            $GLOBALS['oView']->assign("sPayaccInfo", $sPayaccInfo);
            $GLOBALS['oView']->display('finance_companypaymentinfo.html');
        }
    }

    /**
     * 给予优惠
     * @author pierce
     * @date 2017-09-26
     */
    function actionDiscount() {
        $oFinanceModel = new model_finance();
        $oFinanceModel->iLvtopId = $this->lvtopid;
        if($this->getIsPost()) {
            $aGetData = $this->post(array(
                "dateKey" => parent::VAR_TYPE_DATE,
                "layerid" => parent::VAR_TYPE_STR,
            ));
            $sWhere = "`lvtopid` = '".$this->lvtopid."'";
            if (isset($aGetData['dateKey']) && $aGetData['dateKey'] != '') {
                $sWhere .= " AND DATE_FORMAT(`finishtime` ,'%Y-%m-%d') ='" . $aGetData['dateKey'] . "'";
            }
            if (isset($aGetData['layerid']) && $aGetData['layerid'] != '') {
                $sWhere .= " AND `layerid` IN '" . $aGetData['layerid'] . "'";
            }
            $aDiscount = $oFinanceModel->getDiscount($sWhere);
            $this->ajaxMsg(1,"查询成功",$aDiscount);
        }
    }

    /**
     * 给予优惠分类
     * @author pierce
     * @date 2017-09-27
     */
    function actionDiscountCategory()
    {
        $oFinanceModel = new model_finance();
        $oFinanceModel->iLvtopId = $this->lvtopid;
        if ($this->getIsAjax()) {
            $aGetData = $this->get(array(
                "inserdate" => parent::VAR_TYPE_DATE,
                "layerid" => parent::VAR_TYPE_STR,
                "category" => parent::VAR_TYPE_INT,
            ));
            $sWhere = "`lvtopid` = '" . $this->lvtopid . "'";
            if (isset($aGetData['inserdate']) && $aGetData['inserdate'] != '') {
                $sWhere .= " AND DATE_FORMAT(`finishtime` ,'%Y-%m-%d') ='" . $aGetData['inserdate'] . "'";
            }
            if (isset($aGetData['layerid']) && $aGetData['layerid'] != '') {
                $sWhere .= " AND `layerid` IN '" . $aGetData['layerid'] . "'";
            }
            if (isset($aGetData['category']) && $aGetData['category'] > 0) {
                if($aGetData['category'] == 1) {
                    $aDiscountCategory = $oFinanceModel->getCompanyDiscount($sWhere);
                }elseif ($aGetData['category'] == 2) {
                    $aDiscountCategory = $oFinanceModel->getFastpayDiscount($sWhere);
                }elseif ($aGetData['category'] == 3) {
                    $aDiscountCategory = $oFinanceModel->getDepositDiscount($sWhere);
                }elseif ($aGetData['category'] == 4) {
                    $aDiscountCategory = $oFinanceModel->getActivityDiscount($sWhere);
                }
            }
            if (empty($aDiscountCategory)) {
                $this->outPutJQgruidJson(array(), 0);
            }
            foreach ($aDiscountCategory as $key => &$v) {
                $v['order'] = $key + 1;
            }
            if (!empty($aDiscountCategory) && !empty($aGetData)) {
                $this->outPutJQgruidJson($aDiscountCategory, count($aDiscountCategory));
            }
        } else {
            $GLOBALS['oView']->display('finance_companypaymentinfo.html');
        }
    }

}