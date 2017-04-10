<?php 

/*

Release 1

*/

use WHMCS\Database\Capsule;

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
 
    echo <<<EOF
<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
    <tr>
        <th><a href="?module=dcmgmt&orderby=id">Product ID</a></th>
        <th><a href="?module=dcmgmt&orderby=domain">Domain (Product/Service)</a></th>
        <th><a href="?module=dcmgmt&orderby=interface">Interface</a></th>
        <th><a href="?module=dcmgmt&orderby=switch">Switch</a></th>
        <th><a href="?module=dcmgmt&orderby=bwmonth">Bandwidth used this month</a></th>
        <th><a href="?module=dcmgmt&orderby=bw31d">Bandwidth used in last 30d</a></th>
        <th>Due date</th>
        <th><a href="?module=dcmgmt&orderby=status">Status</a></th>
    </tr>
EOF;
	
	$servers_name = '';
	$result = Capsule::table('tblservers')->select('id', 'name')->where('type', '=', 'dcmgmt ')->get();
	foreach($result as $res) {
		$servers_name[$res->id] = $res->name;
	}
	
	
	$products_info = Capsule::table('tblcustomfieldsvalues')
	    ->select('tblhosting.id', 'tblhosting.userid', 'tblhosting.domain', 'tblhosting.nextduedate', 'tblhosting.server', 'tblhosting.domainstatus', 'tblcustomfields.fieldname', 'tblcustomfieldsvalues.value', 'tblhosting.nextduedate')
	    ->join('tblhosting', 'tblhosting.id', '=', 'tblcustomfieldsvalues.relid')
	    ->join('tblcustomfields', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
	    ->where('tblcustomfields.fieldname', '=', 'interface')
	    ->where('tblcustomfieldsvalues.value', '!=', NULL)
	    ->orderby('tblhosting.nextduedate', 'asc')
	    ->get();
	
	$products_info = objectToArray($products_info);
	
	foreach($products_info as $id => $product) {
		if($product['domainstatus'] == 'Terminated' || $product['domainstatus'] == 'Cancelled') {
			unset($products_info[$id]);
			continue;
		}
		
		if(empty($product['domain']))
			$products_info[$id]['domain'] = '(No domain)';
		
		$products_info[$id]['servername'] = $servers_name[$product['server']];
		$products_info[$id]['bwusage_31d'] = get_bwusage($product['value'], '31d', null);
		$products_info[$id]['bwusage_month'] = get_bwusage($product['value'], 'month', $product['nextduedate']);
		
		if(empty($product['value']))
			$products_info[$id]['value'] = '(not set)';
	}
	
	if(isset($_GET['orderby'])) {
		switch($_GET['orderby']) {
			case 'id': usort($products_info, function($a, $b) {return $a['id'] - $b['id'];}); break;
			case 'domain': usort($products_info, function($a, $b) {return $a['domain'] - $b['domain'];}); break;
			case 'interface': usort($products_info, function($a, $b) {return $a['value'] - $b['value'];}); break;
			case 'switch': usort($products_info, function($a, $b) {return $a['server'] - $b['server'];}); break;
			case 'bwmonth': usort($products_info, function($a, $b) {return $a['bwusage_month'] - $b['bwusage_month'];}); break;
			case 'bw31d': usort($products_info, function($a, $b) {return $a['bwusage_31d'] - $b['bwusage_31d'];}); break;
		}
	}
	
	foreach($products_info as $product) {
		echo '<tr>
		    <td><a href="clientsservices.php?userid='.$product['userid'].'&id='.$product['id'].'">'.$product['id'].'</a></td>
		    <td><a href="clientsservices.php?userid='.$product['userid'].'&id='.$product['id'].'">'.$product['domain'].'</a></td>
		    <td>'.$product['value'].'</td>
		    <td>'.$product['servername'].'</td>
		    <td style="text-align: right">'.print_bwusage($product['bwusage_month']).'</td>
		    <td style="text-align: right">'.print_bwusage($product['bwusage_31d']).'</td>
		    <td>'.$product['nextduedate'].'</td>
		    <td>'.$product['domainstatus'].'</td>
		</tr>';
	}
	echo '</table>';
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

// type may be month or 31d for last 31 days
function get_bwusage($interface, $type = 'month', $nextduedate = null)
{
	if ($type == 'month') {
		$day = date('d', $nextduedate);
		$prev_month = date('m')-1;
		$from = date('Y').'-'.$prev_month.'-'.$day;
		$to = date('Y-m').'-'.$day;
		$traffic_result = Capsule::table('mod_dcmgmt_bandwidth_port')->select('id', 'rx', 'tx')->where('name', '=', $interface)->where('timestamp', '>=', $from)->where('timestamp', '<=', $to)->orderBy('id', 'asc')->get();
	}
	else {
		$traffic_result = Capsule::table('mod_dcmgmt_bandwidth_port')->select('id', 'rx', 'tx')->where('name', '=', $interface)->where('timestamp', '<=', date('Y-m-d'))->where('timestamp', '>=', date('Y-m-d', date('U') - 3600 * 24 * 31))->orderBy('id', 'asc')->get();
	}
	foreach($traffic_result as $i => $date) {
		if (!isset($traffic_result[$i + 1])) break;
		if ($traffic_result[$i + 1]->rx >= $traffic_result[$i]->rx) {
			$last_month['rx']+= $traffic_result[$i + 1]->rx - $date->rx;
			$last_month['tx']+= $traffic_result[$i + 1]->tx - $date->tx;
		}
		$last_month['days']++;
	}
	$last_month['total'] = $last_month['rx'] + $last_month['tx'];
	$results = round($last_month['total'] / 1024 / 1024 / 1024, 2); // convert bytes to gigabytes
	return $results;
}

function print_bwusage($bw, $limit=5000)
{
	if (intval($bw) < $limit) return '<span class="label active">' . number_format($bw, 2) . ' GB</span>';
	return '<span class="label terminated">' . number_format($bw, 2) . ' GB</span>';
}

function objectToArray($d)
{
	if (is_object($d)) {
		// Gets the properties of the given object
		// with get_object_vars function
		$d = get_object_vars($d);
	}
	if (is_array($d)) {
		/*
		* Return array converted to object
		* Using __FUNCTION__ (Magic constant)
		* for recursive call
		*/
		return array_map(__FUNCTION__, $d);
	}
	else {
		// Return array
		return $d;
	}
}