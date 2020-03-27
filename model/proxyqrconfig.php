<?php

/**
 * @desc 商户首页二维码设定
 * @author Ben
 * @date 2017-06-20
 *
 */
class model_proxyqrconfig extends basemodel {

    /**
     * 获取商户首页二维码列表
     * @author Ben
     * @date 2017-06-20
     * @param $iLvTopId
     * @return mixed
     */
    public function getConfigList($iLvTopId) {
        if (empty($iLvTopId) || !is_numeric($iLvTopId)) {
            return false;
        }

        $aReturn = $this->oDB->getAll("SELECT * FROM `proxyqrconfig` WHERE `lvtopid`='${iLvTopId}' LIMIT 2");
        if ($aReturn) {
            foreach ($aReturn as &$item) {
                $item['path'] = getImageLoadUrl() . $item['path'];
            }
        }

        return $aReturn;
    }

    /**
     * 获取单条记录信息
     * @author Ben
     * @date 2017-06-21
     * @param $iQrid
     * @param $iLvTopId
     * @return array|bool
     */
    public function getConfigById($iQrid, $iLvTopId) {
        if (empty($iQrid) || !is_numeric($iQrid)) {
            return false;
        }

        return $this->oDB->getOne("SELECT `qrid`,`path` FROM `proxyqrconfig` WHERE `qrid`='${iQrid}' AND `lvtopid` ='${iLvTopId}' LIMIT 1");
    }

    /**
     * 更新配置
     * @author Ben
     * @data 2017-06-21
     * @param $aData
     * @param $sCondition
     * @return mixed
     */
    public function updateConfig($aData, $sCondition) {
        if (!empty($aData['path'])) {
            $aData['path'] = str_replace(getImageLoadUrl(), '', trim($aData['path']));
            $aData['path'] = $this->oDB->real_escape_string($aData['path']);
        }

        return $this->oDB->update('proxyqrconfig', $aData, $sCondition);
    }

    /**
     * 获取商户首页二维码列表
     * @author left
     * @date 2017-07-30
     * @param   int   $iLvTopId     商户id
     * @return array                商户二维码图片
     */
    public function getQrcodeList($iLvTopId) {

        $iLvTopId = intval($iLvTopId);

        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $aResult = $oMemCache->getOne("qrcode_" . $iLvTopId);

        if (empty($aResult)) {
            $aResult = $this->oDB->getAll("SELECT * FROM `proxyqrconfig` WHERE `lvtopid`='${iLvTopId}' LIMIT 2");
            if ($this->oDB->errno > 0) {
                return array();
            }
            $oMemCache->insert("qrcode_" . $iLvTopId, $aResult, 60*60*24);
        }

        return $aResult;
    }
}
