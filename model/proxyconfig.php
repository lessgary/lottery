<?php

/**
 * @desc 商户配置
 * @author Ben
 * @date 2017-06-17
 *
 */
class model_proxyconfig extends basemodel {
    private $sCacheKey = 'proxyconfig_';

    public static function option (){
        return [
            'yes' => '是',
            'no'  => '否',
            'realname' => '真实姓名',
            'mobile' => '手机号码',
            'qq' => 'QQ',
            'email' => '邮件',
            'proxy' => '代理',
            'member' => '会员',
            'quick' => '快速充值',
            'fastpay' => '快捷支付',
            'companypay' => '公司入款',
            'online' => '网银支付',
            'other' => '其他支付',
            'wechatpay' => '微信',
            'alipay' => '支付宝',
            'qqpay' => 'QQ钱包',
            'invitation' => '邀请码',
            'auto' => '自动',
            'manual' => '手动'
        ];
    }



    /**
     * 获取系统配置
     * @author Ben
     * @date 2017-06-19
     * @param $iLvTopId
     * @return array|bool
     */
    public function getSystemConfig($iLvTopId) {
        if (empty($iLvTopId) || !is_numeric($iLvTopId)) {
            return false;
        }
        $sSql = "SELECT * FROM `proxyconfig` WHERE `lvtopid` ='${iLvTopId}' AND `isdisabled` =0 AND `settype` = 0";
        $aData = $this->oDB->getAll($sSql);

        if (!empty($aData)) {
            foreach ($aData as &$item) {
                if (!empty($item['configvalue'])) {
                    $item['configvalue'] = stripslashes($item['configvalue']);
                }
            }

            return $this->getTree($aData);
        }
        return [];
    }

    /**
     * 获取非顶级菜单
     * @author Ben
     * @date 2017-06-19
     * @param $iLvTopId
     * @return array|bool
     */
    public function getSystemConfigChild($iLvTopId) {
        if (empty($iLvTopId) || !is_numeric($iLvTopId)) {
            return false;
        }
        $sSql = "SELECT * FROM `proxyconfig` WHERE `lvtopid` ='${iLvTopId}' AND `isdisabled` =0 AND `settype` = 0 AND `parentid` > 0";
        $aData = $this->oDB->getAll($sSql);

        return $aData ?: [];
    }

    /**
     * 更新用户配置表记录
     * @author Ben
     * @date 2017-06-19
     * @param $iLvTopId
     * @param $aList
     * @param $aData
     * @return bool
     */
    public function setSystemConfig($iLvTopId, $aList, $aData) {
        if (empty($iLvTopId) || !is_numeric($iLvTopId)) {
            return false;
        }

        // 开启事务
        $this->oDB->doTransaction();
        try {
            foreach ($aList as $item) {
                if (!empty($item['configvalue'])) {
                    $item['configvalue'] = $this->oDB->real_escape_string(trim($item['configvalue']));
                }
                $sKey = 'config_' . $item['configid'];
                if (array_key_exists($sKey, $aData)) {
                    $sValue = is_array($aData[$sKey]) ? trim(implode('|', $aData[$sKey])) : trim($aData[$sKey]);
                    // 数据持久化
                    $result = $this->oDB->update(
                        'proxyconfig',
                        [
                            'configvalue' => $this->oDB->real_escape_string($sValue)
                        ],
                        "`configid`='${item['configid']}' AND `lvtopid`='${iLvTopId}'"
                    );
                } else {
                    // 数据持久化
                    $result = $this->oDB->update(
                        'proxyconfig',
                        [
                            'configvalue' => ''
                        ],
                        "`configid`='${item['configid']}' AND `lvtopid`='${iLvTopId}'"
                    );
                }
                if (false  === $result) {
                    throw new Exception();
                }
            }
            $this->oDB->doCommit();
            // 清理缓存
            /* @var $oMemCache memcachedb */
            $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
            $oMemCache->delete($this->sCacheKey . $iLvTopId);
            return true;
        } catch (Exception $oException) {
            // 回滚，返回失败
            $this->oDB->doRollback();
            return false;
        }
    }

    /**
     * 组装层级
     * @author Ben
     * @date 2017-06-19
     * @param $aData
     * @param int $iParent
     * @return array
     */
    private function getTree($aData, $iParent = 0) {
        $return = [];
        if (!empty($aData)) {
            foreach ($aData as $k => $v) {
                if ($iParent == $v['parentid']) {
                    unset($aData[$k]);
                    $v['son'] = $this->getTree($aData, $v['configid']);
                    $return[] = $v;
                }
            }
        }
        return $return;
    }
    /**
     * desc 获取配置项
     * @author rhovin 2017-07-10
     * @param mixed $keys
     * @return array
     */
    function getConfigs($lvtopid, $mKeys, $bMore = FALSE) {
        $aConfigs = array();
        if (empty($mKeys)) {
            return $aConfigs;
        }

        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $aCached = $oMemCache->get($this->sCacheKey . $lvtopid);
        if (empty($aCached)) {
            $aConfig = $this->oDB->getAll("SELECT * FROM `proxyconfig` WHERE `lvtopid` ='${lvtopid}' AND `isdisabled` =0 AND `settype` = 0 AND `parentid` > 0");
            $oMemCache->set($this->sCacheKey . $lvtopid, $aConfig, 0, 43200);
            $aCached = $aConfig;
        }
        $aConfig = [];
        foreach ($aCached as $item) {
            if (is_array($mKeys)) {
                if (in_array($item['configkey'], $mKeys)) {
                    $aConfig[] = $item;
                }
            } else {
                if ($mKeys === $item['configkey']) {
                    $aConfig = $item;
                    break;
                }
            }
        }

        if ($bMore === TRUE) {
            return $aConfig;
        }
        if (!empty($aConfig)) {
            if (is_array($mKeys)) {
                foreach ($aConfig as $value) {
                    if ($value['forminputtype'] == 'input') {
                        $aConfigs[$value["configkey"]] = ''!==$value['configvalue']&&!is_null($value["configvalue"]) ? $value["configvalue"] : $value['defaultvalue'];
                    } else {
                        $aConfigs[$value["configkey"]] = !empty($value['configvalue']) ? $value["configvalue"] : $value['defaultvalue'];
                    }
                }
            } else {
                if ($aConfig['forminputtype'] == 'input') {
                    $aConfigs = ''!==$aConfig['configvalue']&&!is_null($aConfig['configvalue']) ? $aConfig['configvalue'] : $aConfig['defaultvalue'];
                } else {
                    $aConfigs = !empty($aConfig['configvalue']) ? $aConfig['configvalue'] : $aConfig['defaultvalue'];
                }
            }
        }
        return $aConfigs;
    }
    
}