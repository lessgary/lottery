<?php

/**
 * 文件 : /_app/controller/activity.php
 * 功能 : 控制器 - 活动管理
 *
 * @author    ken 2017
 * @version   3.0.0
 * @package   passportadmin
 */
class controller_transferorder extends pcontroller {



    private function setArrayData($aArray){
        $aType=[0=>'主账户->KY棋牌',1=>'KY棋牌->主账户'];
        $aStatus=[0=>'未处理',1=>'失败',2=>'成功'];
        foreach ($aArray as $k=>&$v){
            $v['type'] = $aType[$v['type']];
            $v['status_type'] = $v['status'];
            $v['status'] = $aStatus[$v['status']];
            $v['preavailable'] = sprintf("%.2f", $v['preavailable']);;
            $v['availablebalance'] = sprintf("%.2f", $v['availablebalance']);

        }
        return $aArray;
    }


    /**
     * 获取转账记录
     *
     */
    public function actionList()
    {
        /* @var $oModel model_ptransferorder */
        $oModel = A::singleton("model_ptransferorder");
        $aDataArray=array(
            "page" => parent::VAR_TYPE_INT,
            "rows" => parent::VAR_TYPE_INT,
            "sidx" => parent::VAR_TYPE_STR,
            "sord" => parent::VAR_TYPE_STR,
            "searchtype" => parent::VAR_TYPE_INT,
            "searchwords" => parent::VAR_TYPE_STR,
            "starttime" => parent::VAR_TYPE_DATETIME,
            "endtime" => parent::VAR_TYPE_DATETIME,
            "minAmount" => parent::VAR_TYPE_FLOAT,
            "maxAmount" => parent::VAR_TYPE_FLOAT,
            "type" => parent::VAR_TYPE_INT,
            "status" => parent::VAR_TYPE_INT,
        );
        $aGetData = $this->post($aDataArray);
        $aGetData['starttime'] = !empty($aGetData['starttime']) ? $aGetData['starttime'] : date('Y-m-d H:i:s',strtotime(date('Y-m-d',time())));
        $aGetData['endtime'] = !empty($aGetData['endtime']) ? $aGetData['endtime'] : date('Y-m-d H:i:s',strtotime(date('Y-m-d',time()+86400)));
        if ($this->getIsPost()) {
            $page = isset($_POST['page']) ? intval($_POST['page']) : 0 ;//页码
            $rows = isset($_POST['rows']) ? intval($_POST['rows']) : 0 ;//每页条数
            $aGetData['lvtopid'] = $_SESSION['lvtopid'];//只查询当前总代
            $sOrderBy = $aGetData['sidx'] == '' ? " ID DESC" :"${aGetData['sidx']} ${aGetData['sord']}";//排序
            $result = $oModel->getAllTransfer('*', $aGetData, $sOrderBy, $page, $rows);
            $result['results'] = $this->setArrayData($result['results']);
            $this->outPutJQgruidJson($result['results'], $result['affects'], $page, $rows);
        }else{
            $GLOBALS['oView']->assign('getDataArray',$aGetData);
            $GLOBALS['oView']->display('transfer_order_list.html');
        }


    }

    
    
}

