<?php

/**
 * @desc 首页图片设置
 * @author Ben
 * @date 2017-06-22
 *
 */
class model_proxyhomeimage extends basemodel {

    /**
     * 类型
     * @var array
     */
    public static $TYPE = [
        0, // 首页轮播
        1 //  首页浮动窗口
    ];

    /** 状态
     * @var array
     */
    public static $STATUS = [
        '正常',
        '隐藏',
        '删除'
    ];

    public static $POSITION =  [
        1 =>'左边',
        2 =>'右边'
    ];

    /**
     * 首页轮播列表
     * @author Ben
     * @param $iStatus
     * @param $iStatus
     * @param $iRows
     * @param $iPage
     * @date 2017-06-23
     * @return mixed
     */
    public function getRotationList($iLvtopId, $iStatus, $iRows, $iPage) {
        $sTableName = '`proxy_home_image_set`';

        if (empty($iLvtopId) || !is_numeric($iLvtopId)) {
            return false;
        }
        $sWhere = " `lvtopid` = '${iLvtopId}' AND `status` < 2 AND  `type` = 0";

        if (-1 != $iStatus) {
            $sWhere .= " AND `status` = '${iStatus}'";
        }

        $sFields = '`image_id`,`status`,`title`,`pc_image`,`mobile_image`,`link`,`last_time`,`admin_name`,`sort`,`link_type`';

        $aResult = $this->oDB->getPageResult($sTableName, $sFields, $sWhere, $iRows, $iPage, 'ORDER BY `sort` DESC');
        if ($aResult && !empty($aResult['results'])) {
            foreach ($aResult['results'] as &$item) {
                if (array_key_exists($item['status'], self::$STATUS)) {
                    $item['status_msg'] = self::$STATUS[$item['status']];
                }
                if (!empty($item['pc_image'])) {
                    $item['pc_image'] = getImageLoadUrl() . $item['pc_image'];
                }
                if (!empty($item['mobile_image'])) {
                    $item['mobile_image'] = getImageLoadUrl() . $item['mobile_image'];
                }
            }
        }
        return $aResult;
    }

    /**
     * 获取浮动窗口列表
     * @author Ben
     * @date 2017-06-24
     */
    public function getFloatWindowList($iLvtopId, $iStatus, $iPosition, $iRows, $iPage) {
        $sTableName = '`proxy_home_image_set`';

        if (empty($iLvtopId) || !is_numeric($iLvtopId)) {
            return false;
        }
        $sWhere = " `lvtopid` = '${iLvtopId}' AND `status` < 2 AND `type` = 1";

        if (-1 != $iStatus) {
            $sWhere .= " AND `status` = '${iStatus}'";
        }

        if (-1 != $iPosition) {
            $sWhere .= " AND `position` = '${iPosition}'";
        }

        $sFields = '`image_id`,`status`,`title`,`pc_image`,`link`,`last_time`,`admin_name`,`sort`,`position`,`link_type`';

        $aResult = $this->oDB->getPageResult($sTableName, $sFields, $sWhere, $iRows, $iPage, 'ORDER BY `sort` DESC');
        if ($aResult && !empty($aResult['results'])) {
            foreach ($aResult['results'] as &$item) {
                if (array_key_exists($item['status'], self::$STATUS)) {
                    $item['status_msg'] = self::$STATUS[$item['status']];

                }
                if (array_key_exists($item['position'], self::$POSITION)) {
                    $item['position_msg'] = self::$POSITION[$item['position']];
                }
                if (!empty($item['pc_image'])) {
                    $item['pc_image'] = getImageLoadUrl() . $item['pc_image'];
                }

            }
        }
        return $aResult;
    }

    /**
     * 添加图片设置
     * @author Ben
     * @date 2017-06-23
     * @param $aData
     * @return bool|mixed
     */
    public function add($aData) {
        if (!empty($aData['pc_image'])) {
            $aData['pc_image'] = $this->oDB->real_escape_string($aData['pc_image']);
        }
        if (!empty($aData['mobile_image'])) {
            $aData['mobile_image'] = $this->oDB->real_escape_string($aData['mobile_image']);
        }

        $aData['last_time'] = date('Y-m-d H:i:s');
        return $this->oDB->insert('proxy_home_image_set', $aData);
    }

    /**
     * 修改图片数据
     * @param $aData
     * @param $sCondition
     * @return bool|int
     */
    public function edit($aData, $sCondition) {
        if (empty($sCondition)) {
            return false;
        }
        if (!empty($aData['pc_image'])) {
            $aData['pc_image'] = $this->oDB->real_escape_string($aData['pc_image']);
        }
        if (!empty($aData['mobile_image'])) {
            $aData['mobile_image'] = $this->oDB->real_escape_string($aData['mobile_image']);
        }

        return $this->oDB->update('proxy_home_image_set', $aData, $sCondition);
    }

    /**
     * 获取单条记录
     * @author Ben
     * @date 2017-06-23
     * @param $iLvtopId
     * @param $iImageId
     * @return mixed
     */
    public function getByImageId($iLvtopId, $iImageId) {
        if (empty($iLvtopId) || empty($iImageId)) {
            return false;
        }
        return $this->oDB->getOne("SELECT `image_id`,`pc_image`,`mobile_image` FROM `proxy_home_image_set` WHERE lvtopid='${iLvtopId}' AND image_id='${iImageId}'");
    }
}