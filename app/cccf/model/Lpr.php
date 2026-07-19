<?php

namespace app\cccf\model;

use think\Db;

/**
 * LPR利率模型
 */
class Lpr extends Common
{
    protected $table = 'lpr_rate';

    public function index($action = '', $data = [])
    {
        switch ($action) {
            case 'list':
                return $this->getList($data);
            case 'getRateByDate':
                return $this->getRateByDate($data);
            case 'getLatestRate':
                return $this->getLatestRate($data);
            case 'getRateByDateRange':
                return $this->getRateByDateRange($data);
            default:
                return $this->error('未知操作: ' . $action);
        }
    }

    protected function success($message = '操作成功', $data = [], $total = null)
    {
        $rt = [
            'code' => self::CODE_SUCCESS,
            'message' => $message,
            'data' => $data
        ];
        if ($total !== null) {
            $rt['total'] = $total;
        }
        return $rt;
    }

    protected function error($message = '操作失败', $data = [])
    {
        return [
            'code' => self::CODE_ERROR,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * 获取所有LPR利率列表
     */
    public function getList($data)
    {
        $page = $data['page'] ?? 1;
        $pageSize = $data['pagesize'] ?? 100;

        $total = Db::name($this->table)->count();

        $list = Db::name($this->table)
            ->order('publish_date', 'desc')
            ->page($page, $pageSize)
            ->select();

        return $this->success('获取成功', $list, $total);
    }

    /**
     * 获取指定日期的LPR利率
     * 返回该日期或之前最近一次的LPR利率
     */
    public function getRateByDate($data)
    {
        $date = $data['date'] ?? date('Y-m-d');

        $record = Db::name($this->table)
            ->where('publish_date', '<=', $date)
            ->order('publish_date', 'desc')
            ->find();

        if (!$record) {
            // 如果没有找到，返回最早的记录
            $record = Db::name($this->table)
                ->order('publish_date', 'asc')
                ->find();
        }

        return $this->success('获取成功', $record);
    }

    /**
     * 获取最新LPR利率
     */
    public function getLatestRate($data)
    {
        $record = Db::name($this->table)
            ->order('publish_date', 'desc')
            ->find();

        return $this->success('获取成功', $record);
    }

    /**
     * 根据日期范围获取LPR利率
     */
    public function getRateByDateRange($data)
    {
        $startDate = $data['startDate'] ?? '';
        $endDate = $data['endDate'] ?? '';

        $query = Db::name($this->table);

        if ($startDate) {
            $query->where('publish_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('publish_date', '<=', $endDate);
        }

        $list = $query->order('publish_date', 'asc')->select();

        return $this->success('获取成功', $list);
    }

}
