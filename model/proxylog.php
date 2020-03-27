<?php

/**
 * @desc 商户日志
 * @author Ben
 * @date 2017-06-14
 *
 */
class model_proxylog extends basemodel {
    /**
     * 获取日志列表
     * @author Ben
     * @date 2017-06-14
     * @param $iLvtopid
     * @param $sStarttime
     * @param $sEndTime
     * @param $iProxyMenuId
     * @param $sAdminName
     * @param $sClintIp
     * @param $sContent
     * @param $iRows
     * @param $iPage
     * @return bool|mixed
     */
    public function getList($iLvtopid, $sStarttime, $sEndTime, $iProxyMenuId, $sAdminName, $sClintIp, $sContent, $iRows, $iPage) {
        $sTableName = '`proxylog` AS `a` LEFT JOIN `proxyuser` AS `b` ON `a`.`proxyadminid` = `b`.`proxyadminid`';

        if (empty($iLvtopid) || !is_numeric($iLvtopid)) {
            return false;
        }
        $sWhere = " `a`.`lvtopid` = '${iLvtopid}'";

        if (!empty($sStarttime)) {
            $sWhere .= " AND `a`.`times` >= '${sStarttime}'";
        }

        if (!empty($sEndTime)) {
            $sWhere .= " AND `a`.`times` <= '${sEndTime}'";
        }

        if (!empty($iProxyMenuId)) {
            $aMenuContition = $this->getLogUniConditionByMenuId($iProxyMenuId);
            if ($aMenuContition) {
                $sWhere .= " AND `a`.`controller` = '${aMenuContition['controller']}' and `a`.`actioner` = '${aMenuContition['actioner']}'";
            }
        }

        if (!empty($sAdminName)) {
            $sWhere .= " AND `b`.`adminname` = '${sAdminName}'";
        }

        if (!empty($sClintIp)) {
            $sWhere .= " AND `a`.`clientip` = '${sClintIp}'";
        }

        if (!empty($sContent)) {
            $sWhere .= " AND `a`.`content` like '%${sContent}%' ";
        }

        $sFields = '`a`.`entry`,`b`.`adminname`,`a`.`title`,`a`.`content`,`a`.`clientip`,`a`.`times`';

        return $this->oDB->getPageResult($sTableName, $sFields, $sWhere, $iRows, $iPage, "ORDER BY a.`times` DESC");
    }

    /**
     * 根据菜单id获取菜单
     * @autho
     * @param $iMenuId
     * @return bool
     */
    private function getLogUniConditionByMenuId($iMenuId) {
        if (empty($iMenuId) || !is_numeric($iMenuId)) {
            return false;
        }
        $oProxyMenu = a::singleton('model_proxymenu');
        return $oProxyMenu->proxymenu($iMenuId);
    }
    
    
    /**
     * @param string $title
     * @param string $content
     * @param string $controller
     * @param string $action
     * @param int  $iTypeid   日志类型(0=系统自动日志, 1=特殊日志)
     * @param int $iProxyAdminId
     * @param int $iLvtopId
     * @return BOOL
     */
    function insert($sTitle, $sContent, $sController, $sAction, $iTypeid = 0, $iProxyAdminId = 0, $iLvtopId = 0) {
        if (empty($sTitle) || empty($sController) || empty($sAction)) {
            return FALSE;
        }
        if (empty($sContent)) {
            $sContent = '';
        }
        $aInsertArr['typeid'] = $iTypeid > 0 ? 1 : 0;
        $aInsertArr['proxyadminid'] = intval($iProxyAdminId);
        $aInsertArr['lvtopid'] = intval($iLvtopId);
        $aInsertArr['clientip'] = getRealIP();
        $aInsertArr['proxyip'] = $_SERVER['REMOTE_ADDR'];
        $aInsertArr['times'] = date("Y-m-d H:i:s");
        $aInsertArr['querystring'] = getUrl();
        $aInsertArr['controller'] = daddslashes($sController);
        $aInsertArr['actioner'] = daddslashes($sAction);
        $aInsertArr['title'] = daddslashes($sTitle);
        $aInsertArr['content'] = daddslashes($sContent);
        $aInsertArr['requeststring'] = addslashes(serialize($_REQUEST));
        $this->oDB->insert('proxylog', $aInsertArr);
        return TRUE;
    }
}