# Introduction

Talking about data, we also talk about statistics because we learn about data through statistics. The `Statistics` package allows you to compute basic and advanced statistical measures for columns of your data. The basic measures could be `min`, `max`, `mean`, `median`, etc. The advanced measures are, for example, `lowerQuartile`, `popStdDeviation` and others.

# Installation

1. Download and unzip the zipped file.
2. Copy the folder `statistics` into `koolreport/packages` folder
3. Reference to the Statistics process and the utility StatisticsReader by the classname `\koolreport\statistics\Statistics` and `\koolreport\statistics\StatisticsReader`


# Documentation

## Quick start

In your report's setup page, use the process `\koolreport\statistics\Statistics` with an array parameter in the type of '{{statistical measure}}' => '{{array of column names}}'

```
<?php
//MyReport.php
use \koolreport\statistics\Statistics;

class MyReport extends \koolreport\KoolReport
{
    ...
    function setup()
    {
        $this->src('sales')
        ->pipe(new Statistics(array(
            'min' => array('2003'),
            'max' => array('2003'),
            'mean' => array('2003', '2004'),
            'median' => array('2003', '2004', '{{all}}'),
            'lowerQuartile' => array('2005'),
            'upperQuartile' => array('2005'),
            'meanDeviation' => array('{{all}}'),
            'stdDeviation' => array('{{all}}'),
            'percentile_10' => array('{{all}}'),
            'percentile_90' => array('{{all}}'),
        )))
        ->pipe($this->dataStore('salesYearMonthStatistics'));
    }
}
```

Then in your view, you could use the utility StatisticsReader to extract the measures:


```
<?php
    //MyReport.view.php:
    use \koolreport\statistics\StatisticsReader;
    
    $stats = StatisticsReader::read($this->dataStore('salesYearMonthStatistics'));

    echo $stats['min']['2003'];
    echo $stats['median']['2004'];
    echo $stats['percentile_90']['{{all}}'];
?>
```

## Properties

The package supports the following statistical measures:

|name|description|
|---|---|
|`min`|Returns the first minimum value of a data series.|
|`max`|Returns the first maximum value of a data series.|
|`mode`|Returns the first most common value of a data series.|
|`mean`|Returns the average value of a data series.|
|`median`|Returns the middle value that divides a data series into 2 equal halves when ordering.|
|`lowerQuartile`|Returns the first quartile of the quartiles that divides a data series into 4 equal group when ordering. The second quartile is the median.|
|`upperQuartile`|Returns the third quartile.|
|`percentile_X`|Returns the value that X percentage of a data series fall below when ordering. X is an integer from 0 to 100.|
|`meanDeviation`|Returns the mean absolute deviation value of a data series in the formula of Sum of absolute(elementValue - mean) / length of data series.|
|`popStdDeviation`|Returns the population standard deviation value of a data series in the formula of Square root of (sum of square(elementValue - mean) / length of data series).|
|`samStdDeviation`|Returns the sample standard deviation value of a data series in the formula of Square root of (sum of square(elementValue - mean) / (length of data series - 1)).|


# Support

Please use our forum if you need support, by this way other people can benefit as well. If the support request need privacy, you may send email to us at __support@koolreport.com__.