<?php

/**
 * 文件 : /_app/controller/payaccount.php
 * 功能 : 控制器 - 入款账号管理
 *
 * @author    Ben
 * @version   1.0.0
 * @package   proxyweb
 *
 */
class controller_payaccount extends pcontroller {
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
     * 列表
     * @author Ben
     * @date 2017-06-27
     */
    public function actionList() {
        if ($this->getIsPost()) {
            if (isset($_POST['status'])) {
                $iStatus = intval($_POST['status']);
            } else {
                $iStatus = -1;
            }
            $iRows = isset($_POST['rows']) ? intval($_POST['rows']) : 20;
            $iPage = isset($_POST['page']) ? intval($_POST['page']) : 1;

            /* @var $oProxyPayAcc model_proxypayacc */
            $oProxyPayAcc = a::singleton('model_proxypayacc');
            $aList = $oProxyPayAcc->getList($this->lvtopid, $iStatus, $iRows, $iPage);
            if (false !== $aList) {
                $this->outPutJQgruidJson($aList['results'], $aList['affects'], $iPage, $iRows);
            }
        }

        // 获取银行列表
        /* @var $oBankInfo model_bankinfo */
        $oBankInfo = a::singleton('model_bankinfo');
        $aBankList = $oBankInfo->getList();
        $this->oViewer->assign('aBankList', $aBankList);

        // 用户层级列表
        $aLayer = $this->getLayerList();
        $this->oViewer->assign('layer_list', $aLayer);

        $this->oViewer->display('payaccount_list.html');
    }

    /**
     * 添加入款账号
     * @author Ben
     * @date 2017-06-29
     */
    public function actionAdd() {
        if ($this->getIsAjax() && isset($_GET['check_unique'])) {
            $this->checkUnique();
        }

        if ($this->getIsPost()) {
            $aData = $this->post([
                "bankid" => parent::VAR_TYPE_INT,
                "accout_no" => parent::VAR_TYPE_STR,
                "nickname" => parent::VAR_TYPE_STR,
                "is_show_qr_img" => parent::VAR_TYPE_INT,
                "isnote" => parent::VAR_TYPE_INT,
                "payee" => parent::VAR_TYPE_STR,
                "notice" => parent::VAR_TYPE_STR
            ]);
            $aData['lvtopid'] = $this->lvtopid;
            $aData['adminname'] = $this->adminname;
            $aData['adminid'] = $this->loginProxyId;
            // 层级
            if (!empty($_POST['user_layerids']) && is_array($_POST['user_layerids'])) {
                $aData['user_layerids'] = implode(',', $_POST['user_layerids']);
            }

            // 上传文件
            if (!empty($_FILES) && !empty($_FILES['img_path']) && empty($_FILES['img_path']['error'])) {
                a::loadFile('filefunc.php', A_DIR . DS . 'includes' . DS . 'plugin');
                $sFilePath = DS . 'upload' . DS . date('Ymd');
                $aResult = saveUploadFile(
                    $_FILES['img_path'],
                    $this->getPassportPath() . $sFilePath,
                    'image',
                    [
                        'gif',
                        'jpeg',
                        'jpg',
                        'png'
                    ],
                    0,
                    2097152 // 1024*1024*2
                );
                if (is_array($aResult)) {
                    if (!empty($aResult['err_msg']) && 0 > $aResult['code']) {
                        $this->error($aResult['err_msg']);
                    } else {
                        // 图片上传成功，记录图片路径
                        $aData['img_path'] = $sFilePath . DS . basename($aResult['name']);
                    }
                } else {
                    $this->error('Sorry, 图片上传错误');
                }
            }

            $aData['inserttime'] = date('Y-m-d H:i:s');
            $oPayacc = new model_proxypayacc();
            if (false !== $oPayacc->add($aData)) {
                die(json_encode(['error' => 0, 'msg' => '添加成功！']));
            } else {
                die(json_encode(['error' => 1, 'msg' => '添加失败！请联系管理员。']));
            }
        }
        // 获取银行列表
        /* @var $oBankInfo model_bankinfo */
        $oBankInfo = a::singleton('model_bankinfo');
        $aBankList = $oBankInfo->getList();
        $this->oViewer->assign('aBankList', $aBankList);

        // 用户层级列表
        $aLayer = $this->getLayerList();
        $this->oViewer->assign('layer_list', $aLayer);

        $this->oViewer->display('payaccount_form.html');
    }

    /**
     * 编辑入款账号
     * @author Ben
     * @date 2017-06-29
     */
    public function actionEdit() {
        if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
            $this->error('编辑的数据不存在！');
        }

        $iId = intval($_GET['id']);

        // 数据记录
        /* @var $oProxyPayAcc model_proxypayacc */
        $oProxyPayAcc = a::singleton('model_proxypayacc');
        $aInfo = $oProxyPayAcc->getInfo($this->lvtopid, $iId);
        if (empty($aInfo)) {
            $this->error('编辑的数据不存在！');
        }

        if ($this->getIsAjax()) {
            if (!empty($_GET['is_changeStatus'])) {
                // 修改状态
                $aData['status'] = 2 == $aInfo['status'] ? 1 : 2;
                if (false !== $oProxyPayAcc->edit($this->lvtopid, $iId, $aData)) {
                    die(json_encode(['error' => 0, 'msg' => '修改成功！']));
                } else {
                    die(json_encode(['error' => 1, 'msg' => '修改失败！请联系管理员。']));
                }
            }
            $aData = $this->post([
                "bankid" => parent::VAR_TYPE_INT,
                "accout_no" => parent::VAR_TYPE_STR,
                "nickname" => parent::VAR_TYPE_STR,
                "is_show_qr_img" => parent::VAR_TYPE_INT,
                "isnote" => parent::VAR_TYPE_INT,
                "payee" => parent::VAR_TYPE_STR,
                "notice" => parent::VAR_TYPE_STR,
            ]);
            $aData['lvtopid'] = $this->lvtopid;
            $aData['adminname'] = $this->adminname;
            $aData['adminid'] = $this->loginProxyId;
            // 层级
            if (!empty($_POST['user_layerids']) && is_array($_POST['user_layerids'])) {
                $aData['user_layerids'] = implode(',', $_POST['user_layerids']);
            }

            // 上传文件
            if (!empty($_FILES) && !empty($_FILES['img_path']) && empty($_FILES['img_path']['error'])) {
                a::loadFile('filefunc.php', A_DIR . DS . 'includes' . DS . 'plugin');
                $sFilePath = DS . 'upload' . DS . date('Ymd');
                $aResult = saveUploadFile(
                    $_FILES['img_path'],
                    $this->getPassportPath() . $sFilePath,
                    'image',
                    [
                        'gif',
                        'jpeg',
                        'jpg',
                        'png'
                    ],
                    0,
                    2097152 // 1024*1024*2
                );
                if (is_array($aResult)) {
                    if (!empty($aResult['err_msg']) && 0 > $aResult['code']) {
                        $this->error($aResult['err_msg']);
                    } else {
                        // 图片上传成功，记录图片路径
                        $aData['img_path'] = $sFilePath . DS . basename($aResult['name']);
                        // 获取旧图片
                        $sOldImage = $aInfo['img_path'];
                    }
                } else {
                    $this->error('Sorry, 图片上传错误');
                }
            }

            if (false !== $oProxyPayAcc->edit($this->lvtopid, $iId, $aData)) {
                // 删除旧图片
                if (!empty($sOldImage) && !empty($aData['img_path'])) {
                    @unlink($this->getPassportPath() . $aInfo['path']);
                }
                die(json_encode(['error' => 0, 'msg' => '修改成功！']));
            } else {
                die(json_encode(['error' => 1, 'msg' => '修改失败！请联系管理员。']));
            }
        }
        $this->oViewer->assign('aInfo', $aInfo);

        // 获取银行列表
        /* @var $oBankInfo model_bankinfo */
        $oBankInfo = a::singleton('model_bankinfo');
        $aBankList = $oBankInfo->getList();
        $this->oViewer->assign('aBankList', $aBankList);

        // 用户层级列表
        $aLayer = $this->getLayerList();
        $this->oViewer->assign('layer_list', $aLayer);

        $this->oViewer->assign('is_edit', 1);
        $this->oViewer->display('payaccount_form.html');
    }

    /**
     * 删除账号
     * @author Ben
     * @date 2017-06-30
     */
    public function actionDelete() {
        if ($this->getIsAjax() && $this->getIsPost() && !empty($_POST['id']) && is_numeric($_POST['id'])) {
            $iId = intval($_POST['id']);
            // 数据记录
            /* @var $oProxyPayAcc model_proxypayacc */
            $oProxyPayAcc = a::singleton('model_proxypayacc');
            $aInfo = $oProxyPayAcc->getInfo($this->lvtopid, $iId);
            if (empty($aInfo)) {
                $this->error('编辑的数据不存在！');
            }

            $aData = [
                'status' => 0,
                'adminname' => $this->adminname,
                'adminid' => $this->loginProxyId
            ];
            if (false !== $oProxyPayAcc->edit($this->lvtopid, $iId, $aData)) {
                die(json_encode(['error' => 0, 'msg' => '删除成功！']));
            } else {
                die(json_encode(['error' => 1, 'msg' => '操作失败，请联系管理员！']));
            }
        } else {
            $this->error('非法请求');
        }
    }

    /**
     * 获取用户层级
     * @author Ben
     * @date 2017-06-27
     * @return array
     */
    private function getLayerList() {
        /* @var $oUserLayer model_userlayer */
        $oUserLayer = a::singleton('model_userlayer');
        $aLayer = $oUserLayer->getLayerList($this->lvtopid);
        if ($aLayer) {
            $aReturn = [];
            foreach ($aLayer as $item) {
                $aReturn[$item['layerid']] = $item;
            }
        }
        return !empty($aReturn) ? $aReturn : [];
    }

    /**
     * 检查数据唯一性
     * @author Ben
     * @date 2017-07-13
     */
    private function checkUnique() {
        if ($this->getIsPost() && $this->getIsAjax()) {
            $aData = $this->post([
                'nickname' => parent::VAR_TYPE_STR,
                'accout_no' => parent::VAR_TYPE_STR,
                'id' => parent::VAR_TYPE_STR
            ]);
            /* @var $oProxyPayAcc model_proxypayacc */
            $oProxyPayAcc = a::singleton('model_proxypayacc');
            if (!empty($aData['nickname'])) {
                $sCondition = "nickname='${aData['nickname']}'";
            } else if (!empty($aData['accout_no'])) {
                $sCondition = "accout_no='${aData['accout_no']}'";
            } else {
                die(json_encode(['error' => -1, 'msg' => '非法请求！']));
            }

            $sCondition .= ' AND `status` > 0';
            if (!empty($aData['id'])) {
                $sCondition .= " AND `id` <> '${aData['id']}'";
            }
            $iResult = $oProxyPayAcc->checkUnique($this->lvtopid, $sCondition);
            if (false === $iResult) {
                die(json_encode(['error' => -1, 'msg' => '服务器忙，请稍后再试！']));
            }
            die(json_encode(['error' => $iResult > 0 ? 0 : 1, 'msg' => '请求成功!']));
        } else {
            die(json_encode(['error' => -1, 'msg' => '非法请求！']));
        }
    }
}