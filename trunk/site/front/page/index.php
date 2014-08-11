<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Front_Page_Index extends Front_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title = 'Weather Weather lang';
	protected $_class = 'home';
	protected $_template = '/index.phtml';
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		// get remote address or visitors ip address
		$remote = $_SERVER['REMOTE_ADDR'];

		// if remote is localhost set remote ip address
		// to openovate labs office
		if ($remote == '127.0.0.1') { $remote = '122.54.175.139'; }

		$this->_body = array('remote' => $remote);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	/* Private Methods
	-------------------------------*/
}
