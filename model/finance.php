<?php

/**
 * desc 财务报表模型 /proxyweb/model/finance
 * @author     Rhovin 2017-09-20
 * @package    proxyweb
 */
class model_finance extends basemodel {

    // 错误消息
    public $_errMsg ;

    //总代Id
    public $iLvtopId ;

    //初始化数据
    private $initData = [] ;

    //合并的结果集
    public $mergeData = [];

    //开始时间
    public $startDt;

    //结束时间
    public $endDt;

    public function __construct($aDBO = []) {
        parent::__construct($aDBO);
        $this->setInitData();  //初始化数据
    }
    /**
     * desc 查询财务报表整个列表数据
     * @author rhovin 2017-09-20
     * @param $skipToday bool 统计是否忽略当天数据,默认不忽略
     */
    public function selectAll($skipToday = false) {
        $total_company_money=[];
        if(!empty($this->mergeData)) {
            foreach ($this->mergeData as $key => &$value) {
                $value['selfpoints'] = sprintf("%.4f",$value['selfpoints']);
               //$tempModel = $this->buildTempModel($value);
                $value['total_favor'] = sprintf("%.2f",($value['company_favor']+$value['fastpay_favor']+$value['manuapay_favor']+$value['activity_money']));
                $dayCount=$value['company_money']+$value['fastpay_money']+$value['manuapay_money']+$value['manuaordinarypay_money']+$value['withdraw_fee']+$value['manuadraw_money']-$value['total_favor']-$value['selfpoints']-$value['parentspoints']-$value['bank_withdraw_money']-$value['fast_withdraw_money'];
                $value['onedaydata'] = $dayCount != 0 ? sprintf("%.2f",$dayCount) : '0.00';
                //存放总计数据
                if ($skipToday && $value['dateKey'] === date('Y-m-d')) {
                    $value['selfpoints'] = '尚未结算';
                    $value['parentspoints'] = '尚未结算';
                    $value['onedaydata'] = '尚未结算';
                } else {
                    $total_onedaydata[] = $value['onedaydata'];
                    $total_favor_money[] = $value['company_favor']+$value['fastpay_favor']+$value['manuapay_favor']+$value['activity_money'];
                    $total_favor_user[] = $value['compay_favor_user']+$value['fast_favor_user']+$value['manua_favor_user'];
                    $total_company_money[] = $value['company_money'];
                    $total_company_usernum[] = $value['company_usernum'];
                    $total_fastpay_money[] = $value['fastpay_money'];
                    $total_fastpay_usernum[] = $value['fastpay_usernum'];
                    $total_manuapay_money[] = $value['manuapay_money'];
                    $total_manuapay_usernum[] = $value['manuapay_usernum'];
                    $total_manuaordinarypay_money[] = $value['manuaordinarypay_money'];
                    $total_manuaordinarypay_usernum[] = $value['manuaordinarypay_usernum'];
                    $total_selfpoints[] = $value['selfpoints'];
                    $total_parentspoints[] = $value['parentspoints'];
                    $total_bank_withdraw_money[] = $value['bank_withdraw_money'];
                    $total_fast_withdraw_money[] = $value['fast_withdraw_money'];
                    $total_bank_withdraw_usernum[] = $value['bank_withdraw_usernum'];
                    $total_fast_withdraw_usernum[] = $value['fast_withdraw_usernum'];
                    $total_manuadraw_money[] = $value['manuadraw_money'];
                    $total_manuadraw_usernum[] = $value['manuadraw_usernum'];
                    $total_withdraw_fee[] = $value['withdraw_fee'];
                    $total_drawfee_usernum[] = $value['drawfee_usernum'];
                }
                //列表
                $value['company_money'] =$value['company_money'].'('.$value['company_usernum'].')人';  
                $value['fastpay_money'] =$value['fastpay_money'].'('.$value['fastpay_usernum'].')人';  
                $value['manuapay_money'] =$value['manuapay_money'].'('.$value['manuapay_usernum'].')人';  
                $value['manuaordinarypay_money'] =$value['manuaordinarypay_money'].'('.$value['manuaordinarypay_usernum'].')人';  
                $value['bank_withdraw_money'] =$value['bank_withdraw_money'].'('.$value['bank_withdraw_usernum'].')人';
                $value['fast_withdraw_money'] =$value['fast_withdraw_money'].'('.$value['fast_withdraw_usernum'].')人';
                $value['withdraw_fee'] =$value['withdraw_fee'].'('.$value['drawfee_usernum'].')人';
                $value['manuadraw_money'] =$value['manuadraw_money'].'('.$value['manuadraw_usernum'].')人';  
            }
            //总计
            $total = [] ;
            $total['total_company'] = sprintf("%.2f",array_sum($total_company_money)).'('.array_sum($total_company_usernum).')人'; //公司入款
            $total['total_fastpay'] = sprintf("%.2f",array_sum($total_fastpay_money)).'('.array_sum($total_fastpay_usernum).')人'; //三方入款
            $total['total_manuapay'] = sprintf("%.2f",array_sum($total_manuapay_money)) .'('.array_sum($total_manuapay_usernum).')人';//人工存款存入
            //人工普通存入
            $total['total_manuaordinarypay'] = sprintf("%.2f",array_sum($total_manuaordinarypay_money)) .'('.array_sum($total_manuaordinarypay_usernum).')人';
            $total['total_favor'] = sprintf("%.2f",array_sum($total_favor_money)) ;//总优惠
            $total['total_selfpoints'] = sprintf("%.2f",array_sum($total_selfpoints));//总用户返点
            $total['total_parentspoints'] = sprintf("%.2f",array_sum($total_parentspoints));//总的代理返点
            $total['total_bank_withdraw'] = sprintf("%.2f",array_sum($total_bank_withdraw_money)).'('.array_sum($total_bank_withdraw_usernum).')人';//用户银行卡提现
            $total['total_fast_withdraw'] = sprintf("%.2f",array_sum($total_fast_withdraw_money)).'('.array_sum($total_fast_withdraw_usernum).')人';//用户三方提现
            $total['total_manuadraw'] = sprintf("%.2f",array_sum($total_manuadraw_money)).'('.array_sum($total_manuadraw_usernum).')人';//人工取款
            $total['total_withdraw_fee'] = sprintf("%.2f",array_sum($total_withdraw_fee)).'('.array_sum($total_drawfee_usernum).')人';//出款手续费
            $total['total_data'] = sprintf("%.2f",array_sum($total_onedaydata));
        }
       
        return ['mergeData'=>$this->mergeData,'total'=>!empty($total) ? $total : []];
    }

    /**
     * desc 按时间dateKey 将相同的数据到一个数组
     * @author rhovin 2017-09-20
     */
    public function arrayMergeArray($brr = []) {
        $date = [];
        foreach ($brr as $key => $value) {
            $date[$value['dateKey']]=$value;
        }
        $aMergeData = [] ; 
        if(!empty($this->mergeData)) {
            foreach ($this->mergeData as $key => $v) {
                $exist = array_key_exists($v['dateKey'], $date);
                $aMergeData[$key] = $exist ? array_merge($v,$date[$v['dateKey']]) : $v ;
            }
        }
       $this->mergeData = $aMergeData;
       return $this;
    }

     /**
     * desc 初始化表格数据
     * @author rhovin 2017-09-20
     * 
     */
    public function setInitData () {
        $this->initData = [
            'company_money'=>'0.00',         //公司入款金额 
            'company_favor'=>'0.00',         //公司入款优惠 
            'company_usernum'=>0,       //公司入款人数
            'compay_favor_user'=>0,            //公司入款优惠
            'fastpay_money'=>'0.00',         //三方支付金额
            'fastpay_usernum'=>0,               //三方支付人数
            'fastpay_favor'=>'0.00',         //三方支付优惠
            'fast_favor_user'=>0,            //三方入款优惠人数
            'manuaordinarypay_money'=>'0.00',    //人工普通存入金额
            'manuaordinarypay_usernum'=>0,  //人工普通存入人数
            'manuapay_money'=>'0.00',     //人工存款存入金额
            'manuapay_favor'=>'0.00',     //人工存款存入优惠
            'manuapay_usernum'=>0 ,        //人工存款存入人数
            'manua_favor_user'=>0,         //人工存款优惠人数
            'withdraw_fee'=>'0.00',      //用户提现手续费
            'bank_withdraw_money'=>'0.00',   //用户银行卡提现手金额
            'fast_withdraw_money'=>'0.00',   //用户三方提现手金额
            'bank_withdraw_usernum'=>0,       //用户银行卡提现人数
            'fast_withdraw_usernum'=>0,       //用户三方提现人数
            'drawfee_usernum'=>0 ,       //用户提现扣除了手续费的人数
            'manuadraw_money'=>'0.00', //人工取款金额
            'manuadraw_usernum'=>0, //人工取款人数
            'selfpoints' => '0.000',       //用户返点
            'parentspoints' => '0.000',     //代理总返点
            'activity_money'=>'0.00',       //活动优惠
        ];
    }
    /**
     *desc设置商户ID
     */
    public function setLvtopId($iLvtopId) {
        $this->iLvtopId = $iLvtopId;
    }

    /**
     *desc 获取lvtopid 条件 e.x lvtopid=1;
     */
    public function getLvtopIdStr($column = NULL) {
        return $sWhere = $column !== NULL && $this->iLvtopId !==NULL ? $column.'='.$this->iLvtopId : '';
        
    }
    /**
     * desc 获取按天统计的公司入款列表数据
     * @author rhovin 2017-09-20
     */
    public function getCompanyMoney($sWhere = '') {
        $sSql = "SELECT  DATE_FORMAT(finishtime ,'%Y-%m-%d') AS dateKey, round(SUM(`favor_amount`),2) AS company_favor,if(`favor_amount`!=0,COUNT(DISTINCT(`userid`)),0) AS compay_favor_user,round(SUM(`real_amount`),2) AS company_money,COUNT(DISTINCT(`userid`)) AS company_usernum FROM user_deposit_company  WHERE DATE_FORMAT(finishtime ,'%Y-%m-%d')>='{$this->startDt}' AND  DATE_FORMAT(finishtime ,'%Y-%m-%d')<='{$this->endDt}' AND `status`=2 AND `lvtopid`={$this->iLvtopId} GROUP BY DATE_FORMAT(finishtime ,'%Y-%m-%d') ";
        return $this->oDB->getAll($sSql);
    }
    /**
     * desc 获取按天统计的三方支付列表
     * @author rhovin 2017-09-20
     */
    public function getFastPayMoney($sWhere = '') {
        $sSql = "SELECT  DATE_FORMAT(finishtime ,'%Y-%m-%d') AS dateKey, round(SUM(`favor_amount`),2) AS fastpay_favor, if(`favor_amount`!=0,COUNT(DISTINCT(`userid`)),0) AS fast_favor_user,round(SUM(`apply_amount`)-SUM((`charge`)),2) AS fastpay_money,COUNT(DISTINCT(`userid`)) AS fastpay_usernum FROM user_deposit_fastpay  WHERE DATE_FORMAT(finishtime ,'%Y-%m-%d')>='{$this->startDt}' AND  DATE_FORMAT(finishtime ,'%Y-%m-%d')<='{$this->endDt}' AND `status` IN(2,4) AND `lvtopid`={$this->iLvtopId} GROUP BY DATE_FORMAT(finishtime ,'%Y-%m-%d') ";
        return $this->oDB->getAll($sSql);
    }
    /**
     * desc 获取按天统计的人工存款存入
     * @author rhovin 2017-09-20
     * order_type  1 存款存入 0 普通存入
     */
    public function getManualPayMoney($sWhere = '') {
         $sSql = "SELECT  DATE_FORMAT(finishtime ,'%Y-%m-%d') AS dateKey, round(SUM(`ext_amount`),2) AS manuapay_favor, if(`ext_amount`!=0,COUNT(DISTINCT(`user_ids`)),0) AS manua_favor_user,round(SUM(`amount`),2) AS manuapay_money,COUNT(DISTINCT(`user_ids`)) AS manuapay_usernum FROM manualpay_confirm  WHERE DATE_FORMAT(finishtime ,'%Y-%m-%d')>='{$this->startDt}' AND  DATE_FORMAT(finishtime ,'%Y-%m-%d')<='{$this->endDt}' AND `isconfirm`=1 AND `optype`=0 AND order_type=1  AND `lvtopid`={$this->iLvtopId} GROUP BY DATE_FORMAT(finishtime ,'%Y-%m-%d') ";
        return $this->oDB->getAll($sSql);
    }

    /**
     * desc 获取按天统计的人工普通存入
     * @author rhovin 2017-09-20
     * order_type  1 存款存入 0 普通存入
     */
    public function getOrdinaryPay($sWhere = '') {
         $sSql = "SELECT  DATE_FORMAT(finishtime ,'%Y-%m-%d') AS dateKey, round(SUM(`amount`),2) AS manuaordinarypay_money,COUNT(DISTINCT(`user_ids`)) AS manuaordinarypay_usernum FROM manualpay_confirm  WHERE DATE_FORMAT(finishtime ,'%Y-%m-%d')>='{$this->startDt}' AND  DATE_FORMAT(finishtime ,'%Y-%m-%d')<='{$this->endDt}' AND `isconfirm`=1 AND `optype`=0 AND order_type=0 AND `lvtopid`={$this->iLvtopId} GROUP BY DATE_FORMAT(finishtime ,'%Y-%m-%d') ";
        return $this->oDB->getAll($sSql);
    }
     /**
     * desc 获取按天统计的活动优惠
     * @author rhovin 2017-09-20
     * order_type  1 存款存入 0 普通存入 3 活动优惠
     */
    public function getActivityMoney($sWhere = '') {
         $sSql = "SELECT  DATE_FORMAT(finishtime ,'%Y-%m-%d') AS dateKey, round(SUM(`amount`),2) AS activity_money FROM manualpay_confirm  WHERE DATE_FORMAT(finishtime ,'%Y-%m-%d')>='{$this->startDt}' AND  DATE_FORMAT(finishtime ,'%Y-%m-%d')<='{$this->endDt}' AND `isconfirm`=1 AND `optype`=0 AND order_type=3  AND `lvtopid`={$this->iLvtopId} GROUP BY DATE_FORMAT(finishtime ,'%Y-%m-%d') ";
        return $this->oDB->getAll($sSql);
    }
    /**
     * desc 获取按天统计的人工取款
     * @author rhovin 2017-09-21
     */
    public function getManualDrawMoney($sWhere = '') {
         $sSql = "SELECT  DATE_FORMAT(finishtime ,'%Y-%m-%d') AS dateKey,round(SUM(`amount`),2) AS manuadraw_money,COUNT(DISTINCT(`user_ids`)) AS manuadraw_usernum FROM manualpay_confirm  WHERE DATE_FORMAT(finishtime ,'%Y-%m-%d')>='{$this->startDt}' AND  DATE_FORMAT(finishtime ,'%Y-%m-%d')<='{$this->endDt}' AND `isconfirm`=1 AND `optype`=1 AND  order_type IN(4,5,6,7) AND `lvtopid`={$this->iLvtopId} GROUP BY DATE_FORMAT(finishtime ,'%Y-%m-%d') ";
        return $this->oDB->getAll($sSql);
    }
    /**
     * desc 按天获取用户银行卡提现数据
     * @author rhovin 2017-09-21
     */
    public function getBankWithdraw ($sWhere = '') {
        $sSql = "SELECT DATE_FORMAT(`finishtime` ,'%Y-%m-%d') AS dateKey,COUNT(DISTINCT(`userid`)) AS bank_withdraw_usernum, `layerid`,SUM(`admin_fee`+`charge`) AS withdraw_fee,SUM(`real_amount`) AS bank_withdraw_money ,if(SUM(`admin_fee`+`charge`)!=0,COUNT(DISTINCT(`userid`)),0) AS drawfee_usernum FROM user_withdraw WHERE DATE_FORMAT(`finishtime` ,'%Y-%m-%d')>='{$this->startDt}' AND  DATE_FORMAT(`finishtime` ,'%Y-%m-%d')<='{$this->endDt}' AND `status`=2 AND `withdraw_type`=0 AND `lvtopid`={$this->iLvtopId} GROUP BY DATE_FORMAT(finishtime ,'%Y-%m-%d')";
         return $this->oDB->getAll($sSql);
    }
    /**
     * desc 按天获取用户三方提现数据
     * @author rhovin 2017-09-21
     */
    public function getFastWithdraw ($sWhere = '') {
        $sSql = "SELECT DATE_FORMAT(`finishtime` ,'%Y-%m-%d') AS dateKey,COUNT(DISTINCT(`userid`)) AS fast_withdraw_usernum, `layerid`,SUM(`admin_fee`+`charge`) AS withdraw_fee,SUM(`real_amount`) AS fast_withdraw_money ,if(SUM(`admin_fee`+`charge`)!=0,COUNT(DISTINCT(`userid`)),0) AS drawfee_usernum FROM user_withdraw WHERE DATE_FORMAT(`finishtime` ,'%Y-%m-%d')>='{$this->startDt}' AND  DATE_FORMAT(`finishtime` ,'%Y-%m-%d')<='{$this->endDt}' AND `status`=2 AND `withdraw_type`=1 AND `lvtopid`={$this->iLvtopId} GROUP BY DATE_FORMAT(finishtime ,'%Y-%m-%d')";
         return $this->oDB->getAll($sSql);
    }
    /**
     * desc 按天获取用户提现数据扣取手续费和行政费人数
     * @author rhovin 2017-10-25
     */
    public function getFeeUser ($sWhere = '') {
        $sSql = "SELECT DATE_FORMAT(`finishtime` ,'%Y-%m-%d') AS dateKey,COUNT(DISTINCT(`userid`))  AS drawfee_usernum FROM user_withdraw WHERE DATE_FORMAT(`finishtime` ,'%Y-%m-%d')>='{$this->startDt}' AND  DATE_FORMAT(`finishtime` ,'%Y-%m-%d')<='{$this->endDt}' AND `status`=2 AND `lvtopid`={$this->iLvtopId} AND (`admin_fee`!=0 OR `charge`!=0) GROUP BY DATE_FORMAT(finishtime ,'%Y-%m-%d')";
         return $this->oDB->getAll($sSql);
    }

    /**
     * desc 按天获取用户返点数据
     * @author rhovin 2017-09-21
     *
     */
    public function getPreport($sWhere = 1) {
        $sSql = "SELECT `day` AS dateKey, sum(`points`) AS selfpoints FROM user_report WHERE userid = '".$this->iLvtopId."' AND `day` >= '".$this->startDt."' AND `day` <= '".$this->endDt."'  GROUP BY `day`";
        $aAllResult = $this->oDB->getAll($sSql);
        return $aAllResult;
    }
   
    /**
     * desc 初始化时间数据
     * @author rhovin 2017-09-20
     */
    public function iniDateList($startDt = '', $endDt='' ) {
        if($startDt == '' && $endDt == '') {
            $endDt = date("Y-m-d") ;
            $startDt = date("Y-m-d" , strtotime("-30 days")) ;
        }
        $this->startDt = $startDt;
        $this->endDt = $endDt;
        $sSql = "SELECT dateKey
        FROM (
        SELECT DATE_ADD('{$this->startDt}',
        INTERVAL(ones.num+tens.num+hundreds.num)day) dateKey
        FROM
        (SELECT 0 num union ALL select 1 num union all select 2 num union all select 3 num union all select 4 num union all
        select 5 num union all select 6 num union all select 7 num union all select 8 num union all select 9 num ) ones
        CROSS JOIN 
        (SELECT 0 num union ALL SELECT 10 num union ALL SELECT 20 num union ALL SELECT 30 num union ALL SELECT 40 num union ALL
        SELECT 50 num union ALL SELECT 60 num union ALL SELECT 70 num union ALL SELECT 80 num union ALL SELECT 90 num ) tens
        CROSS JOIN
        (SELECT 0 num union ALL SELECT 100 num union ALL SELECT 200 num union ALL SELECT 300 num) hundreds
        where date_add('${startDt}',
        interval(ones.num+tens.num+hundreds.num)DAY)<='{$this->endDt}'
        ORDER BY 1 DESC) d";
        $aDate = $this->oDB->getAll($sSql); 
        foreach ($aDate as $key => &$value) {
            $value = array_merge($this->getInitData(),$value);  //按时间初始化数据
        }
        if(!empty($aDate)) {
            $this->mergeData = $aDate;
        } else {
            $this->_errMsg ="时间查询出错";    
        }
        return $this;
    }
    /**
     * desc 获取初始化数据
     * @author rhovin 2017-09-21
     */
    private function getInitData() {
        return $this->initData ;
    }

    /**
     * desc 统计用户人工存提金额
     * @author rhovin 2017-09-25
     */
    public function getManuaInfo($data,$iCurrPage = 1,$iPageRecords = 30,$sOrderBy = '') {
        if(!empty($data)) {
            $sWhere = 1;
            $sWhere .= " AND DATE_FORMAT(mc.`finishtime` ,'%Y-%m-%d')='${data['dateKey']}'";
            $sWhere .=" AND mc.`optype`=${data['optype']}";
             if(in_array($data['order_type'], [4,5,6,7])) {
                $sWhere .= " AND mc.`order_type` in(4,5,6,7)" ;
            } else{
                $sWhere .=" AND mc.`order_type`=${data['order_type']}";
            }
            $sWhere .=" AND mc.`isconfirm`=1   AND mc.`lvtopid`={$this->iLvtopId} ";
        } 
        $sTableName = "manualpay_confirm mc LEFT JOIN usertree ut ON ut.`userid`=mc.`user_ids`";
        $sFields="ut.`username`,DATE_FORMAT(mc.`finishtime` ,'%Y-%m-%d') AS dateKey,ut.`userid`, round(SUM(mc.`amount`),4) AS manuapay_money";
        $sCountSql="SELECT COUNT(DISTINCT(ut.`userid`)) AS TOMCOUNT FROM $sTableName WHERE $sWhere";
        $result = $this->oDB->getPageResult($sTableName, $sFields, $sWhere, $iPageRecords, $iCurrPage, $sOrderBy='GROUP BY mc.`user_ids`', '', $sCountSql);
        return $result;
    }
    /**
     * desc 统计用户人工存提金额明细列表
     * @author rhovin 2017-09-26
     */
    public function getManuaInfoList($data,$iCurrPage = 1,$iPageRecords = 30,$sOrderBy = '') {
        if(!empty($data)) {
            $sWhere = 1;
            $sWhere .= " AND DATE_FORMAT(mc.`finishtime` ,'%Y-%m-%d')='${data['dateKey']}'";
            $sWhere .=" AND mc.`optype`=${data['optype']}";
            if(in_array($data['order_type'], [4,5,6,7])) {
                $sWhere .= " AND mc.`order_type` in(4,5,6,7)" ;
            } else{
                $sWhere .=" AND mc.`order_type`=${data['order_type']}";
            }
            $sWhere .=" AND mc.`user_ids`=${data['userid']}";
            $sWhere .=" AND mc.`isconfirm`=1   AND mc.`lvtopid`={$this->iLvtopId} ";
        } 
        $sTableName = "manualpay_confirm mc LEFT JOIN usertree ut ON ut.`userid`=mc.`user_ids`";
        $sFields="ut.`username`,ut.`lvproxyid`,ut.`parentid`,mc.`confirmadmin`,mc.`finishtime`,ut.`userid`, mc.`confirm_remark`,round(mc.`amount`,4) AS manuapay_money";
        $sCountSql='';
        $result = $this->oDB->getPageResult($sTableName, $sFields, $sWhere, $iPageRecords, $iCurrPage, $sOrderBy, '', $sCountSql);
        return $result;
    }
    /**
     * desc 统计用户取款资金统计列表
     * @author rhovin 2017-09-26
     */
    public function getUserWithdraw($sWhere = 1,$iCurrPage = 1,$iPageRecords = 30,$sOrderBy = '') {
        $aFastpayCompany = $this->oDB->getAll("SELECT * FROM `fastpay_company`");
        foreach ($aFastpayCompany as $v) {
            $aFastPayMay[$v['id']] = $v['cnname'];
        }
        $sWhere = $sWhere." AND uw.`lvtopid`={$this->iLvtopId}";
        $sTableName = "user_withdraw uw LEFT JOIN usertree ut ON ut.`userid`=uw.`userid` LEFT JOIN proxy_fastpay_acc AS pfa ON(uw.fastpayid = pfa.id)";
        $sFields="ut.`username`,pfa.companyid,DATE_FORMAT(uw.`finishtime` ,'%Y-%m-%d') AS dateKey,ut.`userid`,round(SUM(uw.`admin_fee`),4) AS admin_fee,round(SUM(uw.`charge`),4) AS charge, round(SUM(uw.`real_amount`),4) AS withdraw_money,uw.withdraw_type";
        $sCountSql="SELECT COUNT(DISTINCT(uw.`userid`)) AS TOMCOUNT FROM $sTableName WHERE $sWhere";
        $result = $this->oDB->getPageResult($sTableName, $sFields, $sWhere, $iPageRecords, $iCurrPage, $sOrderBy, '', $sCountSql);
        foreach ($result['results'] as $k => &$v) {
            if (!empty($v['companyid'])) {
                $v['fastName'] = $aFastPayMay[$v['companyid']];
            }
        }
        return $result;
    }
    /**
     * desc 统计用户取款金额明细列表
     * @author rhovin 2017-09-26
     */
    public function getWithdrawList($sWhere = 1,$iCurrPage = 1,$iPageRecords = 30,$sOrderBy = '') {
        $sWhere =$sWhere." AND uw.`status`=2  AND uw.`lvtopid`={$this->iLvtopId} ";
        $sTableName = "user_withdraw uw LEFT JOIN usertree ut ON ut.`userid`=uw.`userid`";      
        $sFields="ut.`username`,ut.`lvproxyid`,ut.`parentid`,uw.`admin_fee`,uw.`charge`,uw.`admin_remark`,uw.`user_remark`,uw.`adminname`,uw.`finishtime` AS dateKey,ut.`userid`, round(uw.`real_amount`,4) AS withdraw_money";
        $sCountSql='';
        $result = $this->oDB->getPageResult($sTableName, $sFields, $sWhere, $iPageRecords, $iCurrPage, $sOrderBy, '', $sCountSql);
        return $result;
    }
    /**
     * desc 统计某一天用户提款手续费和行政费
     * @author rhovin 2017-09-27
     */
    public function getUserWithdrawFeeCount($dateKey)
    {
        $sSql1 = "SELECT ROUND(if(SUM(`admin_fee`)>0,SUM(`admin_fee`),0),2) AS admin_fee, COUNT(DISTINCT(`userid`)) AS adminfee_user FROM user_withdraw 
            WHERE DATE_FORMAT(`finishtime` ,'%Y-%m-%d')='${dateKey}' AND `status`=2 AND `admin_fee`>0 AND `lvtopid`={$this->iLvtopId}";
        $sSql2 = "SELECT COUNT(DISTINCT userid) AS charge_user ,ROUND(if(SUM(`charge`)>0,SUM(`charge`),0),2) AS charge FROM user_withdraw 
            WHERE DATE_FORMAT(`finishtime` ,'%Y-%m-%d')='${dateKey}' AND `status`=2 AND `charge`>0 AND `lvtopid`={$this->iLvtopId}";
        $data1 = $this->oDB->getOne($sSql1);
        $data2 = $this->oDB->getOne($sSql2);
        return array_merge($data1, $data2);
    }
    /**
     * desc获取公司入款分类数据
     * @author pierce
     * @date 2017-09-25
     * @param $sWhere
     * @return array
     */
    public function getCompanyPayment($sWhere) {
        $aCompanyDeposit = array(
            "bank" => array(
                "user_count" => 0,
                "apply_amount" => 0.00
            ),
            "wechat" => array(
                "user_count" => 0,
                "apply_amount" => 0.00
            ),
            "alipay" => array(
                "user_count" => 0,
                "apply_amount" => 0.00
            ),
            "qqpay" => array(
                "user_count" => 0,
                "apply_amount" => 0.00
            )
        );
        //银行卡入款
        $aBankPayment = $this->oDB->getOne("SELECT COUNT(DISTINCT userid) AS user_count, SUM(`apply_amount`) AS `apply_amount` FROM user_deposit_company WHERE $sWhere AND `bankid` < '100'");
        //非银行卡入款101是微信，102是支付宝
        $aOtherPay = $this->oDB->getAll("SELECT bankid, COUNT(DISTINCT userid) AS user_count, SUM(`apply_amount`) AS `apply_amount` FROM user_deposit_company WHERE $sWhere AND `bankid` > '100' GROUP BY bankid");
        if (!empty($aOtherPay)){
            foreach ($aOtherPay as $k => $v) {
                $aTemp[$v['bankid']] = $v;
            }
            $aCompanyDeposit['wechat']['user_count'] = $aTemp[101]['user_count'] ? $aTemp[101]['user_count'] : 0;
            $aCompanyDeposit['wechat']['apply_amount'] = $aTemp[101]['apply_amount'] ? $aTemp[101]['apply_amount'] : 0.0000;
            $aCompanyDeposit['alipay']['user_count'] = $aTemp[102]['user_count'] ? $aTemp[102]['user_count'] : 0;
            $aCompanyDeposit['alipay']['apply_amount'] = $aTemp[102]['apply_amount'] ? $aTemp[102]['apply_amount'] : 0.0000;
            $aCompanyDeposit['qqpay']['user_count'] = $aTemp[103]['user_count'] ? $aTemp[103]['user_count'] : 0;
            $aCompanyDeposit['qqpay']['apply_amount'] = $aTemp[103]['apply_amount'] ? $aTemp[103]['apply_amount'] : 0.0000;
        }

        if (!empty($aBankPayment)) {
            $aCompanyDeposit['bank']['user_count'] = $aBankPayment['user_count'] ? $aBankPayment['user_count'] : 0;
            $aCompanyDeposit['bank']['apply_amount'] = $aBankPayment['apply_amount'] ? $aBankPayment['apply_amount'] :0.0000;
        }
        return $aCompanyDeposit;
    }

    /**
     * @desc 获取公司入款分类信息
     * @author pierce
     * @date 2017-09-25
     * @param $sWhere
     * @return array
     */
    public function getCompanyCategory($sWhere) {
        $aBankPayment = $this->oDB->getAll("SELECT udc.`company_payacc_id`, bi.`bankname`, ppa.`nickname`, ppa.`payee`,SUM(udc.apply_amount) AS apply_amount FROM user_deposit_company AS udc LEFT JOIN proxy_pay_acc AS ppa ON(udc.company_payacc_id = ppa.id) LEFT JOIN bankinfo AS bi ON(udc.bankid = bi.bankid) WHERE $sWhere GROUP BY udc.company_payacc_id");
        return $aBankPayment;
    }

    /**
     * @desc 根据入款账号id获取入款记录
     * @author pierce
     * @date 2017-09-26
     * @param $sWhere
     * @return array
     */
    public function getPaymentListByPayAccId($sWhere) {
        return $this->oDB->getAll("SELECT userid,username,SUM(apply_amount) AS apply_amount FROM user_deposit_company WHERE $sWhere GROUP BY userid");
    }

    /**
     * @desc 根据入款账号id获取账号信息
     * @author pierce
     * @date 2017-09-26
     * @param $iPayAccId
     * @param $iLvtopid
     * @return array
     */
    public function getBankInfoByPayAccId($iPayAccId,$iLvtopid) {
        $aBankInfo = $this->oDB->getOne("SELECT bi.bankname,ppa.* FROM proxy_pay_acc AS ppa LEFT JOIN bankinfo AS bi ON(ppa.bankid = bi.bankid) WHERE id = '{$iPayAccId}' AND lvtopid = '{$iLvtopid}'");
        return $aBankInfo;
    }

    /**
     * @desc 第三方入款
     * @author pierce
     * @date 2017-09-26
     * @param $sWhere
     * @return array
     */
    public function getFastpayPayment($sWhere) {
        $aFastpayPayment = array();
        $aDetail = $this->oDB->getAll("SELECT udf.company_fastpayacc_id, udf.paytypeid,pfa.nickname, COUNT(DISTINCT udf.userid) AS usercount,SUM(udf.apply_amount) AS apply_amount FROM user_deposit_fastpay AS udf LEFT JOIN proxy_fastpay_acc AS pfa ON(udf.company_fastpayacc_id = pfa.id) WHERE $sWhere GROUP BY udf.company_fastpayacc_id,udf.paytypeid");
        $aTotal = $this->oDB->getAll("SELECT udf.company_fastpayacc_id, fc.cnname AS nickname, COUNT(DISTINCT udf.userid) AS usercount, SUM(udf.apply_amount) AS apply_amount FROM user_deposit_fastpay AS udf LEFT JOIN proxy_fastpay_acc AS pfa ON(udf.company_fastpayacc_id = pfa.id) LEFT JOIN fastpay_company AS fc ON (pfa.companyid = fc.id) WHERE $sWhere GROUP BY udf.company_fastpayacc_id");
        foreach  ($aDetail as $k=>$v) {
            if(isset($v['company_fastpayacc_id'])) {
                $aFastpayPayment[$v['company_fastpayacc_id']][]=$v;
            }
        }
        $aFastpayPayment[0] = $aTotal;
        return $aFastpayPayment;
    }

    /**
     * @desc 根据第三方公司获取入款记录
     * @author pierce
     * @date 2017-09-26
     * @param $sWhere
     * @return array
     */
    public function getFastpayPaymentList($sWhere) {
        return $this->oDB->getAll("SELECT username,SUM(apply_amount) AS apply_amount FROM user_deposit_fastpay WHERE $sWhere GROUP BY userid");
    }

    /**
     * @desc 给予优惠
     * @author pierce
     * @date 2017-09-26
     * @param $sWhere
     * @return array
     */
    public function getDiscount($sWhere) {
        $aDiscount = array(
            "company" => array(
                "user_count" => 0,
                "discount" => 0.00
            ),
            "fastpay" => array(
                "user_count" => 0,
                "discount" => 0.00
            ),
            "deposit" => array(
                "user_count" => 0,
                "discount" => 0.00
            ),
            "activity" => array(
                "user_count" => 0,
                "discount" => 0.00
            )
        );
        //公司入款优惠
        $aCompany = $this->oDB->getOne("SELECT COUNT(DISTINCT userid) AS user_count, SUM(favor_amount) AS discount FROM user_deposit_company WHERE $sWhere AND `status` = 2 AND favor_amount > 0");
        //三方入款优惠
        $aFastpay = $this->oDB->getOne("SELECT COUNT(DISTINCT userid) AS user_count, SUM(favor_amount) AS discount FROM user_deposit_fastpay WHERE $sWhere AND `status` IN (2,4) AND favor_amount > 0");
        //存款存入优惠
        $aDeposit = $this->oDB->getOne("SELECT COUNT(DISTINCT user_ids) AS user_count, SUM(ext_amount) AS discount FROM manualpay_confirm WHERE $sWhere AND optype = 0 AND order_type IN (0,1) AND ext_amount > 0 AND isconfirm = 1");
        //优惠活动优惠
        $aActivity = $this->oDB->getOne("SELECT COUNT(DISTINCT user_ids) AS user_count, SUM(amount) AS discount FROM manualpay_confirm WHERE $sWhere and optype = 0 AND amount > 0 AND order_type = 3 AND isconfirm = 1");
        if (!empty($aCompany) && $aCompany['discount'] != 0) {
            $aDiscount['company']['user_count'] = $aCompany['user_count'];
            $aDiscount['company']['discount'] = $aCompany['discount'];
        }
        if (!empty($aFastpay) && $aFastpay['discount'] != 0) {
            $aDiscount['fastpay']['user_count'] = $aFastpay['user_count'];
            $aDiscount['fastpay']['discount'] = $aFastpay['discount'];
        }
        if (!empty($aDeposit) && $aDeposit['discount'] != 0) {
            $aDiscount['deposit']['user_count'] = $aDeposit['user_count'];
            $aDiscount['deposit']['discount'] = $aDeposit['discount'];
        }
        if (!empty($aActivity) && $aActivity['discount'] != 0) {
            $aDiscount['activity']['user_count'] = $aActivity['user_count'];
            $aDiscount['activity']['discount'] = $aActivity['discount'];
        }
        return $aDiscount;
    }

    /**
     * 获取公司入款优惠
     * @param $sWhere
     * @return array
     */
    public function getCompanyDiscount($sWhere) {
        return $this->oDB->getAll("SELECT username, SUM(favor_amount) AS apply_amount FROM user_deposit_company WHERE $sWhere AND `status` = 2 AND favor_amount > 0 GROUP BY userid");
    }

    /**
     * 获取三方入款优惠
     * @param $sWhere
     * @return array
     */
    public function getFastpayDiscount($sWhere) {
        return $this->oDB->getAll("SELECT username, SUM(favor_amount) AS apply_amount FROM user_deposit_fastpay WHERE $sWhere AND `status` IN (2,4) AND favor_amount > 0 GROUP BY userid");
    }

    /**
     * 获取存款存入优惠
     * @param $sWhere
     * @return array
     */
    public function getDepositDiscount($sWhere) {
        return $this->oDB->getAll("SELECT usernames as username, SUM(ext_amount) AS apply_amount FROM manualpay_confirm WHERE $sWhere AND optype = 0 AND order_type IN (0,1) AND ext_amount > 0 AND isconfirm = 1 GROUP BY user_ids");
    }

    /**
     * 获取活动优惠
     * @param $sWhere
     * @return array
     */
    public function getActivityDiscount($sWhere) {
        return $this->oDB->getAll("SELECT usernames as username, SUM(amount) AS apply_amount FROM manualpay_confirm WHERE $sWhere and optype = 0 AND amount > 0 AND order_type = 3 AND isconfirm = 1 GROUP BY user_ids");
    }
}
