<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/9/29
 * Time: 13:05
 */
class controller_marquee extends pcontroller{
    //构造函数
    public function __construct() {
        parent::__construct();
    }

    /**
     * 跑马灯列表
     * @author pierce
     * @date 2017-9-29
     */
    public function actionList(){
        $oMarquee = new model_marquee();
        if ($this->getIsAjax()) {
            $aPaySetList = $oMarquee->getList($this->lvtopid);
            foreach ($aPaySetList as &$v) {
                if ($v['updatetime'] > 0 ){
                    $v['time'] = $v['updatetime'];
                }else{
                    $v['time'] = $v['inserttime'];
                }
            }
            $this->outPutJQgruidJson($aPaySetList,count($aPaySetList));
        }else{
            $GLOBALS['oView']->display("marquee_list.html");
        }
    }

    /**
     * 添加跑马灯
     * @author pierce
     * @date 2017-9-29
     */
    public function actionAdd(){
        $oMarquee = new model_marquee();
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "sorts" => parent::VAR_TYPE_INT,
                "version" => parent::VAR_TYPE_INT,
                "subject" => parent::VAR_TYPE_STR,
                "content" => parent::VAR_TYPE_STR,
            ));
            if (empty($aGetData['subject'])) {
                $this->ajaxMsg(0,"标题不可以为空");
            }
            if (empty($aGetData['content'])) {
                $this->ajaxMsg(0,"内容不可以为空");
            }
            $aPaySetList = $oMarquee->getList($this->lvtopid);
            if (!empty($aPaySetList) && count($aPaySetList) == 5) {
                $this->ajaxMsg(0,"跑马灯数量不能超过5个");
            }
            //入库数据
            $aData = [] ;
            $aData['lvtopid'] = $this->lvtopid ;
            $aData['sorts'] = $aGetData['sorts'];
            $aData['subject'] = $aGetData['subject'];
            $aData['content'] = $aGetData['content'];
            $aData['version'] = $aGetData['version'];
            $aData['inserttime'] = date('Y-m-d H:i:s', time());
            $aData['adminname'] = $this->adminname;
            $result = $oMarquee->add($aData);
            if ($result === true) {
                $this->ajaxMsg(0,"添加成功");
            } else {
                $this->ajaxMsg(1,"添加失败");
            }
        } else {
            $GLOBALS['oView']->display("marquee_add.html");
        }
    }

    /**
     * 修改跑马灯
     */
    public function actionEdit() {
        $iId = intval($_GET['id']);
        $oMarquee = new model_marquee();
        $aMarquee= $oMarquee->getMarqueeById($this->lvtopid,$iId);
        if ($this->getIsPost()) {
            $aGetData = $this->post(array(
                "sorts" => parent::VAR_TYPE_INT,
                "subject" => parent::VAR_TYPE_STR,
                "content" => parent::VAR_TYPE_STR,
                "version" => parent::VAR_TYPE_INT,
            ));
            if (empty($aGetData['subject'])) {
                $this->ajaxMsg(1,"标题不可以为空");
            }
            if (empty($aGetData['content'])) {
                $this->ajaxMsg(1,"内容不可以为空");
            }
            $aPaySetList = $oMarquee->getList($this->lvtopid);
            $aSorts = array_column($aPaySetList,'sorts');
            if (in_array($aGetData['sorts'],$aSorts) && $aGetData['sorts'] != $aMarquee['sorts']){
                $this->ajaxMsg(1,"排序不可以重复");
            }
            //入库数据
            $aData = [] ;
            $aData['lvtopid'] = $this->lvtopid ;
            $aData['sorts'] = $aGetData['sorts'];
            $aData['subject'] = $aGetData['subject'];
            $aData['content'] = $aGetData['content'];
            $aData['version'] = $aGetData['version'];
            $aData['adminname'] = $this->adminname;
            $result = $oMarquee->editById($aData,$iId);
            if ($result) {
                $this->ajaxMsg(0,"修改成功");
            } else {
                $this->ajaxMsg(1,"修改失败");
            }
        } else {
            $GLOBALS['oView']->assign("aMarquee",$aMarquee);
            $GLOBALS['oView']->display("marquee_edit.html");
        }
    }

    /**
     * 删除跑马灯
     */
    public function actionDelete() {
        $aGetData = $this->get(array(
            "id"=> parent::VAR_TYPE_INT,
        ));
        $oMarquee = new model_marquee();
        $mResult = $oMarquee->deleteById($this->lvtopid,$aGetData['id']);
        if($mResult) {
            sysMessage("删除成功");
        } else {
            sysMessage("删除失败", 1);
        }
    }

    /**
     * 查看跑马灯
     */
    public function actionView() {
        $aGetData = $this->get(array(
            "id"=> parent::VAR_TYPE_INT,
        ));
        $oMarquee = new model_marquee();
        $aMarquee= $oMarquee->getMarqueeById($this->lvtopid,$aGetData['id']);
        $GLOBALS['oView']->assign("aMarquee",$aMarquee);
        $GLOBALS['oView']->display("marquee_view.html");
    }

    /**
     * 设置跑马灯是否展示
     */
    public function actionSet() {
        $aGetData = $this->get(array(
            "id"=> parent::VAR_TYPE_INT
        ));
        $oMarquee = new model_marquee();
        $aOne = $oMarquee->getMarqueeById($_SESSION['lvtopid'], $aGetData['id']);
        if ($aOne['isshow'] == '1'){
            $aGetData['isshow'] = '0';
        }else{
            $aGetData['isshow'] = '1';
        }
        $mResult = $oMarquee->editById($aGetData,$aGetData['id']);
        if($mResult) {
            if($aGetData['isshow'] == 1){
                $this->ajaxMsg(1,'跑马灯已停用');
            }else if ($aGetData['isshow'] == 0) {
                $this->ajaxMsg(1,'跑马灯已启用');
            }
        } else {
            $this->ajaxMsg(1,'操作失败');
        }
    }
}