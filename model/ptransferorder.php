<?php
/**
 * Created by PhpStorm.
 * User: paul
 * Date: 2018/5/23
 * Time: 16:57
 */
class model_ptransferorder extends basemodel {
    //表名
    private $tableName = "transfer_order";

    //表字段
    private $sFileds = 'id,userid,lvtopid,username,transfer_order_number,preavailable,amount,availablebalance,vendor_preavailable,vendor_availablebalance,vendor_id,type,status,insert_time,update_time,remark';

    /**
     * 构造函数
     *
     * @access  public
     * @return  void
     */
    function __construct($aDBO = array()) {
        parent::__construct($aDBO);
    }


    /**根据条件组装SQL
     * @param $aWhere
     *
     * @return string
     *
     */
    private function setWhere($aWhere){
        $sWhere = '1';
        if(is_array($aWhere) && count($aWhere)>0){
            $sWhere .= isset($aWhere['lvtopid']) ? ' AND `lvtopid` = '.$aWhere['lvtopid'] : '';
            if(isset($aWhere['type']) && in_array($aWhere['type'],[0,1])){//根据转账类型筛选
                $sWhere .= " AND `type`=".$aWhere['type'];
            }
            if(in_array($aWhere['status'],[0,1,2])){//根据转账状态筛选
                $sWhere .= " AND `status`=".$aWhere['status'];
            }

            if($aWhere['minAmount'] && $aWhere['minAmount']>0){//根据金额筛选
                $sWhere .= " AND `amount`> ".$aWhere['minAmount'];
            }

            if($aWhere['maxAmount'] && $aWhere['maxAmount']>0){//根据金额筛选
                $sWhere .= " AND `amount`< ".$aWhere['maxAmount'];
            }

            if($aWhere['starttime']){//根据时间筛选
                $sWhere .= " AND `insert_time`>'".$aWhere['starttime']."'";
                $sWhere .= $aWhere['endtime'] ? " AND `insert_time`<'".$aWhere['endtime']."'":'';
            }


            if($aWhere['transfer_order_number']){//订单号查询
                $sWhere .= " AND `transfer_order_number`='".$aWhere['transfer_order_number']."'";
            }

            if($aWhere['userid']){//用户ID查询
                $sWhere .= " AND `userid`='".$aWhere['userid']."'";
            }



            if($aWhere['searchwords'] && in_array($aWhere['searchtype'],[1,2])){//根据用户名或者订单号筛选
                $searchWords = $aWhere['searchwords'];
                $sWhere .= $aWhere['searchtype']==1? " AND `username`='$searchWords'" : " AND `transfer_order_number`='$searchWords'";
            }
        }
        return $sWhere;
    }


    /**
     * 查出所有转账记录
     * @param string $lvtopid
     * @param string $sFiled
     * @param string $sWhere
     *
     * @param int    $iPageRecord 每页条数
     * @param        $iCurrentPage 页码
     * @return array|bool
     */
    public function getAllTransfer($sFileds = '*' , $aWhere = [] ,$sOrderBy='', $iCurrentPage = 0, $iPageRecord = 0){
        $sFileds = $sFileds == '*' ? $this->sFileds : $sFileds;
        $sWhere = $this->setWhere($aWhere);
        if (!empty($sOrderBy)) {
            $sOrderBy = " ORDER BY " . $sOrderBy;
        }
        $iCurrentPage = is_numeric($iCurrentPage) ? intval($iCurrentPage) : 0;
        if ($iPageRecord == 0) {
            return $this->oDB->getAll("SELECT " . $sFileds . " FROM " . $this->tableName . " WHERE " . $sWhere);
        }
        return $this->oDB->getPageResult($this->tableName, $sFileds, $sWhere,  $iPageRecord, $iCurrentPage, $sOrderBy);
    }






}