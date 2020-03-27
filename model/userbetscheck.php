<?php
/**
 * @desc 用户层级管理
 * @author rhovin
 * @date 2017-07-05
 *
 */
class model_userbetscheck extends basemodel
{
    public $id;
    /** * @var 总代id */
    public $lvtopid;

    /** * @var 用户id */
    public $userid;

    /** * @var 插入时间 */
    public $inserttime;

    /** * @var 存款金额 */
    public $amount;

    /** * @var 额外金额(优惠金额) */
    public $ext_amount;

    /** * @var 彩票有效投注 */
    public $betsvalue;

    /** * @var 投注流水达标线(综合打码量) */
    public $ext_bets;

    /** * @var 综合打码量是否达标 */
    public $exttrue;

    /** * @var 充值投注流水达标线 (常态打码量) */
    public $need_bets;

    /** * @var (充值流水)常态打码量放宽标准 */
    public $reduce_bets;

    /** * @var (充值流水)常态打码量是否达标 */
    public $diposittrue;

    /** * @var 常态打码量是否有扣除费用 */
    public $reducetrue;

    /** * @var 额外优惠扣除金额 */
    public $ext_reduce = 0;

    /** * @var 充值流水未达标扣除金额 */
    public $needbets_reduce = 0;

    /** * @var 总扣除金额 */
    public $reduceamount = 0;

    /** * @var 有效投注是否达标 */
    public $betsStatus = 0;

    public function __construct($arr=[]){
        parent::__construct();
        if(!empty($arr)) {
            foreach ($arr as $key => $value) {
                $this->$key = $value;
            }
        }
    }
    /**
     * desc 获取即时稽核存款列表
     * @author rhovin
     * @date 2017-07-05
     */
    public function getBetsCheckList($lvtopid,$userid, $sAndWhere = '') {
        if($userid==0) return [];
        $sSql = "SELECT * FROM user_bets_check WHERE lvtopid='".$lvtopid."' AND userid='".$userid."' ${sAndWhere} ORDER BY inserttime ASC";
        return $this->oDB->getAll($sSql);
    }
    /**
     * desc 综合打码量相关 私有
     * @author rhovin 2017-07-06
     * @param iTotalbets //总流水
     * @param ext_bets //综合打码量
     * @return betsvalue //彩票有效投注
     */
    private function setBetsValue($iTotalbets , $ext_bets) {
        if($iTotalbets >= $ext_bets || $ext_bets == 0) {
            $this->betsvalue = $ext_bets;
            $this->setExttrue(true);
            $this->ext_reduce = 0;    
        } else {
            $this->betsvalue = $iTotalbets < 0 ? 0 : $iTotalbets;
            $this->setExttrue(false);
            $this->ext_reduce = $this->ext_amount; 
        }
    }
    /**
     * desc 常态打码量相关  私有
     * @author rhovin 2017-07-06
     * @param iTotalbets //总流水
     * @param need_bets //充值投注流水达标线 (常态打码量)
     *
     */
    private function setNeedBesValue($iTotalbets , $need_bets) {
        if($iTotalbets >= $need_bets || $need_bets == 0) {
            $this->setDiposittrue(true);
            $this->setReducetrue(true);
            $this->needbets_reduce = 0 ;
        } else {
            if($iTotalbets >= ($need_bets-$this->reduce_bets)) {
                $this->setDiposittrue(true);
                $this->setReducetrue(true);
            } else {
                $oPaySet = new model_payset();
                $xzFee = $oPaySet->getBetsChargeRate($this->userid , $this->lvtopid);
                $this->needbets_reduce = bcmul($this->amount,$xzFee,2);
                $this->setDiposittrue(false);
                $this->setReducetrue(false);
            }
        }
    }
    /**
     * desc 即时稽核表运算 可外部调用运算
     * @author rhovin 2017-07-06
     * @param iTotalbets //总流水
     * @param ext_bets //综合打码量
     * @param need_bets //常态打码量 (充值流水)
     */    
    public function setUserBets($iTotalbets , $ext_bets ,$need_bets) {
        if($iTotalbets >= max($ext_bets,$need_bets) ) {
            $this->setDiposittrue(true);
            $this->setReducetrue(true);
            $this->setExttrue(true);
            $this->betsvalue = max($ext_bets,$need_bets);    
        } else {
            $this->setBetsValue($iTotalbets , $ext_bets);
            $this->setNeedBesValue($iTotalbets , $need_bets);
        }         
    }

    /**
     * desc 查找所有存款打码情况(外部调用) 需展示稽核数据调用此方法即可
     * @author rhovin 2017-07-07
     * @param lvtopid 总代Id
     * @param userid 用户Id 
     * @param sAndWhere ex:'and is_clear=1'  where 条件
     * @return (array)aResults
     */
    public function findAll($userid , $username, $iTotalbets , $lvtopid, $sAndWhere='',$extend = []) {
        $aResults = $this->getBetsCheckList($lvtopid , $userid , $sAndWhere);
        $totalbets = $iTotalbets;
        $total_ext_reduce = 0;
        $total_needbets_reduce =    0;
        $total_reduce = 0;
        foreach ($aResults as $key => &$value) {
            $oUserBet = $this->findModel($value['id'] , $value);
            $oUserBet->setUserBets($iTotalbets , $oUserBet->ext_bets, $oUserBet->need_bets);
            $value['betsvalue'] = $oUserBet->betsvalue; //彩票有效投注
            $value['exttrue'] = $this->getStatus($oUserBet->exttrue); //综合打码量是否达标
            $value['diposittrue'] = $this->getStatus($oUserBet->diposittrue); //常态打码量是否达标
            $value['reducetrue'] = $this->getStatus($oUserBet->reducetrue); //常态打码量是否有扣除费用
            $value['reduceamount'] = $oUserBet->getReduceAmount(); //需扣减金额
            $total_ext_reduce += $oUserBet->ext_reduce; //优惠总扣除
            $total_needbets_reduce += $oUserBet->needbets_reduce; //常态未达标总扣除
            $iTotalbets -= max($oUserBet->ext_bets, $oUserBet->need_bets);
        }
        foreach ($aResults as $key => &$value) {
            $value['username'] = $username;
            $value['total_ext_reduce'] = $total_ext_reduce;
            $value['total_needbets_reduce'] = $total_needbets_reduce;
            $value['total_reduce'] = $total_ext_reduce + $total_needbets_reduce; //优惠扣除+行政费用扣除
            $value['totalbets'] = $totalbets;
        }
        return $aResults;
    }

    /**
     * desc 单条模型实例
     * @author rhovin 2017-07-06
     * @return obj
     */
    public function findModel($id, $arr = []) {
        if(!empty($arr) && $arr['id'] == $id){
            $aResults = $arr ;
        } else {
            $sSql = "SELECT * FROM user_bets_check WHERE id='".intval($id)."' AND lvtopid='".$this->lvtopid."'";
            $aResults = $this->oDB->getOne($sSql);
        }
        if(!empty($aResults)) {
            foreach ($aResults as $key => $value) {
                $this->$key = $value;    
            }
        } else {
            return false;
        }
        return $this;
    }

    /**
     * desc 扣减金额
     * @author rhovin
     * @return int
     */
    public function getReduceAmount() {
        //优惠扣减加上充值扣减
        return $this->ext_reduce + $this->needbets_reduce;
    }

    /**
     * desc 清空打码量
     * @author rhovin
     * @date 2017-07-06
     */
    public function flushBets($id) {
        $sSql = "UPDATE user_bets_check set `ext_bets`='".$this->ext_bets."', `need_bets`='".$this->need_bets."' WHERE id='".$id."'";
        $this->oDB->query($sSql);
        if ($this->oDB->errno() > 0) { // 修改失败
            return FALSE;
        }else {
            return TRUE ;
        }
    }

    /**
     * desc 综合打码量是否达标
     * @author rhovin 2017-07-07
     * @param bool
     */
    private function setExttrue($bool = false) {
        $this->setBetsStatus($bool);
        $this->exttrue =$bool ;
    }

    /**
     * desc (充值流水)常态打码量是否达标
     * @author rhovin 2017-07-07
     * @param bool
     * 
     */
    private function setDiposittrue($bool = false) {
        $this->setBetsStatus($bool);
        $this->diposittrue = $bool ;
    }

    /**
     * desc 常态打码量是否有扣除费用
     * @author rhovin 2017-07-07
     * @return bool 
     */
    private function setReducetrue($bool = false) {
        $this->setBetsStatus($bool);
        $this->reducetrue = $bool ;
    }

    /**
     * desc 获取是否通过的字符
     * @author rhovin 2017-07-07
     * @param bool 
     * @return yes or no 
     */
    public function getStatus($bool = false, $yes='√', $no='x') {
        return $value = ($bool == true ? $yes : $no );
    }
    /**
     * desc 总得有效投注是否达标
     * @author rhovin 2017-10-27  0、达标 1、未达标 
     */
    public function setBetsStatus($bool) {
        if($this->betsStatus == 0) {
            if($bool == false) {
                $this->betsStatus = 1;
            }
        }
    }

}
