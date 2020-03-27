<?php

/**
 * @desc 银行信息
 * @author Ben
 * @date 2017-06-29
 *
 */
class model_bankinfo extends basemodel {
    public function getList() {
        return $this->oDB->getAll("SELECT `bankid`,`bankname` FROM `bankinfo`");
    }

    /**
     * 获取用户银行卡信息
     * @author pierce
     * @date 2017-07-12
     * @param $iUserid
     * @param $iLvtopid
     * @return array
     */
    public function getUserBankInfo($iUserid,$iLvtopid,$iUserBankId){
        return $this->oDB->getOne("SELECT u.username,ub.realname,bi.bankname,ub.branch,ub.cardno,u.remark FROM users AS u LEFT JOIN userbankinfo as ub ON(u.userid = ub.userid) LEFT JOIN bankinfo AS bi ON(ub.bankid = bi.bankid) WHERE u.userid ={$iUserid} AND u.lvtopid = {$iLvtopid} AND ub.entry = {$iUserBankId}");
    }

    /**
     * 根据用户id获取用户银行卡信息
     * @author pierce
     * @date 2017-07-18
     * @param $iUserid
     * @param $iLvtopid
     * @return array
     */
    public function getBankInfoByUserId($iUserid,$iUserbankcardid){
        return $this->oDB->getAll("SELECT * FROM userbankinfo WHERE userid ={$iUserid} AND entry = {$iUserbankcardid}");
    }
}