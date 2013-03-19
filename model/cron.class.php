<?php

/*
 * Copyright (C) xiuno.com
 */

class cron extends base_model {

	function __construct(&$conf) {
		parent::__construct($conf);
		//set_time_limit(600);
		//ignore_user_abort(true);
	}
	
	public function run() {
		$cron_1_next_time = $this->conf['cron_1_next_time'];
		$cron_2_next_time = $this->conf['cron_2_next_time'];
		
		$time = $_SERVER['time'];
			
		// 跨多台WEB的锁。
		if($this->runtime->get('cronlock') == 1) {
			// 判断锁是否过期?
			if($time > $cron_1_next_time + 30) {
				// 过期则解锁
				//$this->runtime->set('cronlock', 0);
			} else {
				// 否则表示其他进程正在执行
				return;
			}
		}
		$this->runtime->set('cronlock', 1);
	
		// 5 分钟执行一次
		if($time > $cron_1_next_time) {
			$nexttime = $time + 300;
			$this->runtime->xset('cron_1_next_time', $nexttime);
			log::write('cron_1_next_time:'.date('Y-n-j H:i', $nexttime), 'cron.php');
			
			// gc online table
			$this->online->gc();
			// $this->runtime->xsave();
		}
		
		// execute on 0:00 perday.
		if($time > $cron_2_next_time) {
			// update the next time of cron
			$nexttime = $_SERVER['time_today'] + 86400;
			$this->runtime->xset('cron_2_next_time', $nexttime);
			log::write('cron_2_next_time:'.date('Y-n-j H:i', $nexttime), 'cron.php');
			
			// set todayposts zero.
			$forumlist = $this->forum->get_list();
			foreach($forumlist as $forum) {
				$forum['todayposts'] = 0;
				$this->forum->update($forum);
				$this->mcache->clear('forum', $forum['fid']);
			}
			
			// 统计
			$arr = explode(' ', $_SERVER['time_fmt']);
			list($y, $n, $d) = explode('-', $arr[0]);
			
			// windows 下的锁有可能会出问题。保险起见，判断一下。
			$stat = $this->stat->read($y, $n, $d);
			if(empty($stat)) {
				$threads = $this->thread->count();
				$posts = $this->post->count();
				$users = $this->user->count();
				$stat = array (
					'year'=>$y,
					'month'=>$n,
					'day'=>$d,
					'threads'=>$threads,
					'posts'=>$posts,
					'users'=>$users,
					'newposts'=>$this->conf['todayposts'],
					'newusers'=>$this->conf['todayusers'],
				);
				$this->stat->create($stat);
			}
		
			// 每日清空一次此文件
			$this->kv->delete('resetpw');
			
			// 清空
			$this->runtime->xset('todayposts', 0);
			$this->runtime->xset('todaythreads', 0);
			$this->runtime->xset('todayusers', 0);
			$this->runtime->xset('onlines', $this->online->count());	// 校对
			// $this->runtime->xsave();
		}
		
		// 释放锁
		$this->runtime->set('cronlock', 0);
		
	}
}
?>