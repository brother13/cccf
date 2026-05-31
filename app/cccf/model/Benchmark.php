<?php

namespace app\cccf\model;

use think\Db;

/**
 * 贷款基准利率模型
 */
class Benchmark extends Common
{
    protected $table = 'benchmark_rate';

    public function index($action = '', $data = [])
    {
        switch ($action) {
            case 'list':
                return $this->getList($data);
            case 'getRateByDate':
                return $this->getRateByDate($data);
            case 'getRateByPeriod':
                return $this->getRateByPeriod($data);
            case 'getLatestRate':
                return $this->getLatestRate($data);
            case 'syncRates':
                return $this->syncRates($data);
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
     * 获取基准利率列表
     */
    public function getList($data)
    {
        $page = $data['page'] ?? 1;
        $pageSize = $data['pagesize'] ?? 100;

        $query = Db::name($this->table)
            ->order('publish_date', 'desc');

        $total = $query->count();

        $list = $query
            ->page($page, $pageSize)
            ->select();

        return $this->success('获取成功', $list, $total);
    }

    /**
     * 获取指定日期的基准利率
     * 返回该日期或之前最近一次的利率
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
     * 根据期限类型获取利率
     * period_type: 6m(六个月内), 6m_1y(六个月至一年), 1y_3y(一至三年), 3y_5y(三至五年), 5y_plus(五年以上), 1y(一年以内), 1y_5y(一至五年)
     */
    public function getRateByPeriod($data)
    {
        $date = $data['date'] ?? date('Y-m-d');
        $periodType = $data['periodType'] ?? '1y_5y';

        $record = Db::name($this->table)
            ->where('publish_date', '<=', $date)
            ->order('publish_date', 'desc')
            ->find();

        if (!$record) {
            return $this->error('未找到基准利率数据');
        }

        $rate = null;
        $periodName = '';

        switch ($periodType) {
            case '6m':
                $rate = $record['period_6m'];
                $periodName = '六个月内';
                break;
            case '6m_1y':
                $rate = $record['period_6m_1y'];
                $periodName = '六个月至一年';
                break;
            case '1y_3y':
                $rate = $record['period_1y_3y'];
                $periodName = '一至三年';
                break;
            case '3y_5y':
                $rate = $record['period_3y_5y'];
                $periodName = '三至五年';
                break;
            case '5y_plus':
                $rate = $record['period_5y_plus'];
                $periodName = '五年以上';
                break;
            case '1y':
                $rate = $record['period_1y'];
                $periodName = '一年以内';
                break;
            case '1y_5y':
                $rate = $record['period_1y_5y'];
                $periodName = '一至五年';
                break;
            default:
                $rate = $record['period_1y_5y'];
                $periodName = '一至五年';
        }

        return $this->success('获取成功', [
            'publish_date' => $record['publish_date'],
            'period_type' => $periodType,
            'period_name' => $periodName,
            'rate' => $rate
        ]);
    }

    /**
     * 获取最新基准利率
     */
    public function getLatestRate($data)
    {
        $record = Db::name($this->table)
            ->order('publish_date', 'desc')
            ->find();

        return $this->success('获取成功', $record);
    }

    /**
     * 批量同步基准利率（存在即更新，不存在即新增）
     */
    public function syncRates($data)
    {
        $rows = $data['rows'] ?? [];
        if (!is_array($rows) || count($rows) === 0) {
            return $this->error('参数错误：rows 不能为空');
        }

        Db::startTrans();
        try {
            $inserted = 0;
            $updated = 0;

            foreach ($rows as $item) {
                $publishDate = $item['date'] ?? '';
                if (empty($publishDate)) {
                    continue;
                }

                $saveData = [
                    'publish_date' => $publishDate,
                    'period_6m' => isset($item['6m']) ? floatval($item['6m']) : null,
                    'period_6m_1y' => isset($item['6m_1y']) ? floatval($item['6m_1y']) : null,
                    'period_1y_3y' => isset($item['1y_3y']) ? floatval($item['1y_3y']) : null,
                    'period_3y_5y' => isset($item['3y_5y']) ? floatval($item['3y_5y']) : null,
                    'period_5y_plus' => isset($item['5y_plus']) ? floatval($item['5y_plus']) : null,
                    'period_1y' => isset($item['1y']) ? floatval($item['1y']) : null,
                    'period_1y_5y' => isset($item['1y_5y']) ? floatval($item['1y_5y']) : null
                ];

                $exists = Db::name($this->table)->where('publish_date', $publishDate)->find();
                if ($exists) {
                    Db::name($this->table)->where('publish_date', $publishDate)->update($saveData);
                    $updated++;
                } else {
                    Db::name($this->table)->insert($saveData);
                    $inserted++;
                }
            }

            Db::commit();
            return $this->success('同步成功', [
                'inserted' => $inserted,
                'updated' => $updated,
                'total' => count($rows)
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('同步失败：' . $e->getMessage());
        }
    }
}
