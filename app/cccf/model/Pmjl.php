<?php

/**
 * 拍卖记录数据保存接口
 * 
 */

namespace app\cccf\model;

use \think\Db;
use \think\View;
use \think\Debug;


class Pmjl extends Common
{
    const ACTION = "pmjl";
    const COMMENT = "拍卖记录";

    const TABLE_PMJL = "pmjl";




    /**
     * 入口程序
     *
     * @param string $action
     * @param array $data
     * @return void
     */
    public function index($action = '', $data = [])
    {
        $rt = $this->_rt();

        switch ($action) {

            case 'getList':
                $rt = $this->getList($data);
                break;

            default:
                $rt['message'] = "操作【/" . self::ACTION . "/{$action}】并不存在！";
        }

        return $rt;
    }


    /**
     * 保存拍卖记录数据
     */
    public function savedata($data = [])
    {
        $rt = $this->_rt();





        if (empty($data) || !is_array($data)) {
            $rt['message'] = "没有数据需要保存！";
            return json($rt);
        }

        $nowTime = getNowTime();

        // 获取所有非空承办人
        $cbrList = [];
        foreach ($data as $row) {
            $cbr = trim($row['cbr'] ?? '');
            if (!empty($cbr) && !in_array($cbr, $cbrList)) {
                $cbrList[] = $cbr;
            }
        }

        $insertCount = 0;
        $deleteCount = 0;

        // 开启事务
        try {
            // 先删除这些承办人的旧记录
            if (!empty($cbrList)) {

                $where = [];
                $where['cbr'] = ['in', $cbrList];
                $deleteCount += $this->getdb(self::TABLE_PMJL)->where($where)->delete();
            }

            // 批量插入新数据
            $insertData = [];
            foreach ($data as $row) {
                $pmkssj = $row['pmkssj'] ?? '';
                $pmjssj = $row['pmjssj'] ?? '';

                $row['qpj'] = str_replace(",",'',$row['qpj']??'');
                $row['cjj'] = str_replace(",",'',$row['cjj']??'');
                $insertRow = [];
                $insertRow['rec_index'] = intval($row['index'] ?? 0);
                $insertRow['caseinfo'] = $row['caseinfo'] ?? '';
                $insertRow['fyname'] = $row['fyname'] ?? '';
                $insertRow['cbr'] = $row['cbr'] ?? '';
                $insertRow['status'] = $row['status'] ?? '';
                $insertRow['bdmc'] = $row['bdmc'] ?? '';
                $insertRow['dsr'] = $row['dsr'] ?? '';
                $insertRow['pmjd'] = $row['pmjd'] ?? '';
                $insertRow['pmpt'] = $row['pmpt'] ?? '';
                $insertRow['pmkssj'] = !empty($pmkssj) ? date('Y-m-d H:i:s', strtotime($pmkssj)) : null;
                $insertRow['pmjssj'] = !empty($pmjssj) ? date('Y-m-d H:i:s', strtotime($pmjssj)) : null;
                $insertRow['bmrs'] = intval($row['bmrs'] ?? 0);
                $insertRow['qpj'] = $row['qpj'] ?? '';
                $insertRow['cjj'] = $row['cjj'] ?? '';
                $insertRow['createtime'] = $nowTime;
                $insertRow['updatetime'] = $nowTime;

                $insertData[] = $insertRow;
            }

            if (!empty($insertData)) {
                // 分批插入，每批100条
                $chunks = array_chunk($insertData, 100);
                foreach ($chunks as $chunk) {
                    $this->getdb(self::TABLE_PMJL)->insertAll($chunk);
                    $insertCount += count($chunk);
                }
            }



            $newdata = [
                'insert_count' => $insertCount,
                'delete_count' => $deleteCount,
                'cbr_list' => $cbrList
            ];
            $rt['code'] = self::CODE_SUCCESS;
            $rt['message'] = '保存成功';
            $rt['total'] = $insertCount;
            $rt['data'] = $newdata;
        } catch (\Exception $e) {
            $rt['message'] = "保存失败：" . $e->getMessage();
        }

        return $rt;
    }

    /**
     * 查询拍卖记录列表
     */
    public function getList($param = [])
    {
        $rt = $this->_rt();

        $cbr = $param['cbr'] ?? '';
        $page = $param['page'] ?? 1;
        $pagesize = $param['pagesize'] ?? 10;

        $sort = $param['sort'] ?? '';

        $where = [];
        if (!empty($cbr)) {
            $where['cbr'] = $cbr;
        }

        $order = "id";
        if (!empty($sort)) {
            $order = $sort;
        }
        $total = $this->getdb(self::TABLE_PMJL)->where($where)->count();

        $data = $this->getdb(self::TABLE_PMJL)->where($where)->order($order)->page($page, $pagesize)->select();


        $newdata = [];
        $newdata['total'] = $total;
        $newdata['items'] = $data;

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['total'] = $total;
        $rt['data'] = $newdata;

        return $rt;
    }
}
