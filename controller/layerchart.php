<?php

/**
 * 文件 : /_app/controller/layerchart.php
 * 功能 : 控制器 - 层级启动状态图
 *
 * @author    Ben
 * @version   1.0.0
 * @package   proxyweb
 *
 */
class controller_layerchart extends pcontroller {
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
     *
     */
    public function actionIndex() {

        $iStatus = isset($_GET['status']) ? intval($_GET['status']) : 1;

        $this->oViewer->assign('tips', $iStatus == 1 ? '启用中' : '停用中');

        // 获取用户层级
        /* @var $oUserLayer model_userlayer */
        $oUserLayer = a::singleton('model_userlayer');
        $aLayerList = $oUserLayer->getLayerList($this->lvtopid);
        $this->oViewer->assign('aLayerList', $aLayerList);

        // 获取三方列表
        /* @var $oTripartitle model_proxyfastpayacc */
        $oTripartitle = a::singleton('model_proxyfastpayacc');
        $aFastPayAccList = $oTripartitle->getAllList($this->lvtopid, $iStatus);
        $aList = $this->handleData($aFastPayAccList, 'paytypeid');
        $this->oViewer->assign('aList', $aList);

        // 支付类型列表
        /* @var $oFastPayType model_fastpaytype */
        $oFastPayType = a::singleton('model_fastpaytype');
        $aPayType = $oFastPayType->getList();
        $this->oViewer->assign('aPayType', $aPayType);

        // 获取银行列表
        /* @var $oBankInfo model_bankinfo */
        $oBankInfo = a::singleton('model_bankinfo');
        $aBankList = $oBankInfo->getList();
        $this->oViewer->assign('aBankList', $aBankList);

        // 入款账号列表
        /* @var $oProxyPayAcc model_proxypayacc */
        $oProxyPayAcc = a::singleton('model_proxypayacc');
        $accList = $oProxyPayAcc->getList($this->lvtopid, $iStatus);
        $accList = $this->handleData($accList, 'bankid');
        $this->oViewer->assign('accList', $accList);

        $this->oViewer->display('layerchart_index.html');
    }

    /**
     * 组装数组
     * @author Ben
     * @date 2017-07-02
     * @param $aData
     * @param $sKey
     * @return array
     */
    public function handleData($aData, $sKey) {
        $aReturn = [];
        foreach ($aData as $item) {
            if (!empty($item[$sKey])) {
                $aReturn[$item[$sKey]][] = $item;
            }
        }
        return $aReturn;
    }
}
