<?php

/**
 * 文件 : /_app/controller/message.php
 * 功能 : 控制器 - 消息管理
 *
 * @author           ken 2017
 * @package   passportadmin
 */
class controller_message extends pcontroller
{
    // @TODO 增加主要列表
    /**
     * 将值显示在页面
     */
    public function actionList()
    {
        $sToday    = $this->sToday;
        $tingri = date('Y-m-d',strtotime('+1 days'));
        /**
         * 实例化模型类
         */
        $oMessage = new model_proxymessage();
        /**
         * 1 接收页面填写的搜索条件
         * 2 根据搜索条件拼接查询语句
         */
        if ($this->getIsPost()) {
            //TODO 调试 之后修改回来
            // 1 接收数据并过滤
            $aGetData = $this->post(array(
                "page"        => parent::VAR_TYPE_INT,
                "rows"        => parent::VAR_TYPE_INT,
                "mt"          => parent::VAR_TYPE_STR, //可读取消息类型 根据管理员
                "isread"      => parent::VAR_TYPE_INT,
                "isdel"       => parent::VAR_TYPE_INT,
                "subject"     => parent::VAR_TYPE_STR, //标题内容
                "sdate"       => parent::VAR_TYPE_DATETIME,
                "edate"       => parent::VAR_TYPE_DATETIME,
                "sendername"  => parent::VAR_TYPE_STR,
                "receivename" => parent::VAR_TYPE_STR,
                "sidx" => parent::VAR_TYPE_STR,
                "sord" => parent::VAR_TYPE_STR,
            ));
            $sOrderBy = $aGetData['sidx'] == '' ? "b.`id` ${aGetData['sord']}" :"${aGetData['sidx']} ${aGetData['sord']}";
            // 2 拼接 WHERE 条件语句
            $sWhere = $this->_getSearch($aGetData);
            // 是否设置分页大小 未设置则取默认值
            $aResult = $oMessage->getMessageList('*', $sWhere, $aGetData['rows'], intval($aGetData['page']), $sOrderBy);

            if (!empty($aResult) && !empty($aResult)) {
                foreach ($aResult['results'] as $k => $v) {
                    if ($aResult['results'][$k]['readtime'] == null) {
                        $aResult['results'][$k]['isread'] = 1; // 未读
                    } else {
                        $aResult['results'][$k]['isread'] = 2;
                    }

                    if ($aResult['results'][$k]['deltime'] == null) {
                        $aResult['results'][$k]['isdel'] = 1; //未删除
                    } else {
                        $aResult['results'][$k]['isdel'] = 2; //删除
                    }
                }
                // @TODO 判断消息类型
                foreach ($aResult['results'] as $k => $v) {
                    if ($aResult['results'][$k]['msgtypeid'] == 1) {
                        $aResult['results'][$k]['mt'] = "充提消息";
                    } elseif ($aResult['results'][$k]['msgtypeid'] == 2) {
                        $aResult['results'][$k]['mt'] = "用户私信";
                    } elseif ($aResult['results'][$k]['msgtypeid'] == 10) {
                        $aResult['results'][$k]['mt'] = "监控用户";
                    } elseif ($aResult['results'][$k]['msgtypeid'] == 20) {
                        $aResult['results'][$k]['mt'] = "大额撤单";
                    } elseif ($aResult['results'][$k]['msgtypeid'] == 30) {
                        $aResult['results'][$k]['mt'] = "财务每日";
                    } elseif ($aResult['results'][$k]['msgtypeid'] == 31) {
                        $aResult['results'][$k]['mt'] = "系统消息";
                    } elseif ($aResult['results'][$k]['msgtypeid'] == 32) {
                        $aResult['results'][$k]['mt'] = "活动消息";
                    } elseif ($aResult['results'][$k]['msgtypeid'] == 33) {
                        $aResult['results'][$k]['mt'] = "中奖消息";
                    }
                }
                $this->outPutJQgruidJson($aResult['results'], $aResult['affects'], $aGetData['page'], $aGetData['rows']);
            }
            $GLOBALS['oView']->assign("today", $sToday);
            $GLOBALS['oView']->assign("mingtian", $tingri);
            $GLOBALS['oView']->display("message_list.html");
        } else {
            // 根据管理员权限, 解析 '消息类型'
            $aHtml['mtoptions'] = $oMessage->getMessageTypeByAdminMenus('opts');
            $GLOBALS['oView']->assign("mingtian", $tingri);
            $GLOBALS['oView']->assign("today", $sToday);
            $GLOBALS['oView']->assign("s", $aHtml);
            $GLOBALS['oView']->display("message_list.html");
        }
    }

    /**
     * 1 选择的是会员 send_range = member
     * 2 选择是的层级 send_range = level
     * @author ken 2017
     */
    public function actionSend()
    {
        //重定向专用
        $aLocation = array(0 => array("text" => "消息列表", "href" => url("message", "list")),
            1 => array("text" => "添加消息", "href" => url("message", "add")));
//        $aLocation[0] = array("text" => '游戏信息列表', "href" => url("game", "list"));
        $oProxyMessage = new model_proxymessage();
        $aGetData      = $this->post(array(
            "mt"          => parent::VAR_TYPE_INT, //消息类型
            "subject"     => parent::VAR_TYPE_STR, //消息标题
            "receivename" => parent::VAR_TYPE_STR, //收件人
            "content"     => parent::VAR_TYPE_STR, //正文
            "send"        => parent::VAR_TYPE_STR, //隐藏域 固定为"发送消息" 否则为非法提交
            "send_range"  => parent::VAR_TYPE_STR,
            "all_level"   => parent::VAR_TYPE_INT,
        ));
        if (empty($aGetData["mt"]) || empty($aGetData["subject"])
            || empty($aGetData["content"]) || empty($aGetData["send"])
        ) {
            sysMessage("提交数据有缺失,请重新检查", 1, $aLocation);
        }
        // 3 检查发送范围
        /**
         * 1 选择的是会员
         * 2 根据选择的会员下属类型进行判断
         */
        // 1 拼接获取的type类型
        $aGetData['type']         = isset($_POST['type']) ? $_POST['type'] : '';
        $aGetData['proxyadminid'] = $this->loginProxyId;//发送者
        $aGetData['lvtopid']      = $this->lvtopid; //发送者
        $aGetData['sendername']   = $this->adminname; //发送者
        $aGetData['level']        = isset($_POST['level']) ? $_POST['level'] : '';
        $iFlag                    = $oProxyMessage->InsertMessageFromAdmin($aGetData);
        if ($iFlag > 0) {
            sysMessage("操作成功, 消息已发送. ", 0, $aLocation);
        }
        switch ($iFlag) {
            case -1000:
                sysMessage("用户层级不能为空", 1, $aLocation);
                break;
            case -1001:
                sysMessage("该用户层级下没有用户", 1, $aLocation);
                break;
            case -1002:
                sysMessage("请选择要发送的用户", 1, $aLocation);
                break;
            case -1003:
                sysMessage("输入用户错误", 1, $aLocation);
                break;
            case -1004:
                sysMessage("群发选项错误", 1, $aLocation);
                break;
            case -1005:
                sysMessage("接收用户错误", 1, $aLocation);
                break;
            case -1006:
                sysMessage("该商户下用户名错误", 1, $aLocation);
                break;
            case -1007:
                sysMessage("该用户没有下级", 1, $aLocation);
                break;
            case -1008:
                sysMessage("该用户没有直接下级", 1, $aLocation);
                break;
            case -1009:
                sysMessage("发送类型错误", 1, $aLocation);
                break;
            case -2001:
                sysMessage("该用户不属于登录用户所属商户", 1, $aLocation);
                break;
            case -1010:
                sysMessage("系统错误,消息发送失败", 1, $aLocation);
                break;
        }

        if ($iFlag > 0) {
            sysMessage("操作成功, 消息已发送. ", 0, $aLocation);
        } elseif ($iFlag == -1) {
            sysMessage("群发选项无效", 1, $aLocation);
        } elseif ($iFlag == -2) {
            sysMessage("消息内容插入失败,请与技术部联系", 1, $aLocation);
        } elseif ($iFlag == -3) {
            sysMessage("接收消息用户名不存在,请检查", 1, $aLocation);
        } elseif ($iFlag == -4) {
            sysMessage("无法找到对应的层级用户", 1, $aLocation);
        } elseif ($iFlag == -10) {
            sysMessage("消息插入失败,请与技术部联系", 1, $aLocation);
        } elseif ($iFlag == -12 || $iFlag == -13) {
            sysMessage("该用户无下属用户", 1, $aLocation);
        } else {
            sysMessage("操作失败,未分类错误", 1, $aLocation);
        }
        exit;
    }

    /**
     * 删除消息
     * URL = ./?controller=message&action=del
     * @author ken 2017 @TODO CHANGE
     */
    public function actionDel()
    {
        $iLvtopid = $this->lvtopid;
        // 跳转
        $aLocation = array(0 => array("text" => "消息列表", "href" => url("message", "list")));
        /**
         *  1 通过 get 提交
         */
        $aGetData = $this->get(array(
            'msgid' => parent::VAR_TYPE_INT,
        ));
        /**
         * 2 根据id对数据库进行更新
         */
        if (isset($aGetData)) {
            // 获取的id
            $sId           = $aGetData['msgid'];
            $oProxyMessage = new model_proxymessage();
            $rst           = $oProxyMessage->delMessageById($sId,$iLvtopid);
            if ($rst) {
                sysMessage("消息删除成功. ", 0, $aLocation);
            } else {
                sysMessage("消息删除失败，请联系管理员. ", 0, $aLocation);
            }
        }
        exit;
    }

    /**
     * 增加消息
     * URL = ./?controller=message&action=add
     * @author ken 160617
     */
    public function actionAdd()
    {
        /* @var $oMessage model_message */
        $oMessage = new model_proxymessage();
        //@TODO 调试用
        $aLayer             = $oMessage->getUserLayer($this->lvtopid);
        $aHtml['mtoptions'] = $oMessage->getUserCanReadMessageType('opts');
        $GLOBALS['oView']->assign("layer", $aLayer);
        $GLOBALS['oView']->assign("info", $aHtml);
        $GLOBALS['oView']->assign("ur_here", "增加消息");
        $GLOBALS['oView']->assign('actionlink', array('href' => url("message", "list"), 'text' => '消息列表'));
        $GLOBALS['oView']->display("message_add.html");
        exit;
    }

    /**
     * 查看消息
     * URL = ./?controller=message&action=view&id={id}
     * @author ken 2017
     */
    public function actionView()
    {
        $iLvtopid = $this->lvtopid;
        $iAdmin = $this->loginProxyId;
        $sAdminName = $this->adminname;
        // 跳转 URL
        $aLocation = array(0 => array("text" => "消息列表", "href" => url("message", "list")));
        if ($this->getIsGet()) {
            $aGetData = $this->get(array(
                "msgid" => parent::VAR_TYPE_INT,
                "entry" => parent::VAR_TYPE_INT,
            ));
            if (empty($aGetData['entry'])) {
                sysMessage("无效消息ID", 1, $aLocation);
            }
            $iId = $aGetData['entry'];
            /* @var $oMessage model_message */
            $oProxyMessage = new model_proxymessage();
            $aResMessage   = $oProxyMessage->getOneAdminMessage($iId, $iLvtopid); // 返回一维数组 下标[msgid] 对应
            if (!isset($iAdmin) && !isset($sAdminName) && empty($iAdmin) && empty($sAdminName)) {//如果阅读的是管理员
                // 则不设置为已读
                if (is_array($aResMessage)) {
                    $oProxyMessage->setIsReaded($aResMessage['msgid']); //标为已读
                }
            }
            if ($aResMessage == -1) {
                sysMessage("消息读取失败", 1, $aLocation);
            }
            $aResMessage['content'] = nl2br(h($aResMessage['content'])); //一维数组

            if ($aResMessage['readtime']) {
                $aResMessage['readtime'] = "已读";
            } else {
                $aResMessage['readtime'] = "未读";
            }

            if ($aResMessage['deltime']) {
                $aResMessage['deltime'] = "已删";
            } else {
                $aResMessage['deltime'] = "未删";
            }

            //获取商户ID  控制器中默认为1
            $aGetData['lvtopid'] = $this->lvtopid;
            $GLOBALS['oView']->assign("detailMsg", $aResMessage);
            $GLOBALS['oView']->display("message_view.html");
            exit;

        } else {
            exit("参数不正确，请联系管理员");
        }

    }

    /**
     * 获取搜索条件
     *
     * @param $aGetData
     * @return string
     */
    private function _getSearch($aGetData)
    {
        $iLvtopid = $this->lvtopid; //init
        $sWhere   = ' 1 '; //init

        if ($aGetData['isdel'] == -1) {
            $sWhere .= "";
        }

        if ($aGetData['isdel'] == 1) {
            // 已删标记
            $sWhere .= " AND `deltime` IS NOT NULL AND `deltime` > 0 ";
        }
        if ($aGetData['isdel'] == 0) {
            $sWhere .= " AND ( `deltime` IS NULL OR `deltime` = 0 ) ";
        }
        if ($aGetData['isread'] == 1) {
            // 已读标记
            $sWhere .= " AND `readtime` IS NOT NULL AND `readtime` > 0 ";
        }
        if ($aGetData['isread'] == 0) {
            $sWhere .= " AND ( `readtime` IS NULL OR `readtime` = 0 )  ";
        }

        if ($aGetData['sdate'] != '') {
            $sWhere .= " AND ( `sendtime` >= '" . daddslashes($aGetData['sdate']) . "' ) ";
            $aGetData['sdate'] = stripslashes_deep($aGetData['sdate']);
        }
        if ($aGetData['edate'] != '') {
            $sWhere .= " AND ( `sendtime` <= '" . daddslashes($aGetData['edate']) . "' ) ";
            $aGetData['edate'] = stripslashes_deep($aGetData['edate']);
        }
        if ($aGetData['receivename'] != '') {
            $oUser = new model_puser();
            $aReciveId = $oUser->getIdByUsername($iLvtopid,$aGetData['receivename']);
            $iReciveId = $aReciveId['userid'];
            $sWhere .= " AND a.receiverid = '{$iReciveId}' ";
        }

        if ($aGetData['subject'] != '') {
            if (strstr($aGetData['subject'], '*')) {
                $sWhere .= " AND `subject` LIKE '" . str_replace('*', '%', $aGetData['subject']) . "' ";
            } else {
                $sWhere .= " AND `subject` = '" . $aGetData['subject'] . "' ";
            }
            $aHtml['subject'] = h(stripslashes_deep($aGetData['subject']));
        }
        $oMessage = new model_proxymessage();
        //接收到的消息类型不为空
        if ($aGetData['mt'] === '') {
            // 全部消息列表
            $aAdminMenus = $oMessage->getMessageTypeByAdminMenus('arr');
            $sAdminMenus = '';
            if (is_array($aAdminMenus) && !empty($aAdminMenus)) {
                foreach ($aAdminMenus as $v) {
                    if (is_numeric($v)) {
                        $sAdminMenus .= $v . ',';
                    }
                }
            }
            if (substr($sAdminMenus, -1, 1) == ',') {
                $sAdminMenus = substr($sAdminMenus, 0, -1);
            }
            if ($sAdminMenus == '') {
                $sWhere .= " AND 0 ";
            } else {
                $sWhere .= " AND c.`menuid` in ($sAdminMenus) ";
            }
        }
        if ($aGetData['mt'] !== '') {
            // 频道ID
            $sWhere .= " AND c.`menuid` = '" . $aGetData['mt'] . "' ";
        }

        $sWhere .= " AND b.lvtopid = $iLvtopid ";
        return $sWhere;
    }
}
