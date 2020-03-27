<?php

/**
 * 文件 : /_app/controller/information.php
 * 功能 : 控制器 - 资讯中心
 *
 * @author    Ben
 * @version   1.0.0
 * @package   proxyweb
 *
 */
class controller_information extends pcontroller {
    /**
     * 视图对象
     * @var $oViewer view
     */
    private $oViewer;

    public function __construct() {
        parent::__construct();
        $this->oViewer = $GLOBALS['oView'];
    }

    /**
     * 资讯列表
     * @author Ben
     * @date 2017-06-21
     */
    public function actionList() {
        if ($this->getIsPost()) {
            $aCondition = $this->post([
                'rows' => parent::VAR_TYPE_INT,
                'page' => parent::VAR_TYPE_INT,
                'iStatus' => parent::VAR_TYPE_INT,
                'iType' => parent::VAR_TYPE_INT
            ]);
            if (!isset($_POST['iStatus'])) {
                $aCondition['iStatus'] = -1;
            }
            if (!isset($_POST['iType'])) {
                $aCondition['iType'] = -1;
            }

            // 列表
            /* @var $oPoxyInformation model_proxyinformation */
            $oPoxyInformation = a::singleton('model_proxyinformation');
            $aList = $oPoxyInformation->getList($this->lvtopid, $aCondition['iStatus'], $aCondition['iType'], $aCondition['rows'], $aCondition['page']);
            if (!empty($aList) && !empty($aCondition)) {
                $this->outPutJQgruidJson($aList['results'], $aList['affects'], $aCondition['page'], $aCondition['rows']);
            }
        } else {
            $this->oViewer->display('information_list.html');
        }
    }

    /**
     * 添加资讯
     * @author Ben
     * @date 2017-06-22
     */
    public function actionAdd() {
        if ($this->getIsPost()) {
            $aData = $this->post([
                'title' => parent::VAR_TYPE_STR,
                'sort' => parent::VAR_TYPE_INT,
                'type' => parent::VAR_TYPE_INT,
                'content' => parent::VAR_TYPE_STR,
                'status' => parent::VAR_TYPE_INT
            ]);
            $aData['lvtopid'] = $this->lvtopid;
            $aData['lastuser'] = $this->adminname;
            $aData['admin_id'] = $this->loginProxyId;
            $oInformation = new model_proxyinformation();
            if ($oInformation->add($aData)) {
                sysMessage('添加成功');
            } else {
                $this->error('保存失败');
            }
        } else {
            $this->error('非法请求！');
        }
    }

    /**
     * 修改资讯
     * @author Ben
     * @date 2016-06-22
     */
    public function actionEdit() {
        if ($this->getIsAjax() && isset($_GET['id'])) {
            /* @var $oPoxyInformation model_proxyinformation */
            $oPoxyInformation = a::singleton('model_proxyinformation');
            $aInfo = $oPoxyInformation->getOne($this->lvtopid, intval($_GET['id']));
            if ($aInfo) {
                die(json_encode(['error' => 0, 'data' => $aInfo]));
            } else {
                die(json_encode(['error' => 1]));
            }
        }
        if ($this->getIsPost()) {
            /* @var $oPoxyInformation model_proxyinformation */
            $oPoxyInformation = a::singleton('model_proxyinformation');
            if (isset($_POST['change_sticky']) && $this->getIsAjax()) {
                // 修改置顶
                if (false !== $oPoxyInformation->changeStatus($this->lvtopid, intval($_POST['informationid']), $this->loginProxyId, $this->adminname)) {
                    die(json_encode(['result' => 1, 'msg' => '修改成功！']));
                } else {
                    die(json_encode(['result' => 0, 'msg' => '修改失败，请联系管理员']));
                }
            } else {
                $aData = $this->post([
                    'title' => parent::VAR_TYPE_STR,
                    'sort' => parent::VAR_TYPE_INT,
                    'type' => parent::VAR_TYPE_INT,
                    'content' => parent::VAR_TYPE_STR,
                    'informationid' => parent::VAR_TYPE_INT,
                    'status' => parent::VAR_TYPE_INT
                ]);
                $aData['lvtopid'] = $this->lvtopid;
                $aData['lastuser'] = $this->adminname;
                $aData['admin_id'] = $this->loginProxyId;
                $iLvtopid = $this->lvtopid;
                if (false !== $oPoxyInformation->edit($aData, "lvtopid='${iLvtopid}' and informationid='${aData['informationid']}'")) {
                    sysMessage('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        } else {
            $this->error('非法请求！');
        }
    }

    /**
     * 删除资讯
     * @author Ben
     * @date 2017-06-22
     */
    public function actionDelete() {
        if ($this->getIsAjax() && $this->getIsPost() && !empty($_POST['informationid']) && is_numeric($_POST['informationid'])) {
            /* @var $oPoxyInformation model_proxyinformation */
            $oPoxyInformation = a::singleton('model_proxyinformation');
            $iLvtopid = $this->lvtopid;
            $iInformationid = intval($_POST['informationid']);
            if (false !== $oPoxyInformation->edit(
                    ['status' => 2, 'lastuser' => $this->adminname, 'admin_id' => $this->loginProxyId],
                    "lvtopid='${iLvtopid}' and informationid='${iInformationid}'")
            ) {
                die(json_encode(['result' => 1, 'msg' => '删除成功！']));
            } else {
                die(json_encode(['result' => 0, 'msg' => '操作失败，请联系管理员！']));
            }
        } else {
            $this->error('非法请求');
        }
    }
    /**
     * desc 公司简介
     * @author rhovin 2017-11-24
     */
    public function actionCompanyList() {
        $information_model = new model_proxyinformation();
        $aData = $information_model->getCompanyDes($this->lvtopid,false);
        $this->oViewer->assign('data', $aData);
        $this->oViewer->display('information_companylist.html');
    }
    /**
     * desc 添加公司简介
     * @author rhovin 2017-11-24
     */
    public function actionCompanyEdit() {
        $information_model = new model_proxyinformation();
        $aData = $information_model->getCompanyDes($this->lvtopid);
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "status" => parent::VAR_TYPE_INT,
                "showtype" => parent::VAR_TYPE_INT,
                "content" => parent::VAR_TYPE_STR,
                "link" => parent::VAR_TYPE_STR,
            ));
            $aGetData['lvtopid'] = $this->lvtopid;
            if(empty($aData)) {
               $aResult = $information_model->addCompanyDes($aGetData);
               if($aResult){
                    $this->ajaxMsg(1,"保存成功");
                }else{
                    $this->ajaxMsg(1,"保存失败");
                }
            } else {
                $aResult = $information_model->editCompanyDes($aGetData,"lvtopid=$this->lvtopid");
                if($aResult){
                    $this->ajaxMsg(1,"保存成功");
                }else{
                    $this->ajaxMsg(1,"保存失败");
                }
            }
        }
        $this->oViewer->assign('data', $aData);
        $this->oViewer->display('information_companyedit.html');
    }
}