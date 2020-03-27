<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/14
 * Time: 12:53
 */

class model_proxymsglist extends basemodel {
    /**
     * 所有站内信信息
     * @return array
     */
    public function getMsg()
    {
        $sql = "SELECT * FROM proxymsglist";
        return $this->oDB->getAll($sql);
    }
}