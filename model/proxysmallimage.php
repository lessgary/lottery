<?php

/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2017/8/12
 * Time: 9:23
 */
class model_proxysmallimage extends basemodel {
    public static $SMLOGO = '460X200@';
    /**
     * 获取商户小图标
     * @author Ben
     * @date 2017-08-12
     * @param int $iLvtopid
     * @param bool $isMobile
     * @return array|bool
     */
    public function getImages($iLvtopid, $isMobile = false) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid)) {
            return false;
        }
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $result = $oMemCache->getOne("logo_" . $iLvtopid);
        if (empty($result)) {
            $result = $this->oDB->getOne("SELECT `logo`, `icon`,`slogo` FROM `proxy_small_image` WHERE lvtopid='${iLvtopid}'");
            if ($this->oDB->errno > 0) {
                return false;
            }

            $oMemCache->insert("logo_" . $iLvtopid, $result, 600);
        }

        if ($isMobile && !empty($result)) {
            $sBaseName = basename($result['logo']);
            $result['logo'] = str_replace($sBaseName, self::$SMLOGO . $sBaseName, $result['logo']);
        }

        return $result;
    }

    /**
     * 设置商户小图标
     * @author Ben
     * @date 2017-08-12
     * @param $iLvtopid
     * @param $aData
     * @return bool|int|mixed
     */
    public function setImages($iLvtopid, $aData) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid) || empty($aData) || !is_array($aData)) {
            return false;
        }

        $aResult = $this->oDB->getOne("SELECT logo,icon,slogo FROM `proxy_small_image` WHERE `lvtopid` ='${iLvtopid}' limit 1");

        if (false === $aResult) {
            return false;
        }

        if (!empty($aData['logo'])) {
            $aData['logo'] = $this->oDB->real_escape_string($aData['logo']);
        }
        if (!empty($aData['icon'])) {
            $aData['icon'] = $this->oDB->real_escape_string($aData['icon']);
        }
        if (!empty($aData['slogo'])) {
            $aData['slogo'] = $this->oDB->real_escape_string($aData['slogo']);
        }

        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        if ($aResult) {
            $bResult = $this->oDB->update('proxy_small_image', $aData, "`lvtopid` ='${iLvtopid}'");
            $aOld = $aResult;
            if ($bResult) {
                // 删除旧图片
                if (!empty($aData['logo'])) {
                    @unlink(getPassportPath() . $aOld['logo']);
                    $sBaseName = basename($aOld['logo']);
                    $path = str_replace($sBaseName, self::$SMLOGO . $sBaseName, $aOld['logo']);
                    @unlink(getPassportPath() . $path);
                }
                if (!empty($aData['icon'])) {
                    @unlink(getPassportPath() . $aOld['icon']);
                }
                if (!empty($aData['slogo'])) {
                    @unlink(getPassportPath() . $aOld['slogo']);
                }

                // 添加缓存
                $oMemCache->delete('logo_' . $iLvtopid);
            }
            return $bResult;
        } else {
            $bResult = $this->oDB->insert('proxy_small_image', $aData);
            // 添加缓存
            if ($bResult) {
                $oMemCache->delete('logo_' . $iLvtopid);
            }
            return $bResult;
        }
    }
    /**
     * 清除图片
     * @author rhovin 201709017
     */
    public function clearImages($iLvtopid , $sImgKey='') {
        $images = $this->getImages($iLvtopid);
        $aData[$sImgKey] = '';
        $bResult = $this->oDB->update('proxy_small_image', $aData, "`lvtopid` ='${iLvtopid}'");
        if($bResult) {
            if($images[$sImgKey] != '') {
                @unlink(getPassportPath() . $images[$sImgKey]);
            }
            $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
            $oMemCache->delete('logo_' . $iLvtopid);
            return true;
        } else {
            return false;
        }
    }
}