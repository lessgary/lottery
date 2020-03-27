<?php

/**
 * 文件 : /_app/controller/qrconfig.php
 * 功能 : 控制器 - 二维码控制器
 *
 * @author    Ben
 * @version   1.0.0
 * @package   proxyweb
 *
 */
class controller_qrconfig extends pcontroller {
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
     * @date 2017-06-20
     */
    public function actionList() {
        /* @var $oProxyQRConfig model_proxyqrconfig */
        $oProxyQRConfig = a::singleton('model_proxyqrconfig');

        $aList = $oProxyQRConfig->getConfigList($this->lvtopid);
        $this->oViewer->assign('list', empty($aList) ? [] : $aList);
        $this->oViewer->display('qrconfig_list.html');
    }

    /**
     * 编辑
     * @author Ben
     * @date 2017-06-20
     */
    public function actionEdit() {
        if (!$this->getIsPost() || empty($_POST['qrid']) || !is_numeric($_POST['qrid'])) {
            $this->error('没有找到相应的记录');
        }
        /* @var $oProxyQRConfig model_proxyqrconfig */
        $oProxyQRConfig = a::singleton('model_proxyqrconfig');

        // 获取参数
        $aData = $this->post([
            'qrid' => parent::VAR_TYPE_INT,
            'link' => parent::VAR_TYPE_STR,
            'link_type' => parent::VAR_TYPE_INT
        ]);

        // 上传文件
        if (!empty($_FILES) && !empty($_FILES['upload']) && empty($_FILES['upload']['error'])) {
            a::loadFile('filefunc.php', A_DIR . DS . 'includes' . DS . 'plugin');
            $sFilePath = DS . 'upload' . DS . getUploadPath();
            $aResult = saveUploadFile(
                $_FILES['upload'],
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
                    $aData['path'] = $sFilePath . DS . basename($aResult['name']);
                    // 获取旧图片信息
                    $aInfo = $oProxyQRConfig->getConfigById($_POST['qrid'], $this->lvtopid);
                }
            } else {
                $this->error('Sorry, 图片上传错误');
            }
        }

        // 更新数据库记录
        if (false !== $oProxyQRConfig->updateConfig($aData, "`qrid`='${aData['qrid']}' AND `lvtopid`=" . $this->lvtopid)) {
            // 删除旧图片
            if (!empty($aData['path']) && isset($aInfo) && !empty($aInfo['path'])) {
                @unlink($this->getPassportPath() . $aInfo['path']);
            }

            // 删除缓存
            /* @var $oMemCache memcachedb */
            $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
            $oMemCache->delete('qrcode_' . $this->lvtopid);
            sysMessage('保存成功！', 0);
        } else {
            $this->error('Sorry,保存失败，请联系管理员');
        }
    }
}