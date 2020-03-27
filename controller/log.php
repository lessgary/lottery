<?php

/**
 * 文件 : /_app/controller/log.php
 * 功能 : 控制器 - 日志管理
 *
 * @author    Ben
 * @version   1.0.0
 * @package   proxyweb
 *
 */
class controller_log extends pcontroller {
    /**
     * 视图对象
     * @var $oViewer view
     */
    private $oViewer;

    public function __construct() {
        parent::__construct();
        $this->oViewer = $GLOBALS['oView'];
    }

    /**
     * 获取日志列表
     * @author Ben
     * @date 2017-06-14
     */
    public function actionAdminLog() {
        // 获取菜单操作下拉列表
        $oProxyMenu = A::singleton('model_proxymenu');
        /* @var $oProxyMenu model_proxymenu */
        $aProyxMenu = $oProxyMenu->getUserMenu($this->lvtopid, '`actionlog` = 1') ?: array();
        $this->oViewer->assign('aProxyMenu', $aProyxMenu ?  : []);

        if ($this->getIsPost()) {
            $aCondition = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "starttime" => parent::VAR_TYPE_STR,
                "endtime" => parent::VAR_TYPE_STR,
                "proxy_menu_id" => parent::VAR_TYPE_INT,
                "adminname" => parent::VAR_TYPE_STR,
                "client_ip" => parent::VAR_TYPE_STR,
                "content" => parent::VAR_TYPE_STR,
            ));
            $oProxyLog = A::singleton('model_proxylog');
            /* @var $oProxyLog model_proxylog */
            $aList = $oProxyLog->getList(
                $this->lvtopid,
                $aCondition['starttime'],
                $aCondition['endtime'],
                $aCondition['proxy_menu_id'],
                $aCondition['adminname'],
                $aCondition['client_ip'],
                $aCondition['content'],
                $aCondition['rows'],
                $aCondition['page']
            );
            if (!empty($aList) && !empty($aCondition)) {
                $this->outPutJQgruidJson($aList['results'], $aList['affects'], $aCondition['page'], $aCondition['rows']);
            }
        } else {
            $this->assignTime();
            $this->oViewer->display('log_adminlog.html');
        }
    }

    /**
     * 用户登入记录
     * @author Ben
     * @date 2017-06-15
     */
    public function actionLoginLog() {
        if ($this->getIsPost()) {
            $aCondition = $this->post(array(
                "page" => parent::VAR_TYPE_INT,
                "rows" => parent::VAR_TYPE_INT,
                "starttime" => parent::VAR_TYPE_STR,
                "endtime" => parent::VAR_TYPE_STR,
                "search_type" => parent::VAR_TYPE_INT,
                "login_type" => parent::VAR_TYPE_INT,
                "keyword" => parent::VAR_TYPE_STR,
                "is_check" => parent::VAR_TYPE_STR
            ));
            /* @var $oLoginLog model_userlog */
            $oLoginLog = a::singleton("model_userlog");
            $aList = $oLoginLog->getLoginLogListWithCondition(
                $this->lvtopid,
                $aCondition['starttime'],
                $aCondition['endtime'],
                $aCondition['search_type'],
                $aCondition['keyword'],
                $aCondition['is_check'],
                $aCondition['rows'],
                $aCondition['page'],
                $aCondition['login_type']
            );
            if (!empty($aList) && !empty($aCondition)) {
                //获取登录信息类型
                $aSearchTypes = array(
                    model_userlog::ERRNO_SUCCESS => '登入成功',
                    model_userlog::ERRNO_PASSWORD => '密码错误',
                    model_userlog::ERRNO_FROZEN => '登录冻结账号',
                    model_userlog::ERRNO_OTHERS => '其它错误',
                    model_userlog::REGISTER_SUCCESS => '注册成功'
                );
                foreach ($aList['results'] as $k => $v) {
                    @$aList['results'][$k]['loginmessage'] = $aSearchTypes[$v['errno']];
                    unset($aList['results'][$k]['errno']);
                }
                $this->outPutJQgruidJson($aList['results'], $aList['affects'], $aCondition['page'], $aCondition['rows']);
            }
            exit(json_encode(array()));
        } else {
            $this->assignTime();
            $this->oViewer->display('log_loginlog.html');
        }
    }

    /**
     * 域名统计
     * @author Ben
     * @date 2017-06-16
     */
    public function actionDomainLog() {
        if ($this->getIsPost()) {
            $iIsOutSide = !empty($_POST['is_outside']) ? 1 : 0;
            $aCondition = $this->post(array(
                "starttime" => parent::VAR_TYPE_STR,
                "endtime" => parent::VAR_TYPE_STR,
                'domainname' => parent::VAR_TYPE_STR,
                'rows' => parent::VAR_TYPE_INT,
                'page' => parent::VAR_TYPE_INT
            ));
            /* @var $oDomainLog model_domainreport */
            $oDomainLog = a::singleton("model_domainreport");
            $aList = $oDomainLog->getDomainReportList($aCondition['starttime'], $aCondition['endtime'], $aCondition['domainname'], $aCondition['rows'], $aCondition['page'], $this->lvtopid,$iIsOutSide);
            if (!empty($aList) && is_array($aList)) {
                $this->outPutJQgruidJson($aList['results'], $aList['affects'], $aCondition['page'], $aCondition['rows']);
            }
            exit(json_encode(array()));
        } else {
            $sView = empty($_GET['is_inside']) ? 'log_domainlog_outside.html' : 'log_domainlog.html';
            $this->sReportTime = '';
            $this->assignTime();
            $this->oViewer->display($sView);
        }
    }

    /**
     * 分配时间变量到模板
     * @author Ben
     * @date 2017-06-15
     */
    private function assignTime() {
        $this->oViewer->assign('sdate', date('Y-m-d ' . $this->sReportTime));
        $this->oViewer->assign('edate', date('Y-m-d ' . $this->sReportTime, strtotime('+1 day')));
    }
}