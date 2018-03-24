<?php

use WHMCS\Database\Capsule;

//Do not run this file without WHMCS
!defined('ROOTDIR') ? die('Cannot run directly!') : 0;

function dcmgmt_config() {
    $configarray = array(
        "name" => "Datacenter management module",
        "description" => "This addon is allow to do some datacenter like functions for dedicated/colocation services.",
        "version" => "0.2",
        "author" => "",
        "fields" => array()
    );
    return $configarray;
}

function dcmgmt_activate() {
    # Create Custom DB Table
    Capsule::statement('drop table users');
    $query = "CREATE TABLE `mod_dcmgmt_bandwidth_port` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `serverid` int(11) NOT NULL,
          `timestamp` datetime NOT NULL,
          `name` varchar(64) NOT NULL,
          `rx` bigint(20) unsigned NOT NULL DEFAULT '0',
          `tx` bigint(20) unsigned NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    Capsule::statement($query);
    Capsule::statement("ALTER TABLE `mod_dcmgmt_bandwidth_port` ADD INDEX `serverid` (`serverid`, `name`, `timestamp`);");
    
    # Return Result
    return array(
        'status' => 'success'
    );
}

function dcmgmt_deactivate() {
    # Remove Custom DB Table
    $query = "TRUNCATE TABLE `mod_dcmgmt_bandwidth_port`";
    Capsule::statement($query);
    
    # Return Result
    return array(
        'status' => 'success'
    );
}

function dcmgmt_output($vars) {
    $modulelink = $vars['modulelink'];
    $version    = $vars['version'];
    $option1    = $vars['option1'];
    $option2    = $vars['option2'];
    $option3    = $vars['option3'];
    $option4    = $vars['option4'];
    $option5    = $vars['option5'];
    $option6    = $vars['option6'];
    $LANG       = $vars['_lang'];
    
	echo <<<EOF
<div class="lic_linksbar"><a href="addonmodules.php?module=dcmgmt&amp;page=bandwidth">Bandwidth</a> | <a class="thickbox" href="addonmodules.php?module=dcmgmt&amp;page=electricity">Electricity</a></div>
    <div id="main">
EOF;
	if(!isset($_GET['page'])) $_GET['page'] = 'bandwidth';
	switch($_GET['page']) {
		case 'bandwidth':
			show_bandwidth_table();
			break;
		case 'electricity':
			show_electricity_table();
			break;
	}
    echo '</div>';
}

function show_bandwidth_table() {
	echo <<<EOF
	<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
		<tr>
			<th><a href="?module=dcmgmt&amp;page=bandwidth&amp;orderby=id">Product ID</a></th>
			<th><a href="?module=dcmgmt&amp;page=bandwidth&amp;orderby=domain">Domain (Product/Service)</a></th>
			<th><a href="?module=dcmgmt&amp;page=bandwidth&amp;orderby=interface">Interface</a></th>
			<th><a href="?module=dcmgmt&amp;page=bandwidth&amp;orderby=switch">Switch</a></th>
			<th><a href="?module=dcmgmt&amp;page=bandwidth&amp;orderby=bwmonth">Bandwidth used this month</a></th>
			<th><a href="?module=dcmgmt&amp;page=bandwidth&amp;orderby=bw31d">Bandwidth used in last 30d</a></th>
			<th><a href="?module=dcmgmt&amp;page=bandwidth&amp;orderby=duedate">Due date</a></th>
			<th><a href="?module=dcmgmt&amp;page=bandwidth&amp;orderby=status">Status</a></th>
		</tr>
EOF;
    
    
    $servers_name = '';
    $result       = Capsule::table('tblservers')->select('id', 'name')->where('type', '=', 'dcmgmt ')->get();
    foreach ($result as $res) {
        $servers_name[$res->id] = $res->name;
    }
    
    $products_info = Capsule::table('tblcustomfieldsvalues')->select('tblhosting.id', 'tblhosting.userid', 'tblhosting.domain', 'tblhosting.nextduedate', 'tblhosting.server', 'tblhosting.domainstatus', 'tblcustomfields.fieldname', 'tblcustomfieldsvalues.value', 'tblhosting.nextduedate')->join('tblhosting', 'tblhosting.id', '=', 'tblcustomfieldsvalues.relid')->join('tblcustomfields', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')->where('tblcustomfields.fieldname', '=', 'interface')->where('tblcustomfieldsvalues.value', '!=', NULL)->orderby('tblhosting.nextduedate', 'asc')->get();
    
    $products_info = objectToArray($products_info);
    
    foreach ($products_info as $id => $product) {
        if ( !( $product['domainstatus'] == 'Active' || $product['domainstatus'] == 'Suspended' ) && empty($product['value'])) {
            unset($products_info[$id]);
            continue;
        }
        
        if (empty($product['domain']))
            $products_info[$id]['domain'] = '(No domain)';
        
        $products_info[$id]['servername']    = $servers_name[$product['server']];
        $products_info[$id]['bwusage_31d']   = get_bwusage($product['server'], $product['value'], '31d', null);
        $products_info[$id]['bwusage_month'] = get_bwusage($product['server'], $product['value'], 'month', $product['nextduedate']);
        
        if (empty($product['value']))
            $products_info[$id]['value'] = '(not set)';
    }
    
    if (isset($_GET['orderby'])) {
        switch ($_GET['orderby']) {
            case 'id':
                usort($products_info, function($a, $b) {
                    return $a['id'] - $b['id'];
                });
                break;
            case 'domain':
                usort($products_info, function($a, $b) {
                    return strcmp($a['domain'], $b['domain']);
                });
                break;
            case 'interface':
                usort($products_info, function($a, $b) {
                    return strcmp($a['value'], $b['value']);
                });
                break;
            case 'switch':
                usort($products_info, function($a, $b) {
                    return strcmp($a['servername'], $b['servername']);
                });
                break;
            case 'bwmonth':
                usort($products_info, function($a, $b) {
                    return $a['bwusage_month']['bytes'] - $b['bwusage_month']['bytes'];
                });
                break;
            case 'bw31d':
                usort($products_info, function($a, $b) {
                    return $a['bwusage_31d']['bytes'] - $b['bwusage_31d']['bytes'];
                });
                break;
            case 'status':
				usort($products_info, function($a, $b) {
					return strcmp($a['domainstatus'], $b['domainstatus']);
				});
				break;
        }
    }
    
    foreach ($products_info as $product) {
        echo '<tr>
            <td><a href="clientsservices.php?userid=' . $product['userid'] . '&id=' . $product['id'] . '">' . $product['id'] . '</a></td>
            <td><a href="clientsservices.php?userid=' . $product['userid'] . '&id=' . $product['id'] . '">' . $product['domain'] . '</a></td>
            <td>' . $product['value'] . '</td>
            <td>' . $product['servername'] . '</td>
            <td style="text-align: right">'.$product['bwusage_month']['from'].'-'.$product['bwusage_month']['to'].' ' . print_bwusage($product['bwusage_month']['bytes']) . '</td>
            <td style="text-align: right">' . print_bwusage($product['bwusage_31d']['bytes']) . '</td>
            <td>' . $product['nextduedate'] . '</td>
            <td>' . $product['domainstatus'] . '</td>
        </tr>';
    }
    echo '</table>';
}

function show_electricity_table() {
	echo <<<EOF
	<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
		<tr>
			<th><a href="?module=dcmgmt&amp;page=electricity&amp;orderby=id">Product ID</a></th>
			<th><a href="?module=dcmgmt&amp;page=electricity&amp;orderby=domain">Domain (Product/Service)</a></th>
			<th><a href="?module=dcmgmt&amp;page=electricity&amp;orderby=interface">Interface</a></th>
			<th><a href="?module=dcmgmt&amp;page=electricity&amp;orderby=socket">Power Socket</a></th>
			<th><a href="?module=dcmgmt&amp;page=electricity&amp;orderby=electricity">Average Wh Use</a></th>
			<th><a href="?module=dcmgmt&amp;page=electricity&amp;orderby=duedate">Due date</a></th>
			<th><a href="?module=dcmgmt&amp;page=electricity&amp;orderby=status">Status</a></th>
		</tr>
EOF;
    
    
    $servers_name = '';
    $result       = Capsule::table('tblservers')->select('id', 'name')->where('type', '=', 'dcmgmt ')->get();
    foreach ($result as $res) {
        $servers_name[$res->id] = $res->name;
    }
    
    // 242
    $result = Capsule::table('tblproductconfigoptions')->select('id')->where('optionname','=','Average Wh Use')->get()[0];
    $option_id = objectToArray($result);
    
    $result = Capsule::table('tblhostingconfigoptions')->select('tblhosting.id', 'tblhosting.userid', 'tblhosting.domain', 'tblhosting.nextduedate', 'tblhosting.server', 'tblhosting.domainstatus', 'tblhostingconfigoptions.qty', 'tblhosting.nextduedate')->join('tblhosting', 'tblhosting.id', '=', 'tblhostingconfigoptions.relid')->where('tblhostingconfigoptions.configid', '=', $option_id)->orderby('tblhosting.nextduedate', 'asc')->get();
    
    $products_info = objectToArray($result);
    
    foreach ($products_info as $id => $product) {
        if ( !( $product['domainstatus'] == 'Active' || $product['domainstatus'] == 'Suspended' ) && empty($product['qty'])) {
            unset($products_info[$id]);
            continue;
        }
        
        if (empty($product['domain']))
            $products_info[$id]['domain'] = '(No domain)';
        
        $products_info[$id]['servername']    = $servers_name[$product['server']];
        
        if (empty($product['qty']))
            $products_info[$id]['qty'] = '(not set)';
        else
			$products_info[$id]['qty'] = $product['qty'].' Watts';
    }
    
    if (isset($_GET['orderby'])) {
        switch ($_GET['orderby']) {
            case 'id':
                usort($products_info, function($a, $b) {
                    return $a['id'] - $b['id'];
                });
                break;
            case 'domain':
                usort($products_info, function($a, $b) {
                    return strcmp($a['domain'], $b['domain']);
                });
                break;
            case 'socket':
                usort($products_info, function($a, $b) {
                    return strcmp($a['value'], $b['value']);
                });
                break;
            case 'electricity':
                usort($products_info, function($a, $b) {
                    return $a['qty'] - $b['qty'];
                });
                break;
            case 'status':
				usort($products_info, function($a, $b) {
					return strcmp($a['domainstatus'], $b['domainstatus']);
				});
				break;
        }
    }
    
    foreach ($products_info as $product) {
        echo '<tr>
            <td><a href="clientsservices.php?userid=' . $product['userid'] . '&id=' . $product['id'] . '">' . $product['id'] . '</a></td>
            <td><a href="clientsservices.php?userid=' . $product['userid'] . '&id=' . $product['id'] . '">' . $product['domain'] . '</a></td>
            <td>-</td>
            <td>' . $product['servername'] . '</td>
            <td style="text-align: right">'.$product['qty'].'</td>
            <td>' . $product['nextduedate'] . '</td>
            <td>' . $product['domainstatus'] . '</td>
        </tr>';
    }
    echo '</table>';
}

function dcmgmt_clientarea($vars) {
    
    $modulelink = $vars['modulelink'];
    $version    = $vars['version'];
    $option1    = $vars['option1'];
    $option2    = $vars['option2'];
    $option3    = $vars['option3'];
    $option4    = $vars['option4'];
    $option5    = $vars['option5'];
    $option6    = $vars['option6'];
    $LANG       = $vars['_lang'];
    
    return array(
        'pagetitle' => 'Addon Module',
        'breadcrumb' => array(
            'index.php?m=demo' => 'Demo Addon'
        ),
        'templatefile' => 'clienthome',
        'requirelogin' => true, # accepts true/false
        'forcessl' => false, # accepts true/false
        'vars' => array(
            'testvar' => 'demo',
            'anothervar' => 'value',
            'sample' => 'test'
        )
    );
    
}

// type may be month or 31d for last 31 days
function get_bwusage($serverid, $interface, $type = 'month', $nextduedate = null) {
    if ($type == 'month') {
        $day   = date('d', strtotime($nextduedate));
        $month = date('m');
        if ($day < date('d')) {
            $from = date('Y') . '-' . $month . '-' . $day;
            $to   = date('Y-m-d', (strtotime($from) + 3600 * 24 * 31));
        } else {
            $to   = date('Y') . '-' . $month . '-' . $day;
            $from = date('Y-m-d', (strtotime($to) - 3600 * 24 * 31));
        }
        //      echo "get $interface ($nextduedate / $from - $to)<br>";
        $traffic_result = Capsule::table('mod_dcmgmt_bandwidth_port')->select('id', 'rx', 'tx')->where('serverid', '=', $serverid)->where('name', '=', $interface)->where('timestamp', '>=', $from)->where('timestamp', '<=', $to)->orderBy('id', 'asc')->get();
    } else {
        $traffic_result = Capsule::table('mod_dcmgmt_bandwidth_port')->select('id', 'rx', 'tx')->where('serverid', '=', $serverid)->where('name', '=', $interface)->where('timestamp', '<=', date('Y-m-d'))->where('timestamp', '>=', date('Y-m-d', date('U') - 3600 * 24 * 31))->orderBy('id', 'asc')->get();
    }
    foreach ($traffic_result as $i => $date) {
        if (!isset($traffic_result[$i + 1]))
            break;
        if ($traffic_result[$i + 1]->rx >= $traffic_result[$i]->rx) {
            $last_month['rx'] += $traffic_result[$i + 1]->rx - $date->rx;
            $last_month['tx'] += $traffic_result[$i + 1]->tx - $date->tx;
        }
        $last_month['days']++;
    }
    $last_month['total'] = $last_month['rx'] + $last_month['tx'];
    $results             = round($last_month['total'] / 1024 / 1024 / 1024, 2); // convert bytes to gigabytes
    return array(
		'bytes' => $results,
		'from' => $from,
		'to' => $to,
	);
}

function print_bwusage($bw, $limit = 5000) {
    if (intval($bw) < $limit)
        return '<span class="label active">' . number_format($bw, 2) . ' GB</span>';
    return '<span class="label terminated">' . number_format($bw, 2) . ' GB</span>';
}
function objectToArray($d) {
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
    } else {
        // Return array
        return $d;
    }
}