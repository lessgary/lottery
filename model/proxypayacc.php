<?php

/**
 * @desc 商户支付账号管理
 * @author Ben
 * @date 2017-06-30
 *
 */
class model_proxypayacc extends basemodel {

    /**
     * 添加数据
     * @author Ben
     * @date 2017-06-30
     * @param $aData
     * @return bool|mixed
     */
    public function add($aData) {
        if (!empty($aData['img_path'])) {
            $aData['img_path'] = $this->oDB->real_escape_string($aData['img_path']);
        }

        return $this->oDB->insert('proxy_pay_acc', $aData);
    }

    /**
     * 更新数据
     * @author Ben
     * @date 2017-06-30
     * @param $iLvtopid
     * @param $iId
     * @param $aData
     * @return mixed
     */
    public function edit($iLvtopid, $iId, $aData) {
        if (!empty($aData['img_path'])) {
            $aData['img_path'] = $this->oDB->real_escape_string($aData['img_path']);
        }

        $sCondition = "`id` = '${iId}' AND  `lvtopid` = '${iLvtopid}'";
        return $this->oDB->update('proxy_pay_acc', $aData, $sCondition);
    }

    /**
     * 获取列表
     * @param $iLvtopid
     * @param $iStatus
     * @param $iRows
     * @param $iPage
     * @return array
     */
    public function getList($iLvtopid, $iStatus = -1, $iRows = null, $iPage = null) {
        $sCondition = " AND `lvtopid` = '${iLvtopid}'";
        if (-1 !== $iStatus) {
            $sCondition .= " AND `status` = '${iStatus}'";
        }
        if (empty($iRows) || empty($iPage)) {
            $aResult = $this->oDB->getAll("SELECT * FROM `proxy_pay_acc` WHERE `status` >0  ${sCondition}");
            if ($aResult && is_array($aResult)) {
                foreach ($aResult as &$item) {
                    if (!empty($item['user_layerids']) || '0' === $item['user_layerids']) {
                        $item['user_layerids_array'] = explode(',', $item['user_layerids']);
                    } else {
                        $item['user_layerids_array'] = [];
                    }
                    $item['isnote'] = empty($item['isnote']) ? 'N' : 'Y';
                    if (!empty($item['img_path'])) {
                        $item['img_path'] = getImageLoadUrl() . $item['img_path'];
                    }
                }
            }
            return $aResult;
        } else {
            $aResult = $this->oDB->getPageResult('proxy_pay_acc' , "*" , "`status` >0  ${sCondition}", $iRows, $iPage,
                $sOrderby='', $sUseIndex = '', $sCountSql='');

            if (isset($aResult['results']) && is_array($aResult['results'])) {
                foreach ($aResult['results'] as &$item) {
                    if (!empty($item['user_layerids']) || '0' === $item['user_layerids']) {
                        $item['user_layerids_array'] = explode(',', $item['user_layerids']);
                    } else {
                        $item['user_layerids_array'] = [];
                    }
                    $item['isnote'] = empty($item['isnote']) ? 'N' : 'Y';
                    if (!empty($item['img_path'])) {
                        $item['img_path'] = getImageLoadUrl() . $item['img_path'];
                    }
                }
            }
            return $aResult;
        }
    }

    /**
     * 获取单条信息
     * @param $iLvtopid
     * @param $iId
     * @return array|bool
     */
    public function getInfo($iLvtopid, $iId) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid) || empty($iId) || !is_numeric($iId)) {
            return false;
        }
        $aResult = $this->oDB->getOne("SELECT * FROM `proxy_pay_acc` WHERE `id` = '${iId}' AND `lvtopid` = '${iLvtopid}' AND `status` >0 LIMIT 1");
        if ($aResult && is_array($aResult)) {
            $aResult['user_layerids_array'] = !empty($aResult['user_layerids']) || '0' === $aResult['user_layerids'] ? explode(',', $aResult['user_layerids']) : [];
        }
        return $aResult;
    }

    /**
     * 检验数据唯一性
     * @author Ben
     * @date 2107-07-13
     * @param $iLvtopid
     * @param $sCondition
     * @return bool|int
     */
    public function checkUnique($iLvtopid, $sCondition) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid) || empty($sCondition)) {
            return false;
        }

        $aResult = $this->oDB->getOne("SELECT COUNT(1) as count FROM `proxy_pay_acc` WHERE `lvtopid`='${iLvtopid}' AND ${sCondition} LIMIT 1");

        if (is_array($aResult) && isset($aResult['count'])) {
            return intval($aResult['count']);
        }
        return false;
    }

    /**
     * 获取用户层级可以用的公司入款账号
     * @param $iLvtopid
     * @param $iLayerid
     * @return array|bool
     */
    public function getUserCompanyAcc($iLvtopid, $iLayerid) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid) || !is_numeric($iLayerid)) {
            return false;
        }
        return $this->oDB->getAll("SELECT ppc.*, bi.bankname,bi.nickname AS iconname FROM `proxy_pay_acc` AS ppc LEFT JOIN `bankinfo` AS bi ON (ppc.bankid = bi.bankid) WHERE `lvtopid`='${iLvtopid}' AND FIND_IN_SET('${iLayerid}', `user_layerids`) AND `status` = 1");
    }
}