<?php

namespace koolreport\statistics;
use \koolreport\core\Utility;

class StatisticsReader
{
  public static function read($statDataStore) {
    $stats = array();
    $data = $statDataStore->data();
    foreach ($data as $row) {
      $stat = $row['{{stat}}'];
      unset($row['{{stat}}']);
      $stats[$stat] = $row;
    }
    return $stats;    
  }
}