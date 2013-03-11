
if($this->form_submit()) {
	if(empty($error)) {
		$user['money'] = intval(core::gpc('money', 'P'));
		$this->user->update($user);
	}
}

$input['money'] = form::get_text('money', $user['money'], 100);