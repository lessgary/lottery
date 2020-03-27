<?php

/**
 * @desc 商户资讯
 * @author Ben
 * @date 2017-06-22
 *
 */
class model_proxyinformation extends basemodel {

    /**
     * 资讯类型
     * @var array
     */
    public static $TYPE = [
        '新闻',
        '技巧'
    ];

    /** 资讯状态
     * @var array
     */
    public static $STATUS = [
        '正常',
        '隐藏',
        '删除'
    ];

    /**
     * 添加记录
     * @author Ben
     * @date 2017-06-22
     * @param $aData
     * @return bool
     */
    public function add($aData) {
        if (empty($aData) || !is_array($aData)) {
            return false;
        }
        $aData['lasttime'] =  date('Y-m-d H:i:s');
        if (!empty($aData['content'])) {
            $aData['content'] = imageSrcRemove($aData['content']);
        }

        return $this->oDB->insert('proxyinformation', $aData);
    }

    /**
     * 获取列表
     * @author Ben
     * @date 2017-06-22
     * @param $iLvtopid
     * @param $iStatus
     * @param $iType
     * @param $iRows
     * @param $iPage
     * @return mixed
     */
    public function getList($iLvtopid, $iStatus, $iType, $iRows, $iPage) {
        $sTableName = '`proxyinformation`';

        if (empty($iLvtopid) || !is_numeric($iLvtopid)) {
            return false;
        }
        $sWhere = " `lvtopid` = '${iLvtopid}' AND `status` < 2";

        if (-1 != $iStatus){
            $sWhere .= " AND `status` = '${iStatus}'";
        }

        if (-1 != $iType) {
            $sWhere .= " AND `type`='${iType}'";
        }

        $sFields = '`informationid`,`lvtopid`,`sort`,`type`,`title`,`status`,`is_sticky`,`lastuser`,`lasttime`';

        $aResult = $this->oDB->getPageResult($sTableName, $sFields, $sWhere, $iRows, $iPage, 'ORDER BY `is_sticky` DESC,`sort` DESC');
        if ($aResult && !empty($aResult['results'])) {
            foreach ($aResult['results'] as &$item) {
                if (array_key_exists($item['status'], self::$STATUS)) {
                    $item['status_msg'] = self::$STATUS[$item['status']];
                }
                if (array_key_exists($item['type'], self::$TYPE)) {
                    $item['type_msg'] = self::$TYPE[$item['type']];
                }
            }
        }
        return $aResult;
    }

    /**
     * 获取单条记录
     * @author Ben
     * @date 2017-07-08
     * @param $iLvtopid
     * @param $iId
     * @param $isPassport
     * @return array
     */
    public function getOne($iLvtopid, $iId, $isPassport = false) {
        $result = $this->oDB->getOne("SELECT * FROM `proxyinformation` WHERE `lvtopid`='${iLvtopid}' AND `informationid`='${iId}'");
        if ($result && $result['content']) {
            $result['content'] = $isPassport ? $result['content'] : imageSrcAdd($result['content']);
        }
        return $result;
    }

    /**
     * 修改资讯
     * @author Ben
     * @date 2106-06-22
     */
    public function edit($aData, $sCondition) {
        if(isset($aData['content'])) {
            $aData['content'] = imageSrcRemove($aData['content']);
        }
        return $this->oDB->update('proxyinformation', $aData, $sCondition);
    }

    /**
     * 修改资讯状态
     * @author Ben
     * @date 2017-06-23
     * @param $iLvtopid
     * @param $iInformationid
     * @param $iAdminid
     * @param $sAdminName
     * @return bool
     */
    public function changeStatus($iLvtopid, $iInformationid, $iAdminid, $sAdminName){
        $sWhere = "informationid='${iInformationid}' and lvtopid='${iLvtopid}'";
        $aInfo = $this->oDB->getOne("select is_sticky from proxyinformation where ${sWhere}");
        if (!$aInfo) {
            return false;
        }

        if (0 == $aInfo['is_sticky']) {
            // 开启事务
            $this->oDB->doTransaction();
            try {
                $this->oDB->update('proxyinformation',['is_sticky' => 1, 'admin_id' => $iAdminid, 'lastuser' => $sAdminName], $sWhere);
                $this->oDB->update('proxyinformation',['is_sticky' => 0, 'admin_id' => $iAdminid, 'lastuser' => $sAdminName], "informationid<>'${iInformationid}' and lvtopid='${iLvtopid}'");
                $this->oDB->doCommit();
                return 1;
            } catch (Exception $oException) {
                // 回滚，返回失败
                $this->oDB->doRollback();
                return false;
            }
        } else {
            return $this->oDB->update('proxyinformation',['is_sticky' => 0, 'admin_id' => $iAdminid, 'lastuser' => $sAdminName], $sWhere);
        }
    }

    /**
     * 获取商户资讯的置顶信息
     *
     * @author left
     * @date 2017/08/09
     *
     * @param  int      $iLvtopid 商户id
     *
     * @return array    置顶信息
     */
    public function getTopOne($iLvtopid) {

        $iLvtopid = intval($iLvtopid);

        $result = $this->oDB->getOne("SELECT * FROM `proxyinformation` WHERE `lvtopid`='${iLvtopid}' AND `is_sticky`=1 AND `status`=0 ");
        return $result;
    }
     /**
     * 获取公司简介
     * @author Rhovin 
     * @date 2017-11-24
     * @param $iLvtopid
     * @param $iId
     * @return array
     */
    public function getCompanyDes($iLvtopid,$front = '') {
        $result = $this->oDB->getOne("SELECT * FROM `proxy_company_des` WHERE `lvtopid`='${iLvtopid}' ");
        if(!empty($result) && $front) {
            $result['content'] = imageSrcRemove($result['content']);
        }
        return $result;
    }
    /**
     * desc 编辑公司简介
     * @author rhovin 2017-11-24
     */
    public function editCompanyDes($aData,$sWhere) {
        return $this->oDB->update('proxy_company_des',$aData, $sWhere);
    }
    /**
     * desc 添加公司简介
     * @author rhovin 2017-11-24
     */
    public function addCompanyDes($aData) {
        return $this->oDB->insert('proxy_company_des',$aData);
    }
}