<?php
// +----------------------------------------------------------------------
// |[ 文档说明: 众筹活动期商品SKU模块Model]
// +----------------------------------------------------------------------



namespace app\lib\models;


use app\BaseModel;
use think\facade\Cache;
use think\facade\Db;

class CrowdfundingActivityGoodsSku extends BaseModel
{
    protected $validateFields = ['activity_code'];

    /**
     * @title  活动商品sku详情
     * @param array $sear
     * @return array
     * @throws \Exception
     */
    public function list(array $sear)
    {
        if (!empty($sear['keyword'])) {
            $map[] = ['', 'exp', Db::raw($this->getFuzzySearSql('title', $sear['keyword']))];
        }

        $map[] = ['activity_code', '=', $sear['activity_code']];
        $map[] = ['round_number', '=', $sear['round_number']];
        $map[] = ['period_number', '=', $sear['period_number']];
        $map[] = ['goods_sn', '=', $sear['goods_sn']];

        $map[] = ['status', 'in', $this->getStatusByRequestModule($sear['searType'] ?? 1)];
        $page = intval($sear['page'] ?? 0) ?: null;
        if (!empty($page)) {
            $aTotal = $this->where($map)->count();
            $pageTotal = ceil($aTotal / $this->pageNumber);
        }

        $list = $this->with(['sku', 'activity'])->where($map)->withMax(['vdc' => 'max_purchase_price'], 'purchase_price')->when($page, function ($query) use ($page) {
            $query->page($page, $this->pageNumber);
        })->order('create_time desc')->select()->toArray();;

        return ['list' => $list, 'pageTotal' => $pageTotal ?? 0];
    }

    /**
     * @title  删除活动商品SKU
     * @param array $data
     * @return mixed
     */
    public function del(array $data)
    {
        $res = $this->where(['activity_code' => $data['activity_code'], 'round_number' => $data['round_number'], 'period_number' => $data['period_number'], 'goods_sn' => $data['goods_sn'], 'sku_sn' => $data['sku_sn'], 'status' => [1, 2]])->save(['status' => -1]);
        $aGoods = $this->where(['activity_code' => $data['activity_code'], 'round_number' => $data['round_number'], 'period_number' => $data['period_number'], 'goods_sn' => $data['goods_sn'], 'status' => [1, 2]])->count();
        if (empty($aGoods)) {
            CrowdfundingActivityGoods::update(['status' => -1], ['activity_code' => $data['activity_code'], 'round_number' => $data['round_number'], 'period_number' => $data['period_number'], 'goods_sn' => $data['goods_sn']]);
        }
        if (empty($data['noClearCache'] ?? null)) {
            if ($res) {
                cache('ApiHomeAllList', null);
                cache('HomeApiCrowdFundingActivityList', null);
                //清除首页活动列表标签的缓存
                Cache::tag(['HomeApiCrowdFundingActivityList', 'ApiHomeAllList'])->clear();
                Cache::tag(['apiWarmUpActivityInfo' . ($data['goods_sn'] ?? '')])->clear();
            }
        }

        return $res;
    }

    public function goodsSpu()
    {
        return $this->hasOne('CrowdfundingActivityGoods', 'goods_sn', 'goods_sn')->where(['status' => 1])->withoutField('id,status,create_time,update_time');
    }

    public function activity()
    {
        return $this->hasOne('CrowdfundingActivity', 'activity_code', 'activity_code')->where(['status' => 1])->withoutField('status,create_time,update_time');
    }

    public function sku()
    {
        return $this->hasOne('GoodsSku', 'sku_sn', 'sku_sn')->where(['status' => 1])->withoutField('id,status,create_time,update_time');
    }

    public function vdc()
    {
        return $this->hasMany('GoodsSkuVdc', 'sku_sn', 'sku_sn')->field('id,sku_sn,level,purchase_price,vdc_genre,vdc_type,belong,vdc_one,vdc_two')->where(['status' => [1, 2]]);
    }

}