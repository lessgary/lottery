<?php

/**
 * 文件 : /_app/controller/rotationconfig.php
 * 功能 : 控制器 - 浮动窗口
 *
 * @author    Ben
 * @version   1.0.0
 * @package   proxyweb
 *
 */
class controller_floatwindowconfig extends pcontroller {
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
     * 添加浮动窗口
     * @author Ben
     * @date 2017-06-24
     */
    public function actionAdd() {
        $oProxyhomeimage = new model_proxyhomeimage();
        if ($this->getIsPost()) {
            $aData = $this->post([
                'title' => parent::VAR_TYPE_STR,
                'link' => parent::VAR_TYPE_STR,
                'link_type' => parent::VAR_TYPE_INT,
                'sort' => parent::VAR_TYPE_INT,
                'status' => parent::VAR_TYPE_INT,
                'position' => parent::VAR_TYPE_INT
            ]);
            $aData['lvtopid'] = $this->lvtopid;
            $aData['admin_name'] = $this->adminname;
            $aData['admin_id'] = $this->loginProxyId;
            $aData['type'] = model_proxyhomeimage::$TYPE[1];

            // 电脑图片
            if (!empty($_FILES) && isset($_FILES['pc_image']) && empty($_FILES['pc_image']['error'])) {
                a::loadFile('filefunc.php', A_DIR . DS . 'includes' . DS . 'plugin');
                $filePath = DS . 'upload' . DS . getUploadPath();
                $aResult = saveUploadFile(
                    $_FILES['pc_image'],
                    $this->getPassportPath() . $filePath,
                    'image',
                    [
                        'gif',
                        'jpeg',
                        'jpg',
                        'png'
                    ],
                    0,
                    16777216 // 1 byte = 8 bit，
                );
                if (is_array($aResult)) {
                    if (!empty($aResult['err_msg']) && 0 > $aResult['code']) {
                        $this->error($aResult['err_msg']);
                    } else {
                        // 图片上传成功，记录图片路径
                        $aData['pc_image'] = $filePath . DS . basename($aResult['name']);
                    }
                } else {
                    $this->error('Sorry, 图片上传错误');
                }
            }

            if ($oProxyhomeimage->add($aData)) {
                // 删除缓存
                /* @var $oMemCache memcachedb */
                $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
                $oMemCache->delete('ad_' . $this->lvtopid . '_' . model_proxyhomeimage::$TYPE[1]);

                sysMessage('添加成功');
            } else {
                $this->error('添加失败，请联系管理员！');
            }
        } else {
            $this->error('非法请求！');
        }
    }

    /**
     * 修改轮播
     * @author Ben
     * @date 2017-06-23
     */
    public function actionEdit() {
        if ($this->getIsPost()) {
            /* @var $oProxyhomeimage model_proxyhomeimage */
            $oProxyhomeimage = a::singleton('model_proxyhomeimage');
            $aData = $this->post([
                'title' => parent::VAR_TYPE_STR,
                'link' => parent::VAR_TYPE_STR,
                'link_type' => parent::VAR_TYPE_INT,
                'sort' => parent::VAR_TYPE_INT,
                'status' => parent::VAR_TYPE_INT,
                'image_id' => parent::VAR_TYPE_INT,
                'position' => parent::VAR_TYPE_INT
            ]);
            $aData['lvtopid'] = $this->lvtopid;
            $aData['admin_name'] = $this->adminname;
            $aData['admin_id'] = $this->loginProxyId;
            $aData['type'] = model_proxyhomeimage::$TYPE[1];

            // 电脑图片
            if (!empty($_FILES) && isset($_FILES['pc_image']) && empty($_FILES['pc_image']['error'])) {
                a::loadFile('filefunc.php', A_DIR . DS . 'includes' . DS . 'plugin');
                $filePath = DS . 'upload' . DS . getUploadPath();
                $aResult = saveUploadFile(
                    $_FILES['pc_image'],
                    $this->getPassportPath() . $filePath,
                    'image',
                    [
                        'gif',
                        'jpeg',
                        'jpg',
                        'png'
                    ],
                    0,
                    16777216 // 1 byte = 8 bit，
                );
                if (is_array($aResult)) {
                    if (!empty($aResult['err_msg']) && 0 > $aResult['code']) {
                        $this->error($aResult['err_msg']);
                    } else {
                        // 图片上传成功，记录图片路径
                        $aData['pc_image'] = $filePath . DS . basename($aResult['name']);
                        // 获取旧图片信息
                        $aInfo = $oProxyhomeimage->getByImageId($this->lvtopid, $_POST['qrid']);
                    }
                } else {
                    $this->error('Sorry, 图片上传错误');
                }
            }

            if (false !== $oProxyhomeimage->edit($aData, "`image_id`='${aData['image_id']}' AND `lvtopid`='${aData['lvtopid']}'  AND `type`='${aData['type']}'")) {
                // 删除旧图片
                if (!empty($aData['pc_image']) && isset($aInfo) && !empty($aInfo['pc_image'])) {
                    @unlink($this->getPassportPath() . $aInfo['pc_image']);
                }

                // 删除缓存
                /* @var $oMemCache memcachedb */
                $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
                $oMemCache->delete('ad_' . $this->lvtopid . '_' . model_proxyhomeimage::$TYPE[1]);

                sysMessage('修改成功');
            } else {
                $this->error('修改失败，请联系管理员！');
            }
        } else {
            $this->error('非法请求！');
        }
    }

    /**
     * 获取浮动窗口列表
     * @author Ben
     * @dat 2017-06-24
     */
    public function actionList(){
        if ($this->getIsPost()) {
            $aCondition = $this->post([
                'page' => parent::VAR_TYPE_INT,
                'rows' => parent::VAR_TYPE_INT,
                'status' => parent::VAR_TYPE_INT,
                'position' => parent::VAR_TYPE_INT
            ]);
            if (!isset($_POST['status'])) {
                $aCondition['status'] = -1;
            }
            if (!isset($_POST['position'])) {
                $aCondition['position'] = -1;
            }

            /* @var $oProxyhomeimage model_proxyhomeimage */
            $oProxyhomeimage = a::singleton('model_proxyhomeimage');
            $aList = $oProxyhomeimage->getFloatWindowList($this->lvtopid, $aCondition['status'], $aCondition['position'], $aCondition['rows'], $aCondition['page']);
            if (!empty($aList) && !empty($aCondition)) {
                $this->outPutJQgruidJson($aList['results'], $aList['affects'], $aCondition['page'], $aCondition['rows']);
            }
        } else {
            $this->oViewer->display('floatwindowconfig_list.html');
        }
    }

    /**
     * 删除浮动窗口
     * @author Ben
     * @date 2017-06-24
     */
    public function actionDelete() {
        if ($this->getIsAjax() && $this->getIsPost() && !empty($_POST['image_id']) && is_numeric($_POST['image_id'])) {
            /* @var $oProxyhomeimage model_proxyhomeimage */
            $oProxyhomeimage = a::singleton('model_proxyhomeimage');
            $iLvtopid = $this->lvtopid;
            $iImageId = intval($_POST['image_id']);
            if (false !== $oProxyhomeimage->edit(
                    ['status' => 2, 'admin_name' => $this->adminname, 'admin_id' => $this->loginProxyId],
                    "lvtopid='${iLvtopid}' and image_id='${iImageId}'")) {
                /* @var $oMemCache memcachedb */
                $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
                $oMemCache->delete('ad_' . $this->lvtopid . '_' . model_proxyhomeimage::$TYPE[1]);
                die(json_encode(['result' => 1, 'msg' => '删除成功！']));
            } else {
                die(json_encode(['result' => 0, 'msg' => '操作失败，请联系管理员！']));
            }
        } else {
            $this->error('非法请求');
        }
    }
}