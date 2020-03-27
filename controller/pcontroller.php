<?php

/**
 * @desc 功能: 控制器 - 用户管理
 * 
 * @author    rhovin
 * @package   proxyweb
 * @date 2017-05-27
 * 已对每一行代码进行规范化处理. 最后效验于 2010-09-17 00:07
 */
class pcontroller extends basecontroller {

    /** * @desc intval 转整型 */
    CONST VAR_TYPE_INT = 0;
    
    /** * @desc 格式：Y-m-d H:i:s */
    CONST VAR_TYPE_DATETIME = 1;

    /** * @desc daddslashes */
    CONST VAR_TYPE_STR = 2;

    /** * @desc 格式：Y-m-d */
    CONST VAR_TYPE_DATE = 3;

    /** * @desc float  1000.01 */
    CONST VAR_TYPE_FLOAT = 4;

    /** * @desc H:i:s */
    CONST VAR_TYPE_TIME = 5;

	//各种列表默认查询时间
    protected $sReportTime;

    //当前登录的商户ID
    protected $lvtopid;

//    当前登录商户管理员id
    protected $loginProxyId;
    
//    当前登录商户名
    protected $adminname;
    
    //请求处理 默认:'_method'.
    public $methodParam = '_method';

    //过滤之后在请求数据
    private $_filterData;
    
    protected $sToday;


    //构造函数
    public function __construct() {

        parent::__construct();
        $this->lvtopid = isset($_SESSION['lvtopid']) ? $_SESSION['lvtopid'] : ""; //总代ID
        $this->loginProxyId = isset($_SESSION['proxyadminid']) ? $_SESSION['proxyadminid']:"" ; //当前登录的商户ID
        $this->adminname = isset($_SESSION['adminname']) ?$_SESSION['adminname'] :""; //当前登录的商户名
        $this->sReportTime = getConfigValue("tradeset_reporttime", "03:00:00");
        $this->sToday = date("Y-m-d");
        /*$this->sReportStartDate = date('Y-m-d');
        if(date('H:i:s') < $this->sReportTime) {
            $this->sReportStartDate = date('Y-m-d', strtotime("-1 day"));
        }*/
    }

    /**
     * @desc jqguid表格输出json数据
     * @author rhovin 
     * @param Array data 要展示的数组
     * @param int records 总条数 records
     * @param int page 当前页数 
     * @param int rows 每页展示的条数
     * @param array $aExtend
     * @return String {"rows":[{"cell":{"userid":"6","usertype":"0","username":"asd123"}}],"records":"7","page":"1","total":120}
     */
    public function
    outPutJQgruidJson($aData, $records,$page = 1 ,$rows = 20, $aExtend = []) {
        if (!empty($aData)) {
            if(intval($rows) == 0)  exit("每页展示的条数不能为0");
            foreach ($aData as $key => $value) {
                $aData['rows'][$key]['cell'] = $value;
                $aData['records'] = $records;
                $aData['total'] = ceil($records/$rows);//进一法取整
                $aData['page'] = $page;
                $aData['extend'] = $aExtend;
            }
            unset( $aData['rows']["userdata"]);
            exit(json_encode($aData));
        }
            exit(json_encode($aData));
    }
    /**
     * @author rhovin
     * @desc 获取post参数
     * @默认获取全部参数值 单个获取e.g $this->post("username")  如果没有  
     */
    public function get($format) {
        if (is_array($format)) {
            return $this->filter($_GET, $format);
        }
        return [];
    }
    /**
     * @author rhovin
     * @desc 获取post参数
     * @e.g post(array("username" =>self::TYPE_STR));
     */
    public function post($format) {
        if (is_array($format)) {
            return $this->filter($_POST, $format);
        }
        return [];
    }
    //过滤参数
    private function filter($aData, $aFormat) {
            $aResult = array();
            if (is_array($aData) && is_array($aFormat)) {
                foreach ($aFormat as $key => $type) {
                    if(isset($aData[$key]) && $aData[$key] == "") {
                        $aResult[$key] = "";
                        continue;
                    }
                    switch ($type) {

                        case self::VAR_TYPE_INT:
                            $aResult[$key] = isset($aData[$key]) ? intval($aData[$key]) : 0;
                            break;

                        case self::VAR_TYPE_DATETIME:
                            $aResult[$key] = !empty($aData[$key]) ? date("Y-m-d H:i:s",strtotime($aData[$key])) : '';
                            break;
                        case self::VAR_TYPE_DATE:
                            $aResult[$key] = !empty($aData[$key]) ? date("Y-m-d",strtotime($aData[$key])) : '';
                            break;

                        case self::VAR_TYPE_STR:
                            $aResult[$key] = isset($aData[$key]) ? trim(daddslashes($aData[$key])) : "";
                            break;  

                        case self::VAR_TYPE_FLOAT:
                            $aResult[$key] = isset($aData[$key]) ? floatval($aData[$key]) : "0.0000";
                            break;  
                         
                        default:
                            break;
                    }
                }
            }
            return $aResult;
    }
    /**
     * @author rhovin
     * @desc 判断当前请求方式  (e.g. GET, POST, HEAD, PUT, PATCH, DELETE).
     * @return 请求方式 像 GET, POST, HEAD, PUT, PATCH, DELETE.
     * @date 2017-05-30
     */
    public function getMethod() {

        if (isset($_POST[$this->methodParam])) {
            return strtoupper($_POST[$this->methodParam]);
        }
        
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }
        
        if (isset($_SERVER['REQUEST_METHOD'])) {
            return strtoupper($_SERVER['REQUEST_METHOD']);
        }
        
        return 'GET';
    }
    /**
     * @desc ajax 输出
     * @author rhovin 
     * @date 2017-06-05
     * @code 返回码 1、成功 0、失败
     * @msg 返回消息
     * @data array 返回数据
     */
    public function ajaxMsg($code=0,$msg,$data = []){
        $data['code'] = $code;
        $data['msg'] = $msg;
        exit(json_encode($data));
    }

    /**
     * @desc 判断是否ajax请求
     * @author rhovin
     * @date 2017-05-30
     */
    public function getIsAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    /**
     * @desc 判断是否GET请求
     * @author rhovin
     * @date 2017-05-30
     */
    public function getIsGet() {
        return $this->getMethod() === 'GET';
    }
    /**
     * @desc 判断是否POST请求
     * @author rhovin
     * @date 2017-05-30
     */
    public function getIsPost() {
        return $this->getMethod() === 'POST';
    }

    /**
     * 表单提交显示错误页面
     * @author Ben
     * @date 2017-06-19
     * @param $sMsg
     */
    protected function error($sMsg) {
        if ($this->getIsAjax()) {
            die(json_encode(['error' => 1, 'msg' => $sMsg]));
        }
        sysMessage($sMsg, 1);
        die;
    }

    /**
     * 获取passport项目上传文件路径
     * @return string
     */
    protected function getPassportPath() {
        return getPassportPath();
    }


    /**
     * @author robert
     * @desc 获取post参数
     * @e.g post(array("username" =>self::TYPE_STR));
     */
    public function filtrationPost($aData,$format) {
        if (is_array($format)&& is_array($aData)) {
            return $this->filter($aData, $format);
        }
        return [];
    }
}