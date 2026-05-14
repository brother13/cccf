<?php

namespace app\cccf\model;

use think\Db;

class Dkp extends Common
{
    // 表名（不含前缀，前缀由数据库配置自动添加）
    const TABLE_NAME = 'shoukuan_dkp';

    /**
     * 主入口方法
     *
     * @param string $action
     * @param array $param
     * @return array
     */
    public function index($action = '', $param = [])
    {
        $rt = $this->_rt();

        switch ($action) {
            case 'list':
                $rt = $this->getList($param);
                break;
            case 'detail':
                $rt = $this->getDetail($param);
                break;
            default:
                $rt['message'] = '操作不存在';
                break;
        }

        return $rt;
    }

    /**
     * 获取待开收据列表
     *
     * @param array $param
     * @return array
     */
    public function getList($param = [])
    {
        $rt = $this->_rt();

        // 分页参数
        $page = isset($param['page']) ? intval($param['page']) : 1;
        $pagesize = isset($param['pagesize']) ? intval($param['pagesize']) : 10;

        // 查询字段
        $fields = [
            'ah',
            'dsr',
            'je',
            'dzdate',
            'note',
            'cbr'
        ];

        // 构建查询条件
        $where = [];

        // 默认只查询当前用户的数据
        // 如果用户有 DKP_QUERY_ALL 权限，则可以查询所有人的数据
        $canQueryAll = $this->checkAuth('DKP_QUERY_ALL');
        if (!$canQueryAll) {
            $where['cbr'] = $this->username;
        }

        // 案号模糊查询
        if (!empty($param['keyword'])) {
            $where['ah'] = ['like', '%' . $param['keyword'] . '%'];
        }

        // 承办人查询（仅在有权限时生效）
        if ($canQueryAll && !empty($param['cbr'])) {
            $where['cbr'] = ['like', '%' . $param['cbr'] . '%'];
        }

        // 当事人查询
        if (!empty($param['dsr'])) {
            $where['dsr'] = ['like', '%' . $param['dsr'] . '%'];
        }

        // 到账日期范围查询
        if (!empty($param['starttime']) || !empty($param['endtime'])) {
            $dateWhere = $this->_where_date(
                $param['starttime'] ?? '',
                $param['endtime'] ?? '',
                false
            );
            if ($dateWhere) {
                $where['dzdate'] = $dateWhere;
            }
        }

        try {
            // 获取总数
            $total = $this->getdb(self::TABLE_NAME)->where($where)->count();

            // 获取列表数据
            $list = $this->getdb(self::TABLE_NAME)
                ->where($where)
                ->field($fields)
                ->order('dzdate desc, ah asc')
                ->page($page, $pagesize)
                ->select();

            $rt['code'] = self::CODE_SUCCESS;
            $rt['message'] = '查询成功';
            $rt['data'] = [
                'total' => intval($total),
                'items' => $list ?: [],
                'page' => intval($page),
                'pagesize' => intval($pagesize)
            ];
        } catch (\Exception $e) {
            $rt['message'] = '查询失败: ' . $e->getMessage();
            $rt['data'] = [
                'total' => 0,
                'items' => [],
                'page' => $page,
                'pagesize' => $pagesize
            ];
        }

        return $rt;
    }

    /**
     * 获取待开收据详情
     *
     * @param array $param
     * @return array
     */
    public function getDetail($param = [])
    {
        $rt = $this->_rt();

        if (empty($param['id'])) {
            $rt['message'] = '参数错误：缺少ID';
            return $rt;
        }

        $id = intval($param['id']);

        try {
            $fields = [
                'ah',
                'dsr',
                'je',
                'dzdate',
                'note',
                'cbr'
            ];

            $data = $this->getdb(self::TABLE_NAME)
                ->field($fields)
                ->where(['id' => $id])
                ->find();

            if ($data) {
                $rt['code'] = self::CODE_SUCCESS;
                $rt['data'] = $data;
            } else {
                $rt['message'] = '记录不存在';
            }
        } catch (\Exception $e) {
            $rt['message'] = '查询失败: ' . $e->getMessage();
        }

        return $rt;
    }
}
