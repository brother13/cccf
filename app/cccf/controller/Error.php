<?php
namespace app\cccf\controller;


class Error
{
   const CODE_SUCCESS = 20000;
   const CODE_ERROR = 0;

  

    public function index(Request $request)
    {
        $name = $request->controller();
        $rt = [];
        $rt['code']=0;
        $rt['message'] = "【{$name}】控制器不存在";
        $rt['data']="";
		return $rt;
    }

	
}
