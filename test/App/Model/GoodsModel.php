<?php
/**
 * Created by PhpStorm.
 * @file   GoodsModel.php
 * @author 李锦 <jin.li@vhall.com>
 * @date   2020/12/5 1:17 下午
 * @desc   GoodsModel.php
 */

namespace App\Model;

use App\Common\AbstractClass;

class GoodsModel extends AbstractClass
{
    public function getGoodsCount()
    {
        return rand(100,110);
    }
}
