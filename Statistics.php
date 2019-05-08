<?php
/**
 * This file contains process to compute various statistical measures for a data stream
 *
 * @author KoolPHP Inc (support@koolphp.net)
 * @link https://www.koolphp.net
 * @copyright KoolPHP Inc
 * @license https://www.koolreport.com/license#regular-license
 * @license https://www.koolreport.com/license#extended-license
 */

/* Usage
 * ->pipe(new Statistics(array(
    'excludedColumns' = array('orderMonth', 1),
    'includedColumns' = array(2, 3),
    'percentiles' => array(10, 90),
    
    'min' => array('2003'),
    'max' => array('2003'),
    'mean' => array('2004'),
    'median' => array('2004'),
    'lowerQuartile' => array('2005'),
    'upperQuartile' => array('2005'),
    'percentile_10' => array('{{all}}'),
    'percentile_90' => array('{{all}}'),
 * )))
 * */
namespace koolreport\statistics;
use \koolreport\core\Utility;

class Statistics extends \koolreport\core\Process
{
  protected $data;
  protected $statData;
  
  protected $stats = array('min', 'max', 'mean', 'mode', 'median', 
    'lowerQuartile', 'upperQuartile', 'meanDeviation', 'stdDeviation');
  
  protected $excludedColumns;
  protected $includedColumns;
  protected $percentiles;
  protected $columns;
  
  protected function OnInit() {
    $this->columns = array();
    $statData = array();
    $params = $this->params;
    foreach ($this->params as $stat => $colNames) {
      $statData[$stat] = array();
      foreach ($colNames as $colName) {
        $statData[$stat][$colName] = 0;
        if (! in_array($colName, $this->columns))
          array_push($this->columns, $colName);
      }
    }
    $this->statData = $statData;
    $this->data = array();
    foreach ($this->columns as $colName)
      $this->data[$colName] = array();
  }
  
  protected function OnInput($row) {
    if (array_key_exists('mean', $this->statData)) {
      $colNames = $this->params['mean'];
      foreach ($colNames as $colName)
        $this->statData['mean'][$colName] += $row[$colName];
    }
    foreach ($this->columns as $colName) {
      array_push($this->data[$colName], $row[$colName]);
    }
  }
  
  function getPercentile($sortedSeries, $start, $len, $p) {
    $rank = $p / 100 * ($len - 1) + 1;
    $n = floor($rank);
    $m = $rank - $n;
    $value = $sortedSeries[$start + $n - 1] + ($m > 0 && $n < $start + $len - 1 ? 
      $m*($sortedSeries[$start + $n] - $sortedSeries[$start + $n - 1]) : 0);
    return $value;
  }
  
  protected function process() {
    $data = $this->data;
    $colDataSorted = array();
    foreach ($this->statData as $stat => $statMeasure) {
      foreach ($statMeasure as $colName => $measure) {
        $len = count($data[$colName]);
        if (! isset($colDataSorted[$colName])) {
          $colDataSorted[$colName] = $data[$colName];
          sort($colDataSorted[$colName]);
        }
        $colData = $colDataSorted[$colName];
        switch (true) {
          case $stat === 'mean':
            $this->statData[$stat][$colName] = 
              $this->statData[$stat][$colName] / $len;
            break;
          case $stat === 'min':
            $this->statData[$stat][$colName] = $colData[0];
            break;
          case $stat === 'max':
            $this->statData[$stat][$colName] = $colData[$len - 1];
            break;
          case $stat === 'mode':
            $mode = $colData[0];
            $maxCount = 1;
            $currentCount = 1;
            foreach ($colData as $i => $num)
              if ($i > 0 && $num !== $colData[$i - 1])
                $currentCount = 1;
              else if ($i > 0 && $num === $colData[$i - 1]) {
                $currentCount++;
                if ($currentCount > $maxCount) {
                  $maxCount = $currentCount;
                  $mode = $num;
                }
              }
            $this->statData[$stat][$colName] = $mode;
            break;
          case $stat === 'popStdDeviation':
            $mean = $this->statData[$stat][$colName] / $len;
            $stdDeviation = 0;
            foreach ($colData as $num) 
              $stdDeviation += pow($num - $mean, 2);
            $this->statData[$stat][$colName] = sqrt($stdDeviation / $len);
            break;
          case $stat === 'samStdDeviation':
            $mean = $this->statData[$stat][$colName] / $len;
            $stdDeviation = 0;
            foreach ($colData as $num) 
              $stdDeviation += pow($num - $mean, 2);
            $this->statData[$stat][$colName] = sqrt($stdDeviation / ($len - 1));
            break;
          case $stat === 'meanDeviation':
            $mean = $this->statData[$stat][$colName] / $len;
            $meanDeviation = 0;
            foreach ($colData as $num) 
              $meanDeviation += abs($num - $mean);
            $this->statData[$stat][$colName] = $meanDeviation / $len;
            break;
          case $stat === 'median':
            $this->statData[$stat][$colName] = $this->getPercentile(
              $colData, 0, $len, 50);
            break;
          case $stat === 'lowerQuartile':
            $this->statData[$stat][$colName] = $this->getPercentile(
              $colData, 0, $len, 25);
            break;
          case $stat === 'upperQuartile':
            $this->statData[$stat][$colName] = $this->getPercentile(
              $colData, 0, $len, 75);
            break;
          case substr($stat, 0, 11) === 'percentile_':
            $per = (int) substr($stat, 11);
            $this->statData[$stat][$colName] = $this->getPercentile(
              $colData, 0, $len, $per);
            break;
          default:
        }
      }
    }
    
    $this->metaData['columns']['stat'] = array('type' => 'string');
  }
  
  public function receiveMeta($metaData, $source) 
  {
    $this->metaData = array_merge($metaData);
  }
  
  protected function OnInputEnd() {
    $this->process();
    
    $this->sendMeta($this->metaData);
    
    // foreach($this->stats as $statName) {
      // $this->next($this->statData[$statName]);
    // }
    
    // Utility::prettyPrint($this->statData);
    
    foreach ($this->statData as $stat => $row)
      // $this->next($row, $stat);
      // $this->next($row);
      $this->next(array('{{stat}}' => $stat) + $row);
  }
}