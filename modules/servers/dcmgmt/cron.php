#!/usr/bin/env php
<?php

if(php_sapi_name() !== 'cli') {
	die('Access Denied');
}
//===================================================================================================//
//CONFIG PART

$community = 'innovasnmp';
$version = '2c';

//===================================================================================================//
//CODE PART
$whmcs_root = __DIR__.'/../../../';
require_once $whmcs_root.'/configuration.php';

$db = mysql_connect($db_host, $db_username, $db_password) or die("Could not connect: " . mysql_error());

mysql_select_db($db_name) or die("Could not select database");

$traffic='';

$mysql_result = mysql_query("SELECT * FROM tblservers WHERE `type`='dcmgmt'");
while($server = mysql_fetch_array($mysql_result, MYSQL_ASSOC)) {
	// get interface names with index
	$interfaces='';
	$output = shell_exec("snmpwalk -v$version -c $community -m IF-MIB ".$server['ipaddress']." IF-MIB::ifName");
	$lines = explode("\n", $output);
	foreach($lines as $line) {
		# IF-MIB::ifName.1 = STRING: Gi1/1
		preg_match('/^IF-MIB::ifName.(\d+) = STRING: (.*)$/', $line, $matches);
		if(preg_match('/^(Gi|Vl|Po|Tu|\d+)/', $matches[2])) {
			$interfaces[$matches[1]]['name'] = strtolower($matches[2]);
		}
	}
	
	// get incoming bytes
	$output = shell_exec("snmpwalk -v$version -c $community -m IF-MIB ".$server['ipaddress']." IF-MIB::ifHCInOctets");
	$lines = explode("\n", $output);
	foreach($lines as $line) {
		# IF-MIB::ifHCInOctets.182 = Counter64: 1469416807863
		preg_match('/^IF-MIB::ifHCInOctets.(\d+) = Counter64: (.*)$/', $line, $matches);
		if(isset($interfaces[$matches[1]])) {
			$interfaces[$matches[1]]['rx'] = $matches[2];
		}
	}
	
	// get outgoing bytes
	$output = shell_exec("snmpwalk -v$version -c $community -m IF-MIB ".$server['ipaddress']." IF-MIB::ifHCOutOctets");
	$lines = explode("\n", $output);
	foreach($lines as $line) {
		# IF-MIB::ifHCOutOctets.182 = Counter64: 1469416807863
		preg_match('/^IF-MIB::ifHCOutOctets.(\d+) = Counter64: (.*)$/', $line, $matches);
		if(isset($interfaces[$matches[1]])) {
			$interfaces[$matches[1]]['tx'] = $matches[2];
		}
	}
	
	$traffic[$server['id']]['interfaces'] = $interfaces;
	
	foreach($interfaces as $id => $data) {
		mysql_query("INSERT INTO `mod_dcmgmt_bandwidth_port` (`serverid`,`timestamp`,`name`,`rx`,`tx`) VALUES (".$server['id'].",NOW(),'".$data['name']."',".$data['rx'].",".$data['tx'].")");
		print "Update data for ".$data['name']." on server ".$server['id'].": ".$data['rx'].",".$data['tx']."\n";
	}
}