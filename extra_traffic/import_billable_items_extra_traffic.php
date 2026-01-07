<?php

require_once "whmcs_api.php";
require_once "cred.php";

/*

  Required permissions:

  GetCurrencies
  GetClientsProducts
  GetClientsDetails
  AddBillableItem

*/

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Get CLI arguments
$args = getopt('', ['file:', 'dry-run']);

$whmcs = new whmcs_api($url, $identifier, $secret);

$ret = $whmcs->call(array('action' => 'GetCurrencies'));

if($ret['result'] !== 'success') {
   die("Cant get currencies from whmcs\n"); 
}

foreach($ret['currencies']['currency'] as $j) {
    $currencies[$j['id']] = $j;
}

// Display usage if no arguments are provided
if (empty($args)) {
    echo "Usage: php $0 --file=import.csv [--dry-run]\n";
    exit(1);
}


$is_dryrun = false;
if(isset($args['dry-run'])) {
  $is_dryrun = true;
}

$file = $args['file'];

$delimiter = ','; // Default delimiter is a comma
$enclosure = '"'; // Default enclosure is a double quote

// Check if the file exists
if (!file_exists($file)) {
    die("Error: File not found - $file\n");
}

// Open the CSV file for reading
if (($handle = fopen($file, 'r')) === false) {
    die("Error: Could not open file - $file\n");
}

echo "Parsing CSV file: $file\n\n";

// Read the header row
$headers = fgetcsv($handle, 0, $delimiter, $enclosure);
if ($headers === false) {
    die("Error: Failed to read headers from the CSV file.\n");
}

// Process each row of the CSV file
while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false) {
    // Combine the headers with the row values
    $line = array_combine($headers, $row);
    //var_dump($line);
    
    /*if($line['amount_eur'] == 0) {
        echo "Skip AddBillableItem for number ".$line['number']." ".$line['company']."\n";
        continue;
    }*/
    
    $ret = $whmcs->call(array(
        'action' => 'GetClientsProducts',
        'serviceid' => $line['productid'],
    ));
    
    if($ret['totalresults'] < 1) {
        echo "[error] Cant find customer with serviceid ".$line['productid']."\n";
        continue;
    } elseif($ret['totalresults'] > 1) {
		$found=0;
		foreach($ret['products']['product'] as $prod) {
			if($prod['status'] == 'Terminated') {
				continue;
			}
			
			$found++;
			$product = $prod;
		}
		if($found>1) {
			echo "[error] There is more than one customer with same productid ".$line['productid']."\n";
      continue;
		}
	} else {
		$product = $ret['products']['product'][0];
		
	}
    //echo "found client product id ".$ret['products']['product'][0]['id']."\n";
    
    $clientid = $product['clientid'];
    $hostname = $product['domain'];
    
    $client = $whmcs->call(array(
        'action' => 'GetClientsDetails',
        'clientid' => $clientid
    ));
    
    $currency_id = $client['client']['currency'];
    $currency_code = $client['client']['currency_code'];
    $currency_rate = $currencies[$currency_id]['rate'];

    // get network switch and port
//    $switch = $product['serverhostname'];
//    $port = getCustomFieldValue($product['customfields']['customfield'], 'interface');
//    printf("%s,%s,%s,%s\n", $line['productid'], $line['hostname'], $switch, $port);
//    continue;
    
    if(round($line['extra_traffic'], 2) !== (float)0) {
        $amount = $line['extra_traffic'] * 4; // 4 euro = 1tb extra
        $amount = round($amount * $currency_rate, 2); // apply currency rate // convert from EUR to MDL/USD/RON
        $interval_string = get_interval_string();
        $description = sprintf("Extra traffic %s / %s (%sTB) (%s)", $hostname, $line['productid'], $line['extra_traffic'], $interval_string);
        if($is_dryrun == true) {
          printf("Description: %s, amount %s %s\n", $description, $amount, $currency_code);
          
        } else {
          printf("Description: %s, amount %s %s\n", $description, $amount, $currency_code);
          $ret = $whmcs->call(array(
              'action' => 'AddBillableItem',
              'clientid' => $clientid,
              'unit' => 'quantity',
              'description' => $description,
              'amount' => $amount,
              'invoiceaction' => 'nextinvoice', // nextinvoice
          ));
          if($ret['result'] !=='success') {
              echo "Cant add billable item for productid: ".$line['productid']."\n";   
          }
        }
    }
}

// Close the file
fclose($handle);

function get_interval_string() {
    $from2 = new DateTime(date("1.m.Y"));
    $from2 = $from2->modify("-1 month");
    $from = $from2->format('d.m.Y');
    $to2 = $from2->modify("+1 month");
    $to2->modify('-1 day');
    $to = $to2->format('d.m.Y');
    return sprintf("%s - %s", $from, $to);
}

function getCustomFieldValue($array, $fieldname) { // get custom field value by name
    foreach($array as $i) {
      if($i['name'] == $fieldname) {
        return $i['value'];
      }
    }
    return false;
}
?>
