<?php
/**
Version 0.1
- initial release

**/

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

use WHMCS\Database\Capsule;

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see http://docs.whmcs.com/Provisioning_Module_Meta_Data_Parameters
 *
 * @return array
 */
 
define("IP_REGEX", "/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/");
define("INTERFACE_REGEX", "/^(gi|fa|vlan|vl|tu|tun)?(\/?\d+)+$/");

function dcmgmt_MetaData() {
    return array(
        'DisplayName' => 'Datacenter Management',
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => true, // Set true if module requires a server to work
        'DefaultNonSSLPort' => '1111', // Default Non-SSL Connection Port
        'DefaultSSLPort' => '1112', // Default SSL Connection Port
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin'
    );
}

/**
 * Define product configuration options.
 *
 * The values you return here define the configuration options that are
 * presented to a user when configuring a product for use with the module. These
 * values are then made available in all module function calls with the key name
 * configoptionX - with X being the index number of the field from 1 to 24.
 *
 * You can specify up to 24 parameters, with field types:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each and their possible configuration parameters are provided in
 * this sample function.
 *
 * @return array
 */
function dcmgmt_ConfigOptions() {
    return array(
        'Suspend type' => array(
            'Type' => 'radio',
            'Options' => 'Null-route ip address,Disable network port',
            'Description' => 'What happens when customer service need to suspend:'
        )
    );
}

/**
 * Provision a new instance of a product/service.
 *
 * Attempt to provision a new instance of a given product/service. This is
 * called any time provisioning is requested inside of WHMCS. Depending upon the
 * configuration, this can be any of:
 * * When a new order is placed
 * * When an invoice for a new order is paid
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function dcmgmt_CreateAccount(array $params) {
    try {
        // Call the service's provisioning function, using the values provided
        // by WHMCS in `$params`.
        //
        // A sample `$params` array may be defined as:
        //
        // ```
        // array(
        //     'domain' => 'The domain of the service to provision',
        //     'username' => 'The username to access the new service',
        //     'password' => 'The password to access the new service',
        //     'configoption1' => 'The amount of disk space to provision',
        //     'configoption2' => 'The new services secret key',
        //     'configoption3' => 'Whether or not to enable FTP',
        //     ...
        // )
        // ```
    }
    catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('provisioningmodule', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        return $e->getMessage();
    }
    
    return 'success';
}

/**
 * Suspend an instance of a product/service.
 *
 * Called when a suspension is requested. This is invoked automatically by WHMCS
 * when a product becomes overdue on payment or can be called manually by admin
 * user.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function dcmgmt_SuspendAccount(array $params) {
    try {
        // Call the service's suspend function, using the values provided by
        // WHMCS in `$params`.
        switch ($params['configoption1']) {
            case 'Null-route ip address': {
                if (!preg_match(IP_REGEX, $params['customfields']['customerip'])) {
                    throw new exception("ERROR: empty customerip");
                }
                $output = shell_exec("python3 " . __DIR__ . "/lib/gateway.py --routerip=" . $params['serverip'] . " --username=" . $params['serverusername'] . " --password=" . $params['serverpassword'] . " --action=suspend --type=nullroute --customerip=" . $params['customfields']['customerip'] . " 2>&1");
                break;
            }
            case 'Disable network port': {
                if (!preg_match(INTERFACE_REGEX, $params['customfields']['interface'])) {
                    throw new exception("ERROR: empty interface");
                }
                $output = shell_exec("python3 " . __DIR__ . "/lib/gateway.py --routerip=" . $params['serverip'] . " --username=" . $params['serverusername'] . " --password=" . $params['serverpassword'] . " --action=suspend --type=shutdownport --interface=" . $params['customfields']['interface'] . " 2>&1");
                break;
            }
        }
        if (!preg_match("/OK/", $output)) {
            throw new exception("ERROR from backend: $output");
        }
    }
    catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('provisioningmodule', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
    return 'success';
}

/**
 * Un-suspend instance of a product/service.
 *
 * Called when an un-suspension is requested. This is invoked
 * automatically upon payment of an overdue invoice for a product, or
 * can be called manually by admin user.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function dcmgmt_UnsuspendAccount(array $params) {
    try {
        // Call the service's unsuspend function, using the values provided by
        // WHMCS in `$params`.
        switch ($params['configoption1']) {
            case 'Null-route ip address': {
                if (!preg_match(IP_REGEX, $params['customfields']['customerip'])) {
                    throw new exception("ERROR: empty customerip");
                }
                $output = shell_exec("python3 " . __DIR__ . "/lib/gateway.py --routerip=" . $params['serverip'] . " --username=" . $params['serverusername'] . " --password=" . $params['serverpassword'] . " --action=unsuspend --type=nullroute --customerip=" . $params['customfields']['customerip'] . " 2>&1");
                break;
            }
            case 'Disable network port': {
                if (!preg_match(INTERFACE_REGEX, $params['customfields']['interface'])) {
                    throw new exception("ERROR: empty interface");
                }
                $output = shell_exec("python3 " . __DIR__ . "/lib/gateway.py --routerip=" . $params['serverip'] . " --username=" . $params['serverusername'] . " --password=" . $params['serverpassword'] . " --action=unsuspend --type=shutdownport --interface=" . $params['customfields']['interface'] . " 2>&1");
                break;
            }
        }
        if (!preg_match("/OK/", $output)) {
            throw new exception("ERROR from backend: $output");
        }
    }
    catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('provisioningmodule', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
    return 'success';
}

/**
 * Terminate instance of a product/service.
 *
 * Called when a termination is requested. This can be invoked automatically for
 * overdue products if enabled, or requested manually by an admin user.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function dcmgmt_TerminateAccount(array $params) {
    try {
        // Call the service's terminate function, using the values provided by
        // WHMCS in `$params`.
    }
    catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('provisioningmodule', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        return $e->getMessage();
    }
    
    return 'success';
}

/**
 * Change the password for an instance of a product/service.
 *
 * Called when a password change is requested. This can occur either due to a
 * client requesting it via the client area or an admin requesting it from the
 * admin side.
 *
 * This option is only available to client end users when the product is in an
 * active status.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function dcmgmt_ChangePassword(array $params) {
    try {
        // Call the service's change password function, using the values
        // provided by WHMCS in `$params`.
        //
        // A sample `$params` array may be defined as:
        //
        // ```
        // array(
        //     'username' => 'The service username',
        //     'password' => 'The new service password',
        // )
        // ```
    }
    catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('provisioningmodule', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        return $e->getMessage();
    }
    
    return 'success';
}

/**
 * Upgrade or downgrade an instance of a product/service.
 *
 * Called to apply any change in product assignment or parameters. It
 * is called to provision upgrade or downgrade orders, as well as being
 * able to be invoked manually by an admin user.
 *
 * This same function is called for upgrades and downgrades of both
 * products and configurable options.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function dcmgmt_ChangePackage(array $params) {
    try {
        // Call the service's change password function, using the values
        // provided by WHMCS in `$params`.
        //
        // A sample `$params` array may be defined as:
        //
        // ```
        // array(
        //     'username' => 'The service username',
        //     'configoption1' => 'The new service disk space',
        //     'configoption3' => 'Whether or not to enable FTP',
        // )
        // ```
    }
    catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('provisioningmodule', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        return $e->getMessage();
    }
    
    return 'success';
}

/**
 * Test connection with the given server parameters.
 *
 * Allows an admin user to verify that an API connection can be
 * successfully made with the given configuration parameters for a
 * server.
 *
 * When defined in a module, a Test Connection button will appear
 * alongside the Server Type dropdown when adding or editing an
 * existing server.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return array
 */
function dcmgmt_TestConnection(array $params) {
    try {
        // Call the service's connection test function.
        
        $success  = true;
        $errorMsg = '';
    }
    catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('provisioningmodule', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        $success  = false;
        $errorMsg = $e->getMessage();
    }
    
    return array(
        'success' => $success,
        'error' => $errorMsg
    );
}

/**
 * Additional actions an admin user can invoke.
 *
 * Define additional actions that an admin user can perform for an
 * instance of a product/service.
 *
 * @see provisioningmodule_buttonOneFunction()
 *
 * @return array
 */
/**
 * commented due bug https://forum.whmcs.com/showthread.php?123470-After-upgrade-6-3-gt-7-1-wrong-server-shown-in-customer-product-details-tab&highlight=select+server
 function dcmgmt_AdminCustomButtonArray()
 {
 return array();
 }
 **/

/**
 * Additional actions a client user can invoke.
 *
 * Define additional actions a client user can perform for an instance of a
 * product/service.
 *
 * Any actions you define here will be automatically displayed in the available
 * list of actions within the client area.
 *
 * @return array
 */
function dcmgmt_ClientAreaCustomButtonArray() {
    return array();
}

/**
 * Admin services tab additional fields.
 *
 * Define additional rows and fields to be displayed in the admin area service
 * information and management page within the clients profile.
 *
 * Supports an unlimited number of additional field labels and content of any
 * type to output.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 * @see provisioningmodule_AdminServicesTabFieldsSave()
 *
 * @return array
 */
/*function dcmgmt_AdminServicesTabFields(array $params) {
    // $params['customfields']['interface']
    // querying last 30 days
    // $params['serverid'] is equal to tblhosting.server
    $results = get_bwusage($params['serverid'], $params['customfields']['interface'], '31d');
    
    $fieldsarray = array(
        'Bandwidth Usage (last 30 days)' => "Received: " . dcmgmt_formatSize($results['rx']) . "<br>" . "Sent: " . dcmgmt_formatSize($results['tx']) . "<br>" . "Total: " . dcmgmt_formatSize($results['total']) . "<br>",
        'Average Speed (last 30 days)' => "Receiving: " . dcmgmt_formatSpeed($results['rx'] / (24 * $results['days'] * 3600)) . "<br>" . "Sending: " . dcmgmt_formatSpeed($results['tx'] / (24 * $results['days'] * 3600)) . "<br>"
    );
    return $fieldsarray;
    //return array();
}*/

/**
 * Execute actions upon save of an instance of a product/service.
 *
 * Use to perform any required actions upon the submission of the admin area
 * product management form.
 *
 * It can also be used in conjunction with the AdminServicesTabFields function
 * to handle values submitted in any custom fields which is demonstrated here.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 * @see provisioningmodule_AdminServicesTabFields()
 */
function dcmgmt_AdminServicesTabFieldsSave(array $params) {
    // Fetch form submission variables.
    $originalFieldValue = isset($_REQUEST['provisioningmodule_original_uniquefieldname']) ? $_REQUEST['provisioningmodule_original_uniquefieldname'] : '';
    
    $newFieldValue = isset($_REQUEST['provisioningmodule_uniquefieldname']) ? $_REQUEST['provisioningmodule_uniquefieldname'] : '';
    
    // Look for a change in value to avoid making unnecessary service calls.
    if ($originalFieldValue != $newFieldValue) {
        try {
            // Call the service's function, using the values provided by WHMCS
            // in `$params`.
        }
        catch (Exception $e) {
            // Record the error in WHMCS's module log.
            logModuleCall('provisioningmodule', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
            
            // Otherwise, error conditions are not supported in this operation.
        }
    }
}

/**
 * Perform single sign-on for a given instance of a product/service.
 *
 * Called when single sign-on is requested for an instance of a product/service.
 *
 * When successful, returns a URL to which the user should be redirected.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return array
 */
function dcmgmt_ServiceSingleSignOn(array $params) {
    try {
        // Call the service's single sign-on token retrieval function, using the
        // values provided by WHMCS in `$params`.
        $response = array();
        
        return array(
            'success' => true,
            'redirectTo' => $response['redirectUrl']
        );
    }
    catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('provisioningmodule', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        return array(
            'success' => false,
            'errorMsg' => $e->getMessage()
        );
    }
}

/**
 * Perform single sign-on for a server.
 *
 * Called when single sign-on is requested for a server assigned to the module.
 *
 * This differs from ServiceSingleSignOn in that it relates to a server
 * instance within the admin area, as opposed to a single client instance of a
 * product/service.
 *
 * When successful, returns a URL to which the user should be redirected to.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return array
 */
function dcmgmt_AdminSingleSignOn(array $params) {
    try {
        // Call the service's single sign-on admin token retrieval function,
        // using the values provided by WHMCS in `$params`.
        $response = array();
        
        return array(
            'success' => true,
            'redirectTo' => $response['redirectUrl']
        );
    }
    catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('provisioningmodule', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        return array(
            'success' => false,
            'errorMsg' => $e->getMessage()
        );
    }
}

/**
 * Client area output logic handling.
 *
 * This function is used to define module specific client area output. It should
 * return an array consisting of a template file and optional additional
 * template variables to make available to that template.
 *
 * The template file you return can be one of two types:
 *
 * * tabOverviewModuleOutputTemplate - The output of the template provided here
 *   will be displayed as part of the default product/service client area
 *   product overview page.
 *
 * * tabOverviewReplacementTemplate - Alternatively using this option allows you
 *   to entirely take control of the product/service overview page within the
 *   client area.
 *
 * Whichever option you choose, extra template variables are defined in the same
 * way. This demonstrates the use of the full replacement.
 *
 * Please Note: Using tabOverviewReplacementTemplate means you should display
 * the standard information such as pricing and billing details in your custom
 * template or they will not be visible to the end user.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return array
 */
function dcmgmt_ClientArea(array $params) {
    // Determine the requested action and set service call parameters based on
    // the action.
    /*
    $requestedAction = isset($_REQUEST['customAction']) ? $_REQUEST['customAction'] : '';
    
    if ($requestedAction == 'manage') {
    $serviceAction = 'get_usage';
    $templateFile = 'templates/manage.tpl';
    } else {
    $serviceAction = 'get_stats';
    $templateFile = 'templates/overview.tpl';
    }
    
    try {
    // Call the service's function based on the request action, using the
    // values provided by WHMCS in `$params`.
    $response = array();
    
    $extraVariable1 = 'abc';
    $extraVariable2 = '123';
    
    return array(
    'tabOverviewReplacementTemplate' => $templateFile,
    'templateVariables' => array(
    'extraVariable1' => $extraVariable1,
    'extraVariable2' => $extraVariable2,
    ),
    );
    } catch (Exception $e) {
    // Record the error in WHMCS's module log.
    logModuleCall(
    'provisioningmodule',
    __FUNCTION__,
    $params,
    $e->getMessage(),
    $e->getTraceAsString()
    );
    
    // In an error condition, display an error page.
    return array(
    'tabOverviewReplacementTemplate' => 'error.tpl',
    'templateVariables' => array(
    'usefulErrorHelper' => $e->getMessage(),
    ),
    );
    }
    */
}

/*
function dcmgmt_UsageUpdate($params) {
    $serverid         = $params['serverid'];
    $serverhostname   = $params['serverhostname'];
    $serverip         = $params['serverip'];
    $serverusername   = $params['serverusername'];
    $serverpassword   = $params['serverpassword'];
    $serveraccesshash = $params['serveraccesshash'];
    $serversecure     = $params['serversecure'];
    
    $serveraccesshash = simplexml_load_string($serveraccesshash);
    
    if($serverip==null || empty($serverip)) {
        return;
    }
    
    if (!isset($serveraccesshash->community))
        $serveraccesshash->community = 'public';
    
    if (!isset($serveraccesshash->snmpver))
        $serveraccesshash->snmpver = '2c';
    
    # Run connection to retrieve usage for all domains/accounts on $serverid
    // get interface names with index
    $interfaces = [];
    $output     = shell_exec('snmpwalk -v' . $serveraccesshash->snmpver . ' -c ' . $serveraccesshash->community . ' -m IF-MIB ' . $serverip . ' IF-MIB::ifName');
    $lines      = explode("\n", $output);
    foreach ($lines as $line) {
        # IF-MIB::ifName.1 = STRING: Gi1/1
        preg_match('/^IF-MIB::ifName.(\d+) = STRING: (.*)$/', $line, $matches);
        if (preg_match('/^(Gi|Vl|Po|Tu|\d+)/', $matches[2])) {
            $interfaces[$matches[1]]['name'] = strtolower($matches[2]);
        }
    }
    
    // get incoming bytes
    $output = shell_exec('snmpwalk -v' . $serveraccesshash->snmpver . ' -c ' . $serveraccesshash->community . ' -m IF-MIB ' . $serverip . ' IF-MIB::ifHCInOctets');
    $lines  = explode("\n", $output);
    foreach ($lines as $line) {
        # IF-MIB::ifHCInOctets.182 = Counter64: 1469416807863
        preg_match('/^IF-MIB::ifHCInOctets.(\d+) = Counter64: (.*)$/', $line, $matches);
        if (isset($interfaces[$matches[1]])) {
            $interfaces[$matches[1]]['rx'] = $matches[2];
        }
    }
    
    // get outgoing bytes
    $output = shell_exec('snmpwalk -v' . $serveraccesshash->snmpver . ' -c ' . $serveraccesshash->community . ' -m IF-MIB ' . $serverip . ' IF-MIB::ifHCOutOctets');
    $lines  = explode("\n", $output);
    foreach ($lines as $line) {
        # IF-MIB::ifHCOutOctets.182 = Counter64: 1469416807863
        preg_match('/^IF-MIB::ifHCOutOctets.(\d+) = Counter64: (.*)$/', $line, $matches);
        if (isset($interfaces[$matches[1]])) {
            $interfaces[$matches[1]]['tx'] = $matches[2];
        }
    }
    $pdo = Capsule::connection()->getPdo();
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare('insert into `mod_dcmgmt_bandwidth_port` (serverid, timestamp, name, rx, tx) values (:serverid, NOW(), :name, :rx, :tx)');
        
        foreach ($interfaces as $id => $data) {
            if(!isset($data['tx']) || $data['tx'] !== null)
                continue;
            
            $stmt->execute(array(
                ':serverid' => $serverid,
                ':name' => $data['name'],
                ':rx' => $data['rx'],
                ':tx' => $data['tx']
            ));
        }
        $pdo->commit();
    }
    catch (\Exception $e) {
        echo "Error happen! Rollback transactions {$e->getMessage()}";
        $pdo->rollBack();
    }
    
    # Now loop through results and update DB
    $products_info = Capsule::table('tblcustomfieldsvalues')->select('tblhosting.id', 'tblhosting.server', 'tblcustomfields.fieldname', 'tblcustomfieldsvalues.value', 'tblhosting.nextduedate')->join('tblhosting', 'tblhosting.id', '=', 'tblcustomfieldsvalues.relid')->join('tblcustomfields', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')->where('tblcustomfields.fieldname', '=', 'interface')->where('tblcustomfieldsvalues.value', '!=', NULL)->get();
    
    // check all products and calculate used Bandwidth
    $results = '';
    foreach ($products_info as $product) {
        // product['value'] is interface name
        $last_month            = get_bwusage($product->server, $product->value, 'month', $product->nextduedate);
        $results[$product->id] = round($last_month['total'] / 1024 / 1024, 2); # convert bytes to megabytes
    }
    
    // disk and bw units is MB
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('update `tblhosting` set diskusage=0, disklimit=0, bwusage=:bwusage, bwlimit=:bwlimit, lastupdate=now() where id=:productid');
        
        foreach ($results as $productid => $bwused) {
            $stmt->execute(array(
                ':productid' => $productid,
                ':bwusage' => $bwused,
                ':bwlimit' => 5 * 1024 * 1024
            ));
        }
        $pdo->commit();
    }
    catch (\Exception $e) {
        echo "Error happen! Rollback transactions {$e->getMessage()}";
        $pdo->rollBack();
    }
}
*/

function dcmgmt_formatSize($size) {
    $mod   = 1024;
    $units = explode(' ', 'B KB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
        $size /= $mod;
    }
    return round($size, 3) . ' ' . $units[$i];
}

function dcmgmt_formatSpeed($speed) {
    $mod   = 1024;
    $speed = $speed * 8;
    $units = explode(' ', 'bits Kbit Mbit Gbit');
    for ($i = 0; $speed > $mod; $i++) {
        $speed /= $mod;
    }
    return round($speed, 3) . ' ' . $units[$i];
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
    return $last_month;
}
