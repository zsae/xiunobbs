<?php

/*
 * Copyright (C) xiuno.com
 */

class user_denglu extends base_model{
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'user_denglu';
		$this->primarykey = array('muid');
	}
	
}
?>