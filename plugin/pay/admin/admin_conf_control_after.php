<?php
	// 数据全部写入 kv db

	// 支付相关 ?
	// paytype
	public function on_pay() {
		$tab = core::gpc('tab');
		empty($tab) && $tab = 'setting';
		$this->_checked[$tab] = ' class="checked"';
		
		$kvconf = $this->kv->xget('pay_conf');
		
		// 初始化
		if(!isset($this->conf['pay_on'])) {
			$this->kv->xset('pay_on', 0, 'conf');
			$this->kv->xset('pay_rate', 1, 'pay_conf');
			$this->kv->xset('alipay_on', 0, 'pay_conf');
			$this->kv->xset('alipay_partner', '', 'pay_conf');
			$this->kv->xset('alipay_security_code', '', 'pay_conf');
			$this->kv->xset('alipay_seller_email', '', 'pay_conf');
			$this->kv->xset('ebank_on', 0, 'pay_conf');
			$this->kv->xset('ebank_v_mid', '', 'pay_conf');
			$this->kv->xset('ebank_key', '', 'pay_conf');
			$this->kv->xset('banklist_on', 0, 'pay_conf');
			$this->kv->xset('banklist', '', 'pay_conf');
			
			$this->runtime->xset('pay_on', 0);
			$this->conf['pay_on'] = 0;
		}
		
		$input = $error = array();
		if($tab == 'setting') {
			
			$pay_on = $this->conf['pay_on'];
			$pay_rate = $kvconf['pay_rate'];
			
			if($this->form_submit()) {
				$pay_on = intval(core::gpc('pay_on', 'P'));
				$pay_rate = intval(core::gpc('pay_rate', 'P'));
				
				$this->kv->xset('pay_on', $pay_on, 'conf');
				$this->kv->xset('pay_rate', $pay_rate, 'pay_conf');
			}
			
			$input['pay_on'] = form::get_radio_yes_no('pay_on', $pay_on);
			$input['pay_rate'] = form::get_text('pay_rate', $pay_on, 100);
		
		} elseif($tab == 'alipay') {
			
			$partner = $kvconf['alipay_partner'];
			$security_code = $kvconf['alipay_security_code'];
			$seller_email = $kvconf['alipay_seller_email'];
			$alipay_on = $kvconf['alipay_on'];
			
			if($this->form_submit()) {
				$partner = core::gpc('partner', 'P');
				$security_code = core::gpc('security_code', 'P');
				$seller_email = core::gpc('seller_email', 'P');
				$alipay_on = intval(core::gpc('alipay_on', 'P'));
				
				$this->kv->xset('alipay_partner', $partner, 'pay_conf');
				$this->kv->xset('alipay_security_code', $security_code, 'pay_conf');
				$this->kv->xset('alipay_seller_email', $seller_email, 'pay_conf');
				$this->kv->xset('alipay_on', $alipay_on, 'pay_conf');
			}
			
			$input['partner'] = form::get_text('partner', $partner, 300);
			$input['security_code'] = form::get_text('security_code', $security_code, 300);
			$input['seller_email'] = form::get_text('seller_email', $seller_email, 300);
			$input['alipay_on'] = form::get_radio_yes_no('alipay_on', $alipay_on);
			
		} elseif($tab == 'ebank') {
			
			$v_mid = $kvconf['ebank_v_mid'];
			$key = $kvconf['ebank_key'];
			$ebank_on = $kvconf['ebank_on'];
			
			if($this->form_submit()) {
				$v_mid = core::gpc('v_mid', 'P');
				$key = core::gpc('key', 'P');
				$ebank_on = intval(core::gpc('ebank_on', 'P'));
				
				$this->kv->xset('ebank_v_mid', $v_mid, 'pay_conf');
				$this->kv->xset('ebank_key', $key, 'pay_conf');
				$this->kv->xset('ebank_on', $ebank_on, 'pay_conf');
			}
			
			$input['v_mid'] = form::get_text('v_mid', $v_mid, 300);
			$input['key'] = form::get_text('key', $key, 300);
			$input['ebank_on'] = form::get_radio_yes_no('ebank_on', $ebank_on);
				
		} elseif($tab == 'banklist') {
			
			$banklist_on = $kvconf['banklist_on'];
			$banklist = $kvconf['banklist'];
			
			if($this->form_submit()) {
				$banklist = core::gpc('banklist', 'P');
				$banklist_on = intval(core::gpc('banklist_on', 'P'));
				
				$this->kv->xset('banklist', $banklist, 'pay_conf');
				$this->kv->xset('banklist_on', $banklist_on, 'pay_conf');
			}
			
			$this->view->assign('banklist', $banklist);
			$input['banklist_on'] = form::get_radio_yes_no('banklist_on', $banklist_on);
		}
		
		$this->view->assign('error', $error);
		$this->view->assign('input', $input);
		$this->view->assign('tab', $tab);
		$this->view->display('conf_pay.htm');
	}
	
	
?>