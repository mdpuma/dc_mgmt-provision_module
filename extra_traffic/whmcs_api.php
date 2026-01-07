<?php

class whmcs_api {
	private $url = null;
	private $username = null;
	private $secret = null;
	function __construct($url, $username, $secret) { // https://www.example.com/includes/api.php
		$this->url = $url;
		$this->username = $username;
		$this->secret = $secret;
	}
	
	function call($args = array()) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			http_build_query(
				array_merge($args, array(
					'username' => $this->username,
					'password' => $this->secret,
					'responsetype' => 'json'
				))
			)
		);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		curl_close($ch);	
		return json_decode($response, true);
	}
};