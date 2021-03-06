<?php
/**
 * 接口基类
 */
class base{
	
	public $course_types = array(
        1 => '企业产品培训',
        2 => '医药养生保健',
        3 => '药师备考培训',
        4 => '连锁内部提高',
    );
	
	/**
	 * 接口返回数据输出
	 * @param arrsy $data 返回数据
	 * @param string $state 返回状态码
	 * @param bool $is_state 是否是状态码 默认true
	 * @return string json数据
	 */
	public function getResponse($data,$state,$is_state=true){
		$response['state'] = $state;
		$response['msg'] = $is_state ? $error_config[$state] : $state;
		if($is_state){
			include dirname(__FILE__).'/../error_config.php';
			$response['msg'] = $error_config[$state];
		}else{
			$response['msg'] = $state;
		}
		$response['data'] = $data;
		echo json_encode($response);
	}
}