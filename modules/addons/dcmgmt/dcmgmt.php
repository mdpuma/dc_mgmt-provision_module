<?php 

/*

Release 1

*/

//Do not run this file without WHMCS
!defined('ROOTDIR') ? die('Cannot run directly!') : 0;

function dcmgmt_config() {
	$configarray = array(
	"name" => "Datacenter management module",
	"description" => "This addon is allow to do some datacenter like functions for dedicated/colocation services.",
	"version" => "0.1",
	"author" => "<a href='https://github.com/mdpuma'>mdpuma@github.com</a>",
	"fields" => array());
	return $configarray;
}

function dcmgmt_activate() {
	# Create Custom DB Table
		$query = "CREATE TABLE `mod_dcmgmt_bandwidth_port` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `serverid` int(11) NOT NULL,
		  `timestamp` datetime NOT NULL,
		  `name` varchar(64) NOT NULL,
		  `rx` bigint(20) unsigned NOT NULL DEFAULT '0',
		  `tx` bigint(20) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		mysql_query($query);
	
	# Return Result
	return array('status'=>'success'); 
}
 
function dcmgmt_deactivate() {
	# Remove Custom DB Table
	$query = "TRUNCATE TABLE `mod_dcmgmt_bandwidth_port`";
	mysql_query($query);
	
	# Return Result
	return array('status'=>'success'); 
}

function dcmgmt_output($vars) {
 
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $option1 = $vars['option1'];
    $option2 = $vars['option2'];
    $option3 = $vars['option3'];
    $option4 = $vars['option4'];
    $option5 = $vars['option5'];
    $option6 = $vars['option6'];
    $LANG = $vars['_lang'];
 
    echo '<p>The date & time are currently '.date("Y-m-d H:i:s").'</p>';
 
}

function dcmgmt_clientarea($vars) {
 
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $option1 = $vars['option1'];
    $option2 = $vars['option2'];
    $option3 = $vars['option3'];
    $option4 = $vars['option4'];
    $option5 = $vars['option5'];
    $option6 = $vars['option6'];
    $LANG = $vars['_lang'];
 
    return array(
        'pagetitle' => 'Addon Module',
        'breadcrumb' => array('index.php?m=demo'=>'Demo Addon'),
        'templatefile' => 'clienthome',
        'requirelogin' => true, # accepts true/false
        'forcessl' => false, # accepts true/false
        'vars' => array(
            'testvar' => 'demo',
            'anothervar' => 'value',
            'sample' => 'test',
        ),
    );
 
}