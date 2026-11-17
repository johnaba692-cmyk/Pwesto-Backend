<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Welcome extends Controller {
	public function index() {
		$this->call->view('framework_page');
	}

	public function marketplace() {
		$this->call->view('welcome_page', [
			'google_client_id' => config_item('google_client_id') ?: '',
		]);
	}
}
?>