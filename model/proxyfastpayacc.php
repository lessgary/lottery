<?php

/**
 * @desc 商户三方支付账号管理
 * @author Ben
 * @date 2017-06-17
 *
 */
class model_proxyfastpayacc extends basemodel {

    /**
     * 状态
     * @var array
     */
    public static $status = [
        1 => '启用',
        2 => '停用'
    ];

    /**
     * 添加记录
     * @author Ben
     * @date 2017-06-26
     */
    public function add($aData) {
        return $this->oDB->insert('proxy_fastpay_acc', $aData);
    }

    /**
     * 修改
     * @author Ben
     * @date 2017-06-27
     * @param $aData
     * @param $sCondition
     * @return bool|int
     */
    public function edit($aData, $sCondition) {
        if (empty($sCondition)) {
            return false;
        }

        return $this->oDB->update('proxy_fastpay_acc', $aData, $sCondition);
    }

    /**
     * 获取列表
     * @param $iLvtopid
     * @param $iStatus
     * @param $iPayType
     * @return mixed
     */
    public function getList($sWhere, $iRows, $iPage) {
        $sTableName = '`proxy_fastpay_acc` `a` LEFT JOIN  `fastpay_type` `b` ON `a`.`paytypeid` = `b`.`id` LEFT JOIN `fastpay_company` `c` ON `a`.`companyid` = `c`.`id`';
        $sFields = '`a`.*,`b`.`name` as `pay_type_msg`,`c`.`cnname` as `pay_company_msg`,(`a`.`current_quota`-`a`.`quota`) as sorts';

        $aResult = $this->oDB->getPageResult($sTableName, $sFields, $sWhere, $iRows, $iPage, 'ORDER BY `sorts` DESC');
        if ($aResult && isset($aResult['results']) && is_array($aResult['results'])) {
            foreach ($aResult['results'] as &$result) {
                if (array_key_exists($result['status'], self::$status)) {
                    $result['status_msg'] = self::$status[$result['status']];
                } else {
                    $result['status_msg'] = '';
                }
                if (!empty($result['user_layerids']) || '0' === $result['user_layerids']) {
                    $result['user_layerids_array'] = explode(',', $result['user_layerids']);
                } else {
                    $result['user_layerids_array'] = [];
                }
                if (!empty($result['key'])) {
                    $result['key'] = authcode($result['key'], 'DECODE', $GLOBALS['config']['AesKeys']);
                    $result['key'] = secureStringOutPut($result['key']);
                }
            }
        }
        return $aResult;
    }

    /**
     * 获取全部列表（无分页）
     * @param $iLvtopid
     * @param $iStatus
     * @return array
     */
    public function getAllList($iLvtopid, $iStatus = -1) {
        if (!is_numeric($iStatus)) {
            return false;
        }
        if (-1 === $iStatus) {
            $sStatus = "1";
        } else {
            $sStatus = "`a`.`status` ='${iStatus}'";
        }

        $sTableName = "`proxy_fastpay_acc` `a` LEFT JOIN  `fastpay_type` `b` ON `a`.`paytypeid` = `b`.`id` LEFT JOIN `fastpay_company` `c` ON `a`.`companyid` = `c`.`id`";

        $sFields = '`a`.*,`b`.`name` as `pay_type_msg`,`c`.`cnname` as `pay_company_msg`';
        $sSql = "SELECT ${sFields} FROM ${sTableName} WHERE `a`.`lvtopid`='${iLvtopid}' AND `a`.`status`>0 AND {$sStatus}";

        $aResult = $this->oDB->getAll($sSql);
        foreach ($aResult as &$item) {
            if (!empty($item['user_layerids']) || '0' === $item['user_layerids']) {
                $item['user_layerids_array'] = explode(',', $item['user_layerids']);
            } else {
                $item['user_layerids_array'] = [];
            }
        }

        return $aResult;
    }

    /**
     * 检查数据唯一性
     * @author Ben
     * @date 2017-07-13
     * @param $iLvtopid
     * @param int $sCondition
     * @return mixed 正常返回 count 的数量， 返回 false 则是报错了
     */
    public function checkUnique($iLvtopid, $sCondition = 1) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid) || empty($sCondition)) {
            return false;
        }

        $aResult = $this->oDB->getOne("SELECT COUNT(1) as count FROM `proxy_fastpay_acc` WHERE `lvtopid`='${iLvtopid}' AND ${sCondition} LIMIT 1");

        if (is_array($aResult) && isset($aResult['count'])) {
            return intval($aResult['count']);
        }
        return false;
    }
    /**
     * desc 获取单条三方公司记录
     * @author rhovin 2017-10-06
     */
    public function getOne($iLvtopid , $iAccId) {
        return $this->oDB->getOne("SELECT  *  FROM `proxy_fastpay_acc` WHERE `lvtopid`='${iLvtopid}' AND `id`='${iAccId}' AND `status`=1 LIMIT 1");

    }

    /**
     * 根据三方中文名字获取三方
     * @author  pierce
     * @param $sCompanyName
     * @return mixed
     */
    public function getFastPayCompany($sCompanyName) {
        return $this->oDB->getAll("SELECT * FROM `fastpay_company` WHERE `cnname` LIKE '%$sCompanyName%'");
    }
    /**
     * 获取用户层级可以使用的三方支付账号
     * @author Ben
     * @date 2017-07-18
     * @param $iLvtopid
     * @param $iLayerid
     * @param $iType 0:pc 1:mb
     * @return bool
     */
    public function getUserFastPayAcc($iLvtopid, $iLayerid, $iType = 0) {
        if (empty($iLvtopid) || !is_numeric($iLvtopid) || !is_numeric($iLayerid) || !is_numeric($iType)) {
            return false;
        }
        $sWhere = "";
        if ($iType > 0) {
            $sWhere .= " AND a.isshowmobile = 0";
        }
        $sPayTypeIds = !$iType ? '1,2,3,7,9,10,11,21' : '2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21';
        return $this->oDB->getAll("SELECT `a`.*,`b`.`enname` as `enname`,`c`.`name` AS `payname` FROM `proxy_fastpay_acc` `a` LEFT JOIN `fastpay_company` `b` ON `a`.`companyid`=`b`.`id` LEFT JOIN `fastpay_type` `c` on `a`.`paytypeid`=`c`.`id`  WHERE `lvtopid`='${iLvtopid}' AND FIND_IN_SET('${iLayerid}', `user_layerids`) AND `status`=1  $sWhere AND a.paytypeid in(${sPayTypeIds}) order by `seq` ");
    }

    /**获取商户下开启的三方出款账号
     * @param $iLvtopid
     * @return array
     */
    public function getWithdrawAcc($iLvtopid) {
        return $this->oDB->getOne("SELECT * FROM proxy_fastpay_acc WHERE lvtopid ='" . $iLvtopid . "' AND account_type = 1  AND status = 1 ");
    }

    /**
     * 三方账号置顶
     * @param $aData
     * @return bool
     */
    public function doUp($aData) {
        $this->oDB->doTransaction();
        try {
            if (false === $this->oDB->update('proxy_fastpay_acc', ['seq'=> $aData['seq']],"`id`='${aData['id']}' AND `lvtopid`='".$aData['lvtopid']."' AND `paytypeid`='".$aData['paytypeid']."'")) {
                $this->oDB->doRollback();
                return false;
            }
            if ($aData['seq'] == 0) {
                if (false === $this->oDB->update('proxy_fastpay_acc', ['seq'=>1],"`id`!='${aData['id']}' AND `lvtopid`='".$aData['lvtopid']."' AND `paytypeid`='".$aData['paytypeid']."'")) {
                    $this->oDB->doRollback();
                    return false;
                }
            }
            $this->oDB->doCommit(); // 事务提交
        } catch (Exception $e) {
            $this->oDB->doRollback();
            return FALSE;
        }
    }


    /**
     * 根据入款三方昵称检索符合条件的所以支付账号
     * @param $lvtopid
     * @param $data
     * @return array
     */
    public function searchAccByNickName($lvtopid, $data){
        $nickName = $data['fast_pay_name'];
        $sql = "SELECT `id`, `nickname` FROM proxy_fastpay_acc WHERE `lvtopid` = $lvtopid AND `account_type` = 0 AND `nickname` LIKE '%{$nickName}%'";

        return $this->oDB->getAll($sql);
    }
}