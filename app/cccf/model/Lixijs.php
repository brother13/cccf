<?php

namespace app\cccf\model;

use think\Db;
use think\Model;

/**
 * 利息计算模型
 */
class Lixijs extends Common
{
    protected $table = 'lixijs_history';

    /**
     * 保存计算记录
     */
    public function saveCalculation($data)
    {
        // 准备保存的数据
        $saveData = [
            'case_no' => $data['caseNo'] ?? '',
            'parties' => $data['parties'] ?? '',
            'principal' => $data['principal'] ?? 0,
            'rate' => $data['rate'] ?? 0,
            'rate_type' => $data['rateType'] ?? 'year',
            'calc_type' => $data['calcType'] ?? 'simple',
            'start_date' => $data['startDate'] ?? '',
            'end_date' => $data['endDate'] ?? '',
            'judgment_date' => $data['judgmentDate'] ?? '',
            'due_date' => $data['dueDate'] ?? '',
            'calc_delay_interest' => $data['calcDelayInterest'] ? 1 : 0,
            'days' => $data['result']['days'] ?? 0,
            'normal_interest' => $data['result']['normalInterest'] ?? 0,
            'delay_interest' => $data['result']['delayInterest'] ?? 0,
            'delay_days' => $data['result']['delayDays'] ?? 0,
            'total_repayment' => $data['result']['totalRepayment'] ?? 0,
            'remaining_principal' => $data['result']['remainingPrincipal'] ?? 0,
            'total_amount' => $data['result']['totalAmount'] ?? 0,
            'repayment_list' => json_encode($data['repaymentList'] ?? []),
            'details' => json_encode($data['result']['details'] ?? []),
            'create_by' => $this->username,
            'create_time' => date('Y-m-d H:i:s')
        ];

        $id = Db::name($this->table)->insertGetId($saveData);

        return $this->success('保存成功', ['id' => $id]);
    }

    /**
     * 获取计算历史列表
     */
    public function getHistory($data)
    {
        $page = $data['page'] ?? 1;
        $pageSize = $data['pagesize'] ?? 10;
        $caseNo = $data['caseNo'] ?? '';
        $parties = $data['parties'] ?? '';
        $startDate = $data['startDate'] ?? '';
        $endDate = $data['endDate'] ?? '';

        $query = Db::name($this->table)
            ->where('create_by', $this->username);

        if ($caseNo) {
            $query->where('case_no', 'like', '%' . $caseNo . '%');
        }

        if ($parties) {
            $query->where('parties', 'like', '%' . $parties . '%');
        }

        if ($startDate) {
            $query->where('create_time', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate) {
            $query->where('create_time', '<=', $endDate . ' 23:59:59');
        }

        $total = $query->count();

        $list = $query
            ->order('create_time', 'desc')
            ->page($page, $pageSize)
            ->select();

        // 格式化数据
        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item['id'],
                'caseNo' => $item['case_no'],
                'parties' => $item['parties'],
                'principal' => floatval($item['principal']),
                'rate' => floatval($item['rate']),
                'rateType' => $item['rate_type'],
                'calcType' => $item['calc_type'],
                'startDate' => $item['start_date'],
                'endDate' => $item['end_date'],
                'days' => $item['days'],
                'normalInterest' => floatval($item['normal_interest']),
                'delayInterest' => floatval($item['delay_interest']),
                'calcDelayInterest' => $item['calc_delay_interest'] == 1,
                'totalRepayment' => floatval($item['total_repayment']),
                'remainingPrincipal' => floatval($item['remaining_principal']),
                'totalAmount' => floatval($item['total_amount']),
                'repaymentList' => json_decode($item['repayment_list'], true),
                'calcTime' => $item['create_time']
            ];
        }

        return $this->success('获取成功', $result, $total);
    }

    /**
     * 删除计算记录
     */
    public function deleteCalculation($data)
    {
        $id = $data['id'] ?? 0;

        if (!$id) {
            return $this->error('参数错误');
        }

        // 验证所有权
        $record = Db::name($this->table)
            ->where('id', $id)
            ->where('create_by', $this->username)
            ->find();

        if (!$record) {
            return $this->error('记录不存在或无权限');
        }

        Db::name($this->table)->where('id', $id)->delete();

        return $this->success('删除成功');
    }
}
