<?php
/**
 * 用户相关接口文件
 */
include 'base.php';
include dirname(__FILE__).'/../../Application/User/Common/common.php';
class user extends base{
	public function __construct(){
		define('UC_AUTH_KEY', '!AytOj^6.kwd@M`Nepv1XgH:q*(VrZIbS9o$GscB');
	}
	
	/**
	 * 检查是否已登陆
	 */
	public function is_login($param){
		
	}
	
	/**
	 * 登陆
	 */
	public function login($param){
		if($param['phone'] && $param['password']){
		    $phone = trim($param['phone']);
		    $password = trim($param['password']);
		    //$result = $this->user_model->login($phone,$password,3);
		    $result = $this->check_login($phone,$password);
		    switch($result){
		        case -1:
		            $this->getResponse('', '301');
		            break;
		        case -2:
		            $this->getResponse('', '302');
		            break;
		        default:
		            $info = D('Member')->info($result,'uid,subbranch_id,score,gold,login_score');
		            	
		            //登录时积分奖励
		            $uid = intval($info['uid']);
		            $login_info = M('ucenter_member')->where('id='.$uid)->find();
		            $login_times = $login_info['login_times'];
		            $current_time = date('Ymd',time());
		            $last_login_time = date('Ymd',$login_info['last_login_time']);
		            if($current_time != $last_login_time){  //当天未登陆过
		                $detl_time = $current_time-$last_login_time;
		                if($detl_time == 1){  //连续登陆
		                    if($login_times == 4){
		                        $mem['login_times'] = 0;
		                        //积分+10
		                        $mem['score'] = $info['score']+10;
		                        $mem['login_score'] = $info['login_score']+10;
		                    }else {
		                        //+1
		                        $mem['login_times'] = array('exp', '`login_times`+1');
		                        //积分+1
		                        $mem['score'] = $info['score']+1;
		                        $mem['login_score'] = $info['login_score']+1;
		                    }
		                }else{  //非连续登陆
		                    $mem['login_times'] = 1;
		                    //积分+1
		                    $mem['score'] = $info['score']+1;
		                    $mem['login_score'] = $info['login_score']+1;
		                }
		            }
		            unset($info['login_score']);
		            $mem['last_login_time'] = $u_mem['last_login_time'] = time();
		            $mem['last_login_ip'] = $u_mem['last_login_ip'] = get_client_ip(1);
		            $mem['login'] = array('exp', '`login`+1');
		            M('ucenter_member')->where('id='.$uid)->save($u_mem);
		            M('member')->where('uid='.$uid)->save($mem);
		            	
		            $this->getResponse($info, '0');
		    }
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 私有方法-验证登陆
	 */
	private function check_login($phone,$passwd){
	    $map['mobile'] = trim($phone);
	    $passwd = trim($passwd);
	    $info = M('ucenter_member')->where($map)->find();
	    if($info){
	        $u_passwd = $info['password'];
	        if(think_ucenter_md5($passwd, UC_AUTH_KEY) !== $u_passwd){
	            return -2;
	        }else{
	            return $info['id'];
	        }
	    }else{
	        return -1;
	    }
	}
	
	/**
	 * 用户个人信息
	 */
	public function info($param){
		if($param['uid']){
			$uid = intval($param['uid']);
			$field = 'a.uid,a.head,a.truename,b.phone,a.sex,a.job,a.subbranch_id';
			$info = M()->table(C('DB_PREFIX').'member a')->join(C('DB_PREFIX').'ucenter_member b on a.uid=b.uid')
			           ->field($field)->where('uid='.$uid)->find();
			if($info){
				$info['sex'] = $info['sex'] == '0' ? '女' : '男';
				$info['head'] = $info['head'];
				$subbranch = D('Subbranch')->getSubbranchDetail($info['subbranch_id']);
				unset($info['subbranch_id']);
				$info['subbrabch_name'] = $subbranch['name'];
			}
			$this->getResponse($info?$info:'', '0');
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 学习记录
	 */
	public function study($param){
		if($param['uid']){
			$uid = intval($param['uid']);
			$map['a.uid'] = $uid;
			$field = 'a.course_id,a.status,b.title,b.type,b,a.update_time,b.course_ico,b.video_url,b.gold';
			$records = M()->table(C('DB_PREFIX').'course_record a')->join(C('DB_PREFIX').'course b on a.course_id=b.course_id')
			           ->field($field)->where($map)->order('a.update_time desc')->select();

			if($records){
				$status_map = array(1=>'未考试',2=>'未通过',3=>'已通过');
				foreach($records as $k=>$v){
					$records[$k]['status'] = $status_map[$v['status']];
					$type = M('course_type')->where('id='.$v['type'])->find();
					$records[$k]['type'] = $type['name'];
					$records[$k]['update_time'] = date('Y-m-d',$v['update_time']);
				}
			}
			$this->getResponse($records?$records:array(), '0');
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 我的关注
	 */
	public function focus($param){
		if($param['uid']){
			$uid = intval($param['uid']);
			$map['uid'] = $uid;
			
			/*$field = 'a.course_id,a.focus_time,b.title,b.type,b,b.course_ico,b.video_url';
			$list = M()->table(C('DB_PREFIX').'course_focus a')->join(C('DB_PREFIX').'course b on a.course_id=b.course_id')
			           ->field($field)->where($map)->order('a.focus_time desc')->select();*/
			
			$field = 'item_id,focus_time,focus_type';
			$list = M('user_focus')->where($map)->order('focus_time desc')->select();
			if($list){
				foreach($list as $k=>&$v){
					$focus_type = intval($v['focus_type']);
					if($focus_type == 1){
						$couse = M('course')->where('id='.$v['item_id'])->field('title,type,course_ico,video_url')->find();
						$type = M('course_type')->where('id='.$couse['type'])->find();
						$v['course_type'] = $type['name'];
						$v['item_title'] = $couse['title'];
						$v['item_ico'] = $couse['course_ico'];
						$v['item_url'] = $couse['video_url'];
					}else{
						$act = M('activity')->where('id='.$v['item_id'])->field('title,act_ico,wap_link')->find();
						$type = M('course_type')->where('id='.$couse['type'])->find();
						$v['course_type'] = '';
						$v['item_title'] = $act['title'];
						$v['item_ico'] = $act['act_ico'];
						$v['item_url'] = $act['wap_link'];
					}
					$v['focus_time'] = date('Y-m-d',$v['focus_time']);
				}
			}
			$this->getResponse($list?$list:array(), '0');
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 我的金币
	 */
	public function gold($param){
		if($param['uid']){
			$uid = intval($param['uid']);
			//获得金币记录：考试通过一类课程
			$map['uid'] = $uid;
			$map['level'] = ENTERPRISE;
			$map['status'] = 3;
			$course_record = M('course_record')->where($map)->field('course_id,update_time')->order('update_time desc')->select();
			$course_record = $this->format_record($course_record,'pass',1);
			
			//消耗金币记录：兑换礼品和金币
			$map = array();
			$map['uid'] = $uid;
			$map['pay_type'] = 2;
			$gift_record = M('gift_record')->where($map)->field('oid,time,gold,score,gift_type,pay_type')->order('time desc')->select();
			$gift_record = $this->format_record($gift_record,'gift',2);
			
			$record = array_merge($course_record,$gift_record);
			//根据时间倒序对数组重新排序
			$data = $this->bubble_sort($record,'time');
			$this->getResponse($data, '0');		
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 我的积分
	 */
	public function score($param){
	    if($param['uid']){
	        $uid = intval($param['uid']);
	        
	        /** 获得积分记录 **/
	        //【登陆】
	        $map['uid'] = $uid;
	        $login_record = M('member')->where($map)->field('login_score,last_login_time')->select();
	        $login_record = $this->format_record($login_record,'login');
	        //【金币兑换】
	        $map = array();
	        $map['uid'] = $uid;
	        $map['pay_type'] = 2;
	        $map['gift_type'] = 2;
	        $gold_record = M('gift_record')->where($map)->field('oid,time,gold,score,gift_type,pay_type')->order('time desc')->select();
	        $gold_record = $this->format_record($gift_record,'gift',1);
	        //【评论】
	        $map = array();
	        $map['uid'] = $uid;
	        $comment_record = M('course_comment')->where($map)->field('course_id,comment_time')->order('comment_time desc')->select();
	        $comment_record = $this->format_record($comment_record,'comment',1);
	        
	        /** 消耗积分 **/
	        //【观看二类视频】
	        $map = array();
			$map['uid'] = $uid;
			$map['level'] = PLATFORM;
			$course_record = M('course_record')->where($map)->field('course_id,update_time')->order('update_time desc')->select();
			$course_record = $this->format_record($course_record,'watch',2);
			//【兑换礼品】
			$map = array();
			$map['uid'] = $uid;
			$map['pay_type'] = 1;
			$map['gift_type'] = 1;
			$gift_record = M('gift_record')->where($map)->field('oid,time,gold,score,gift_type,pay_type')->order('time desc')->select();
			$gift_record = $this->format_record($gift_record,'gift',2);
			
			$record = array_merge($login_record,$gold_record,$comment_record,$course_record,$gift_record);
			//根据时间倒序对数组重新排序
			$data = $this->bubble_sort($record,'time');
			$this->getResponse($data, '0');
	    }else{
	        $this->getResponse('', '999');
	    }
	}
	
	/**
	 * 私有方法-格式化金币积分记录统一方法
	 */
	private function format_record($data,$type,$plus){
	    $format_data = array();
	    if($data){
	        switch($type){
	            //通过一类课程
	            case 'pass':
	                foreach($data as $k=>$v){
	                    $course = M('course')->where('id='.$v['course_id'])->field('title,gold')->find();
	                    $format_data[$k]['desc'] = '通过-'.trim($course['title']);
	                    $format_data[$k]['gold'] = intval($course['gold']);
	                    $format_data[$k]['type'] = $plus;
	                    $format_data[$k]['time'] = date('Y-m-d H:i:s',$v['update_time']);
	                }
	            break;
	            //兑换
	            case 'gift':
	                foreach($data as $k=>$v){
	                    $gift = M('order')->where('id='.$v['oid'])->field('name')->find();
	                    $gift_type = $v['gift_type'] == 1 ? '普通' : '金币';
	                    $format_data[$k]['desc'] = "兑换{$gift_type}礼品-".trim($gift['name']);
	                    $format_data[$k]['gold'] = $v['pay_type'] == 1 ? intval($v['score']) : intval($v['gold']);
	                    $format_data[$k]['type'] = $plus;
	                    $format_data[$k]['time'] = date('Y-m-d H:i:s',$v['time']);
	                }
	            break;
	            //观看二类课程
	            case 'watch':
	                foreach($data as $k=>$v){
	                    $course = M('course')->where('id='.$v['course_id'])->field('title,score')->find();
	                    $format_data[$k]['desc'] = '通过-'.trim($course['title']);
	                    $format_data[$k]['gold'] = intval($course['score']);
	                    $format_data[$k]['type'] = $plus;
	                    $format_data[$k]['time'] = date('Y-m-d H:i:s',$v['update_time']);
	                }
	            break;
	            //登陆
	            case 'login':
	                foreach($data as $k=>$v){
	                    $format_data[$k]['desc'] = '登录签到';
	                    $format_data[$k]['gold'] = intval($v['login_score']);
	                    $format_data[$k]['type'] = $plus;
	                    $format_data[$k]['time'] = date('Y-m-d H:i:s',$v['last_login_time']);
	                }
	            break;
	            //评论
	            case 'comment':
	                foreach($data as $k=>$v){
	                    $course = M('course')->where('id='.$v['course_id'])->field('title')->find();
	                    $format_data[$k]['desc'] = '评论-'.trim($course['title']);
	                    $format_data[$k]['gold'] = 5;
	                    $format_data[$k]['type'] = $plus;
	                    $format_data[$k]['time'] = date('Y-m-d H:i:s',$v['comment_time']);
	                }
	            break;
	        }
	    }
	    return $format_data;
	}
	
	/**
	 * 私有方法-冒泡排序
	 */
	private function bubble_sort($data,$key){
		if($data){
			$count = count($data);
			for($i=0;$i<$count;$i++){
				for($j=$i+1;$j<$count;$j++){
					if($data[$i][$key] < $data[$j][$key]){
						$min = $data[$i];
						$data[$i] = $data[$j];
						$data[$j] = $min;
					}
				}
			}
		}
		return $data;
	}
	
	/**
	 * 积分商城接口
	 */
	public function shop($param){
		$map['is_hide'] = 0;
		$order = 'sort desc';
		$field = 'id,name,type,score,order_ico';
		$page = intval($param['page']) ? intval($param['page']) : 1;
		$page_size = intval($param['page_size']) ? intval($param['page_size']) : 5;
		$data = M('order')->where($where)->field($field)->order($order)->page($page)->limit($page_size)->select();
		$this->getResponse($data?$data:array(), '0');
	}
	
	/**
	 * 兑换礼品
	 */
	public function gift($param){
		if($param['uid'] && $param['oid']){
			$uid = intval($param['uid']);
			$oid = intval($param['oid']);
			$gift_score = intval($param['score']);
			$gift_type = intval($param['gift_type']) ? intval($param['gift_type']) : 1;
			$pay_type = intval($param['pay_type']) ? intval($param['pay_type']) : 1;
			
			$user_info = D('Member')->info($uid,'score,gold');
			if($user_info){
				$user_score = intval($user_info['score']);
				$user_gold = intval($user_info['gold']);
				
				switch($gift_type){
					//普通礼品
					case 1:
						if($pay_type == 1){ //使用积分支付
							if($user_score >= $gift_score){
								$save['score'] = array('exp',"`score`-{$gift_score}");
								$res = M('member')->where('uid='.$uid)->save($save);
								//添加兑换记录
								$add['uid'] = $uid;
								$add['oid'] = $oid;
								$add['score'] = $gift_score;
								$add['gold'] = 0;
								$add['pay_type'] = 1;
								$add['gift_type'] = 1;
								$add['time'] = time();
								M('gift_record')->add($add);
								$this->getResponse('', '0');
							}else{
								$this->getResponse('', '305');
							}
						}else{ //使用金币支付
							$equal_gold = ceil($gift_score/10);
							if($user_gold >= $equal_gold){
								$save['gold'] = array('exp',"`gold`-{$equal_gold}");
								$res = M('member')->where('uid='.$uid)->save($save);
								//添加兑换记录
								$add['uid'] = $uid;
								$add['oid'] = $oid;
								$add['score'] = 0;
								$add['gold'] = $equal_gold;
								$add['pay_type'] = 2;
								$add['gift_type'] = 1;
								$add['time'] = time();
								M('gift_record')->add($add);
								$this->getResponse('', '0');
							}else{
								$this->getResponse('', '306');
							}
						}
					break;
					//金币礼品
					case 2:
						$equal_gold = ceil($gift_score/10);//积分转换为金币
						if($user_gold >= $equal_gold){
							$save['score'] = array('exp',"`score`+{$gift_score}");
							$save['gold'] = array('exp',"`gold`-{$equal_gold}");
							$res = M('member')->where('uid='.$uid)->save($save);
							//添加兑换记录
							$add['uid'] = $uid;
							$add['oid'] = $oid;
							$add['score'] = 0;
							$add['gold'] = $equal_gold;
							$add['pay_type'] = 1;
							$add['gift_type'] = 2;
							$add['time'] = time();
							M('gift_record')->add($add);
							$this->getResponse('', '0');
						}else{
							$this->getResponse('', '306');
						}
					break;
				}
			}else{
				$this->getResponse('', '301');
			}
			
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 修改密码
	 */
	public function update($param){
		if($param['old_password'] && $param['new_password'] && $param['uid']){
			$map['uid'] = intval($param['uid']);
			$old_password = trim($param['old_password']);
			$user_info = M('ucenter_member')->where($map)->find();
			if($user_info){
				if(think_ucenter_md5($old_password, UC_AUTH_KEY) !== $user_info['password']){
					$this->getResponse('', '303');
				}else{
					$save['password'] = think_ucenter_md5(trim($param['new_password']),UC_AUTH_KEY);
					$res = M('ucenter_member')->where($map)->save($save);
					$this->getResponse('', $res?'0':'304');
				}
			}else{
				$this->getResponse('', '301');
			}
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 退出
	 */
	public function logout($param){
		
	}
}