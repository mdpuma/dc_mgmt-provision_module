<?php

require_once 'prometheus.class.php';
require_once 'cred.php';

$switches = ['rack-l2', 'rack-l3', 'rack-r0', 'rack-r1', 'rack-r2', 'rack-r4',
  'rack-r20','rack-r21','rack-r22','rack-r24','rack-r31'];

$prom = new PrometheusClient($prometheus_uri);


printf("switch,port,incoming,outgoing,total\n");

foreach($switches as $switch) {
  $query1 = sprintf("increase(ifHCInOctets{hostname=\"%s\"}[%s])/1000/1000/1000/1000 + increase(ifHCOutOctets{hostname=\"%s\"}[%s])/1000/1000/1000/1000 >25", $switch,'30d',$switch,'30d');
  $start = date('Y').'-'.(date('m')-1).'-'.'01';
  $end = date('Y').'-'.date('m').'-'.'01';
  //$result = $prom->queryRange($query1, strtotime($start), strtotime($end), '1d');
  $result = $prom->query($query1, strtotime($end));

  if($result['status'] == 'success') {
    $incoming_res = $prom->query('increase(ifHCInOctets{hostname="'.$switch.'"}[30d])/1000/1000/1000/1000', strtotime($end));
    $outgoing_res = $prom->query('increase(ifHCOutOctets{hostname="'.$switch.'"}[30d])/1000/1000/1000/1000', strtotime($end));
    foreach($result['data']['result'] as $i) {
      $total = round($i['value']['1'], 2);
      if($total > 30) {
          $ifName = $i['metric']['ifName'];
          $in = get_value_by_key($incoming_res, 'ifName', $ifName);
          $out = get_value_by_key($outgoing_res, 'ifName', $ifName);
           printf("%s,%s,%s,%s,%s\n",$switch, $ifName, round($in, 2), round($out, 2), $total);
      }
    }
  }
}

function get_value_by_key($array, $k, $kv) {
  if($array['status'] !=='success') return false;
  foreach($array['data']['result'] as $i) {
    if($i['metric'][$k] == $kv) {
      return $i['value']['1'];    
    }
  }
  return false;
}
