<?php

/**
 * 支付公司
 * @desc
 * @author Ben
 * @date 2017-06-17
 *
 */
class model_fastpaycompany extends basemodel {

    /**
     * 获取列表
     * @author Ben
     * @date 2017-06-26
     */
    public function getList() {
        return $this->oDB->getAll("SELECT * FROM `fastpay_company`");
    }

    /**
     * 获取三方公司的支付类型
     * @author Ben
     * @date 2017-08-31
     */
    public function getFastPayType() {
        $aCompany = $this->oDB->getAll("SELECT * FROM `fastpay_company`");
        if (empty($aCompany)) {
            return [];
        }
        $aReturn = [];
        foreach ($aCompany as $item) {
            $sApiName = "model_newfastpay_${item['enname']}";
            foreach (glob(PDIR_USER_MODEL . 'newfastpay' . DS . '*.php') as $filename) {
                include_once $filename;
            }
            if (class_exists($sApiName)) {
                /* @var $oTmp model_newfastpay_base */
                $oTmp = a::singleton($sApiName);
                $aReturn[$item['id']] = $oTmp->getSelectOption();
            }
        }
        return $aReturn;
    }

    /**
     * 获取三方公司的支付类型
     * @author Ben
     * @date 2017-08-31
     */
    public function getFastPayTypeCnanme() {
        $aCompany = $this->oDB->getAll("SELECT * FROM `fastpay_company`");
        if (empty($aCompany)) {
            return [];
        }
        $aReturn = [];
        foreach ($aCompany as $item) {
            $sApiName = "model_newfastpay_${item['enname']}";
            foreach (glob(PDIR_USER_MODEL . 'newfastpay' . DS . '*.php') as $filename) {
                include_once $filename;
            }
            if (class_exists($sApiName)) {
                /* @var $oTmp model_newfastpay_base */
                $oTmp = a::singleton($sApiName);
                $aReturn[$item['cnname']] = $oTmp->getSelectOption(1);
            }
        }
        return $aReturn;
    }
     /**
     * desc 获取30秒内是否有三方充值用户
     * @author rhovin 2017-10-30
     */
    public function getCountFastByTime($iLvtopid) {
        $sStart = date("Y-m-d H:i:s",time()-60);
        $sSql = "SELECT MAX(id) AS `count_fastpay` FROM `user_deposit_fastpay` WHERE `lvtopid` = '{$iLvtopid}' AND `inserttime` >= '{$sStart}'";
        return $this->getDB()->getOne($sSql);
    }
     /**
     * desc 获取30秒内是否有公司入款用户
     * @author rhovin 2017-10-30
     */
    public function getCountFastCompanyByTime($iLvtopid) {
       // $sStart = date("Y-m-d H:i:s",time()-60);
        $sSql = "SELECT MAX(id) AS `count_companypay` FROM `user_deposit_company` WHERE  `lvtopid` = '{$iLvtopid}' AND `status` =0";
        return  $this->getDB()->getOne($sSql);
    }
}