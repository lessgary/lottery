<?php

/**
 * 支付方式
 * @desc
 * @author Ben
 * @date 2017-06-17
 *
 */
class model_fastpaytype extends basemodel {

    /**
     * 获取列表
     * @author Ben
     * @date 2017-06-26
     */
    public function getList(){
        return $this->oDB->getAll("SELECT * FROM `fastpay_type`");
    }
}