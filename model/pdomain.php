<?php
/**
 * Created by PhpStorm.
 * User: pierce
 * Date: 2017/6/28
 * Time: 17:43
 */
class model_pdomain extends basemodel{
    /**
     * @desc 获取本代理下所有域名
     * @author pierce
     * @date 2017-06-28
     * @param $lvtopid
     * @param $bIsUniqueLink 是否对独立的代理链接
     */
    public function getDomainByLvTopId($lvtopid, $bIsUniqueLink = false){
        $sSql = "SELECT id,`domain` FROM `domains` WHERE `lvtopid`='".$lvtopid."' AND `isfast`!=1 AND `isadmin`=0";
        if ($bIsUniqueLink) {
            $sNot = '';
            $sSql2 = "SELECT DISTINCT `reg_domain` FROM  `user_register_links` WHERE `lvtopid` = '{$lvtopid}' AND `reg_domain` IS NOT NULL AND `reg_domain` <>''";
            $res = $this->oDB->getAll($sSql2);
            if (!empty($res)) {
                foreach ($res as $v) {
                    $sNot .= "'{$v['reg_domain']}'" . ',';
                }
            }
            $sNot = trim($sNot, ',');
        }
        if (!empty($sNot)) {
            $sSql = $sSql . " AND domain not in({$sNot}) ORDER BY domain ASC";
        }

        return $this->oDB->getAll($sSql);
    }

    /**
     * @desc 获取已绑定的域名
     * @author pierce
     * @date 2017-08-28
     * @param $lvtopid
     * @param $domain
     * @return array
     */
    public function getDomainByDomain($lvtopid,$domain){
        $sSql = "SELECT * FROM `user_register_links` WHERE `lvtopid`='".$lvtopid."' AND `reg_domain`='".$domain."'";
        return $this->oDB->getAll($sSql);
    }
    /**
     * @desc 添加代理域名
     * @author pierce
     * @date 2017-06-28
     */
    public function addProxyDomain($aData){
        $a = $this->oDB->insert('user_register_links', $aData);
        if($this->oDB->ar() < 1) {
            return false;
        }
        return true;
    }
    /**
     * @decs 根据id获取代理域名信息
     * @author pierce
     * @date 2017-06-29
     */
    public function getProxyDomainById($id,$iLvtopid){
        $request = $this->oDB->getOne("SELECT * FROM user_register_links WHERE `id` = '" . intval($id) . "' AND  `lvtopid` = '" . intval($iLvtopid) . "'");
        if ($request == ""){
            return false;
        }
        return $request;
    }
    /**
     * @decs 修改代理域名
     * @author pierce
     * @date 2017-06-29
     */
    public function editProxyDomain($aParams,$id,$iLvtopid){
        $sTempWhereSql = " `id` = '" . intval($id) . "' AND  `lvtopid` = '" . intval($iLvtopid) . "'";
        return $this->oDB->update('user_register_links', $aParams, $sTempWhereSql);
    }

    /**
     * 根据商户id和注册码获取信息
     *
     * @author left
     * @date 2017/7/14
     *
     * @param int       $iLvtopid 商户id
     * @param string    $sCode    注册码
     *
     * @return array
     */
    public function getInfoByCode($iLvtopid, $sCode) {
        $sSql = "SELECT * FROM `user_register_links` WHERE `lvtopid`={$iLvtopid} AND `reg_code`='{$sCode}'";
        return $this->oDB->getOne($sSql);
    }

    /**
     * 根据商户id和域名获取信息
     *
     * @author left
     * @date 2017/7/14
     *
     * @param int       $iLvtopid 商户id
     * @param string    $sDomain  域名
     *
     * @return array
     */
    public function getInfoByDomain($iLvtopid, $sDomain) {
        $aReturn = [];
        if ($sDomain == "") {
            return $aReturn;
        }

        $sDomain = daddslashes($sDomain);

        $aDomain = explode(":", $sDomain);
        $sDomain = $aDomain[0];

        $aDomain2 = explode(".", $sDomain);
        $sDomain2_2 = array_pop($aDomain2);
        $sDomain2_1 = array_pop($aDomain2);
        $sDomain2 = $sDomain2_1 . "." . $sDomain2_2;

        $sSql = "SELECT * FROM `user_register_links` WHERE `lvtopid`={$iLvtopid} AND `reg_domain` LIKE '{$sDomain}%' OR `reg_domain` LIKE '{$sDomain2}%'";
        $aResult = $this->oDB->getAll($sSql);

        if (count($aResult) == 1) {
            $aReturn =  array_pop($aResult);
        } else {
            foreach ($aResult as $k=>$v) {
                if($sDomain == $v['reg_domain']) {
                    $aReturn =  $aResult[$k];
                }
            }
        }
        return $aReturn;
    }

    /**
     * 用户注册时对商户注册人数的影响
     * @author james liang
     * @date    2017-7-23
     *
     * @param  int      $iId  表id
     * @param  boolean  $bSet 为真则浏览人数+1，否则注册人数+1
     *
     * @return  int   受影响的行数
     */
    public function setAddreguser($iId, $bSet = false) {
        $sql = "update `user_register_links` set `reg_users`=`reg_users`+1 where `id`='{$iId}'";
        if ($bSet) {
            $sql = "update `user_register_links` set `views`=`views`+1 where `id`='{$iId}'";
        }
        return $this->getDB()->query($sql);
    }

    /**
     * 获取商户代理加盟code
     *
     * @author left
     * @date 2017/8/11
     *
     * @param int $iLvtopid 商户id
     *
     * @return array
     */
    public function getHomeLink($iLvtopid) {
        $iLvtopid = intval($iLvtopid);
        /* @var $oMemCache memcachedb */
        $oMemCache = A::singleton( 'memcachedb', $GLOBALS['aSysMemCacheServer']);
        $aResult = $oMemCache->getOne("user_register_links_" . $iLvtopid);
        if (false === $aResult) {
            $sSql = "SELECT * FROM `user_register_links` WHERE `lvtopid`={$iLvtopid} AND `ishomelink`=1 ";
            $aResult = $this->oDB->getOne($sSql);
            $oMemCache->insert("user_register_links_" . $iLvtopid, $aResult, 60*60*24);
        }

        return $aResult;
    }

}