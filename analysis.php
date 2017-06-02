<?php

if (!isset($_POST["dtp_start"]) || !isset($_POST["dtp_end"]) || !isset($_POST["users"]) || !isset($_POST["countries"]))
{
    header('Location: ./');
    exit;
}

if (empty($_POST["dtp_start"]) || empty($_POST["dtp_end"]))
    die("please define the date range");

if (empty($_POST["users"]) && empty($_POST["countries"]))
    die("please define the citeria");

$dtp_start = $_POST["dtp_start"];
$dtp_end = $_POST["dtp_end"];
$users_q = $_POST["users"];
$countries_q = $_POST["countries"];


set_time_limit(500);

//LINQ for PHP - https://github.com/Athari/YaLinqo
require_once('YaLinqo/Utils.php');
require_once('YaLinqo/Functions.php');
require_once('YaLinqo/Linq.php');
require_once('YaLinqo/EnumerablePagination.php');
require_once('YaLinqo/EnumerableGeneration.php');
require_once('YaLinqo/Enumerable.php');
require_once('YaLinqo/Errors.php');
require_once('YaLinqo/OrderedEnumerable.php');
require_once('general.php');



if (!empty($_POST["users"])){
    //turn csv to array
    $user_ids = explode(',', $users_q);
    //export array to csv (with quotes)
    $user_ids = " and USER_NAME in('".implode("','", $user_ids)."') ";
}
    
if (!empty($_POST["countries"])){
    //turn csv to array
    $countries = explode(',', $countries_q);
    //export array to csv (with quotes)    
    $countries = " and COUNTRY in ('".implode("','", $countries)."') ";
}


$mysql = new dbase();
$mysql->connect_mysql();


////////////////////////////////////////////////////////////////////// ------------------- progress [START]

function getPercentage($current, $total)
{
    $section_one = @($current / $total); //hide the warning Division by zero with @
    if (!$section_one)
        return 0;
    else 
        return number_format($section_one * 100,2);

    //return number_format(($current / $total) * 100,2);
}

//construct SQL
$qprogress = "select * from jobs where (DATE_CREATED between '{$dtp_start}'
 and '{$dtp_end}')".(empty($user_ids) ? "" : $user_ids).(empty($countries) ? "" : $countries);

$rowsprogress = $mysql->getSet($qprogress, null);

//convert to timestamp for easy manipulation in while loop
$startdate = strtotime($dtp_start." UTC");
$enddate = strtotime($dtp_end." UTC");

$progress_list = null;

//create a plain class object - we will use is as array of objects!
$progress_entry = new stdClass();


while($startdate < $enddate) {
    
    $startdate_plus_eight = date('Y-m-d', strtotime('+6 days', $startdate));
    $startdate_formatted =  date('Y-m-d', $startdate);
        
    $total_usa = $mysql->getScalar("select count(*) from jobs where (DATE_CREATED between '{$startdate_formatted}'
                                      and '{$startdate_plus_eight}') AND QUEUE = 'USA' ".(empty($user_ids) ? "" : $user_ids).(empty($countries) ? "" : $countries), null);
    
    $total_asia = $mysql->getScalar("select count(*) from jobs where (DATE_CREATED between '{$startdate_formatted}'
                                      and '{$startdate_plus_eight}') AND QUEUE = 'ASIA' ".(empty($user_ids) ? "" : $user_ids).(empty($countries) ? "" : $countries), null);
    
    $jobs_usa = $mysql->getScalar("select count(*) from jobs where (DATE_CREATED between '{$startdate_formatted}'
                                      and '{$startdate_plus_eight}') AND DELIVERED=1 AND QUEUE = 'USA' ".(empty($user_ids) ? "" : $user_ids).(empty($countries) ? "" : $countries), null);
    
    $jobs_asia = $mysql->getScalar("select count(*) from jobs where (DATE_CREATED between '{$startdate_formatted}'
                                      and '{$startdate_plus_eight}') AND DELIVERED=1 AND QUEUE = 'ASIA' ".(empty($user_ids) ? "" : $user_ids).(empty($countries) ? "" : $countries), null);
    
    //create plain class object
    $progress_entry = new stdClass();
    //attach properties
    $progress_entry->caption = sprintf("%s - %s", date('Y-m-d',$startdate), $startdate_plus_eight);
    $progress_entry->regional = getPercentage($jobs_usa, $total_usa);
    $progress_entry->local = getPercentage($jobs_asia, $total_asia);
    

    //add to array of objects
    $progress_list[] = $progress_entry;
    
    
    //increase the date by one week
    $startdate = strtotime('+7 days', $startdate);
    
    //via LINQ
    //https://github.com/Athari/YaLinqo/issues/20#issuecomment-280885070
    // Your anonymous function lacks use statement (fixed) :
    //    $result4 = from($rows)
    //    ->where(function ($x) use ($startdate) { 
    //        return strtotime($x['CLOSE_DATE']) < strtotime('+8 days', $startdate)  && $x['OWWNER']=='costas'; })
    //    ->toArray();
    //    
    //    $startdate = strtotime('+7 days', $startdate);
} 
//echo "<pre>";
//var_dump($progress_list);
//echo "</pre>";
//construct array for progress PIE
$progress_missed = array();
$progress_missed[] = array('Period', 'Queue', 'Local');
foreach($progress_list as $row) {
	$progress_missed[] = array($row->caption, (float) $row->regional, (float) $row->local);
}



////////////////////////////////////////////////////////////////////// ------------------- LINQ [START]
/*
//doing by SQL
$q = "select COUNTRY, COUNT(COUNTRY) as counter from jobs where (CLOSE_TIME between '{$dtp_start}'
 and '{$dtp_end}') ".(empty($user_ids) ? "" : $user_ids).(empty($countries) ? "" : $countries).' group by COUNTRY order by counter desc';
$rows = $mysql->getSet($q, null);

$countries_all = array();
$countries_all[] = array('Task', 'Hours per Day');
foreach($rows as $row) {
	$countries_all[] = array($row['COUNTRY'], (int) $row['counter']);
}
*/

//construct SQL
$q = "select * from jobs where (DATE_CREATED between '{$dtp_start}'
 and '{$dtp_end}')".(empty($user_ids) ? "" : $user_ids).(empty($countries) ? "" : $countries);

$rows = $mysql->getSet($q, null);

///////////////////////////////////
//groupby country
$result3 = from($rows)
->groupBy(function ($y) { return $y['COUNTRY']; })
->toArray();

$countries = array();
$countries[] = array('Task', 'Hours per Day');
foreach($result3 as $row) {
	$countries[] = array($row[0]['COUNTRY'], sizeof($row));
}

///////////////////////////////////
//groupby country - REGIONAL
$result4 = from($rows)
->where(function ($x) { return $x['QUEUE']=='USA'; })
->groupBy(function ($y) { return $y['COUNTRY']; })
->toArray();

$countries_regional = array();
$countries_regional[] = array('Task', 'Hours per Day');
foreach($result4 as $row) {
	$countries_regional[] = array($row[0]['COUNTRY'], sizeof($row));
}

///////////////////////////////////
//groupby country - LOCAL
$result4 = from($rows)
->where(function ($x) { return $x['QUEUE']=='ASIA'; })
->groupBy(function ($y) { return $y['COUNTRY']; })
->toArray();

$countries_local = array();
$countries_local[] = array('Task', 'Hours per Day');
foreach($result4 as $row) {
	$countries_local[] = array($row[0]['COUNTRY'], sizeof($row));
}

//-----------------------------------USERS-----------------------------------

///////////////////////////////////
//groupby user
$result = from($rows)
->groupBy(function ($y) { return $y['USER_NAME']; })
->toArray();

$users = array();
$users[] = array('Task', 'Hours per Day');
foreach($result as $row) {
	$users[] = array($row[0]['USER_NAME'], sizeof($row));
}

///////////////////////////////////
//groupby user - REGIONAL
$result = from($rows)
->where(function ($x) { return $x['QUEUE']=='USA'; })
->groupBy(function ($y) { return $y['USER_NAME']; })
->toArray();

$users_regional = array();
$users_regional[] = array('Task', 'Hours per Day');
foreach($result as $row) {
	$users_regional[] = array($row[0]['USER_NAME'], sizeof($row));
}

///////////////////////////////////
//groupby user - LOCAL
$result = from($rows)
->where(function ($x) { return $x['QUEUE']=='ASIA'; })
->groupBy(function ($y) { return $y['USER_NAME']; })
->toArray();

$users_local = array();
$users_local[] = array('Task', 'Hours per Day');
foreach($result as $row) {
	$users_local[] = array($row[0]['USER_NAME'], sizeof($row));
}

////////////////////////////////////////////////////////////////////// ------------------- LINQ [END]
?>

<html>
  <head>
      

    <script src="assets/jquery-3.1.1.min.js"></script>
    <script src="assets/bootstrap.min.js"></script>
    <script src="assets/bootstrap-table.min.js"></script>
  
      
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <link href="assets/bootstrap-table.min.css" rel="stylesheet">
      
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        //output charts PNG - https://developers.google.com/chart/interactive/docs/printing
        
        $(function() {
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                //http://stackoverflow.com/a/30468366
                
                //draw the pies
                if (this.href.indexOf('#countries')>0){
                  byCountryPIE();
                  byCountryUSAPIE();
                  byCountryASIAPIE();
                }
                
                //draw the pies
                if (this.href.indexOf('#users')>0){
                  byUserPIE();
                  byUserUSAPIE();
                  byUserASIAPIE();
                }
                
                
                //draw the progress missed
                if (this.href.indexOf('#progress')>0){
                    byprogress();
                }
                
            })

            $("#records_table").bootstrapTable(); //transform to magic!
        });
        
        
      google.charts.load('current', {'packages':['corechart','line']});
      google.charts.setOnLoadCallback(draw_pies);
        
        
      function draw_pies(){
          byCountryPIE();
          byCountryUSAPIE();
          byCountryASIAPIE();
      }

        

        
    /////////////////////////////
    //byprogress
    function byprogress() {

      var data = google.visualization.arrayToDataTable(<?= json_encode($progress_missed);?>);

      var options = {
        chart: {
          title: 'Progress',
          subtitle: 'delivered'
        },
        width: 900,
        height: 500
      };


        var chart = new google.charts.Line(document.getElementById('byprogressPIE'));

        chart.draw(data, options);
      }
        
    </script>
  </head>
  <body>
<div class="container-fluid">
    <ul class="nav nav-tabs">
      <li role="presentation" class="active"><a href="#countries" data-toggle="tab">Countries</a></li>        
      <li role="presentation"><a href="#users" data-toggle="tab">Users</a></li>
      <li role="presentation"><a href="#progress" data-toggle="tab">Progress</a></li>
      <li role="presentation"><a href="#records" data-toggle="tab">Data</a></li>
      <li role="presentation"><a href="#about" data-toggle="tab">About</a></li>
        <div style="margin-top:3px">
                <span class="label label-success" style="margin-top:10px;float:left;">Date Range : <?=$dtp_start?> - <?=$dtp_end?></span>
          
                <?php if ($users_q) { ?>
                    <!-- WHEN USERS SPECIFIED SHOW THEM TO GUI -->
                    <span class="label label-primary" style="margin-top:10px;float:left;margin-left:20px">Users : <?=$users_q?></span>
                <?php } ?>
          
                <?php if ($countries_q) { ?>
                    <!-- WHEN COUNTRIES SPECIFIED SHOW THEM TO GUI -->
                    <span class="label label-warning" style="margin-top:10px;float:left;;margin-left:20px">Countries : <?=$countries_q?></span>
                <?php } ?>
        </div>
		<button class="btn btn-success" style="float:right;" onclick="window.history.back();">BACK</button>
    </ul>

    
    <div class="tab-content">
      <div id="countries" class="tab-pane fade in active">
            <div class="row">
                <div class="col-md-6">
                <div id="byCountryPIE" style="width: 1000px; height: 800px;"></div>
                    </div>
                <div class="col-md-6">
                <div id="byCountryUSAPIE" style="width: 1000px; height: 800px;"></div>
                    </div>
                    <div class="col-md-6">
                <div id="byCountryASIAPIE" style="width: 1000px; height: 800px;"></div>
                        </div>
            </div>
      </div>
      <div id="users" class="tab-pane fade">
            <div class="row">
                <div class="col-md-6">
                <div id="byUserPIE" style="width: 1000px; height: 800px;"></div>
                    </div>
                <div class="col-md-6">
                <div id="byUserUSAPIE" style="width: 1000px; height: 800px;"></div>
                    </div>
                    <div class="col-md-6">
                <div id="byUserASIAPIE" style="width: 1000px; height: 800px;"></div>
                        </div>
            </div>
      </div>
      <div id="progress" class="tab-pane fade">
            <div class="row">
                <div class="col-md-12">
                    <div id="byprogressPIE" style="width: 1000px; height: 800px;"></div>
                </div>
            </div>
      </div>
      <div id="records" class="tab-pane fade">
            <div class="row" style="height: 85%;">
                <table id="records_table" data-striped="true" data-search="true">
                    <thead>
                        <tr>
                            <th data-field="id" data-visible="false">ID</th>
                            <th data-sortable="true">USERNAME</th>
                            <th data-sortable="true">DATE CREATED</th>
                            <th data-sortable="true">COUNTRY</th>
                            <th data-sortable="true">QUEUE</th>
                            <th data-sortable="true">DELIVERED</th>
                        </tr>
                    </thead>

                    <tbody>
<?php
                        //for all rows
                        foreach($rows as $row) {
                            echo "<tr>";
                            
                            //for each field
                            foreach ($row as $value)
                            {
                                echo "<td>".$value."</td>";
                            }
                            echo "</tr>";
                        }
?>
                    </tbody>
                </table>
            </div>
      </div>
      <div id="about" class="tab-pane fade">
            <div class="row" style="margin-top:50px;">
                <span class="label label-danger" style="padding:10px;">development by costas with love :)</span>
            </div>
      </div>
    </div>

</div>
      
      <script>
        
            /////////////////////////////
            //byCountryPIE - ALL
            function byCountryPIE() {

                var data = google.visualization.arrayToDataTable(<?= json_encode($countries);?>);

                //fix to display the value in legend
                var total = 0;
                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        total = total + data.getValue(i, 1);    
                }

                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        var label = data.getValue(i, 0);
                    var val = data.getValue(i, 1) ;
                    var percentual = ((val / total) * 100).toFixed(1); 
                    data.setFormattedValue(i, 0, label  + ' - '+val +' ('+ percentual + '%)');    
                }
                //fix to display the value in legend


                var options = {
                  title: 'Tickets Per Country'
                };


                var chart = new google.visualization.PieChart(document.getElementById('byCountryPIE'));

                chart.draw(data, options);
              }


            /////////////////////////////
            //byCountryPIE  - USA
            function byCountryUSAPIE() {

                var data = google.visualization.arrayToDataTable(<?= json_encode($countries_regional);?>);

                //fix to display the value in legend
                var total = 0;
                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        total = total + data.getValue(i, 1);    
                }

                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        var label = data.getValue(i, 0);
                    var val = data.getValue(i, 1) ;
                    var percentual = ((val / total) * 100).toFixed(1); 
                    data.setFormattedValue(i, 0, label  + ' - '+val +' ('+ percentual + '%)');    
                }
                //fix to display the value in legend


                var options = {
                  title: 'Tickets Per Country USA'
                };


                var chart = new google.visualization.PieChart(document.getElementById('byCountryUSAPIE'));

                chart.draw(data, options);
              }

            /////////////////////////////
            //byCountryPIE  - ASIA
            function byCountryASIAPIE() {

                var data = google.visualization.arrayToDataTable(<?= json_encode($countries_local);?>);

                //fix to display the value in legend
                var total = 0;
                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        total = total + data.getValue(i, 1);    
                }

                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        var label = data.getValue(i, 0);
                    var val = data.getValue(i, 1) ;
                    var percentual = ((val / total) * 100).toFixed(1); 
                    data.setFormattedValue(i, 0, label  + ' - '+val +' ('+ percentual + '%)');    
                }
                //fix to display the value in legend


                var options = {
                  title: 'Tickets Per Country ASIA'
                };


                var chart = new google.visualization.PieChart(document.getElementById('byCountryASIAPIE'));

                chart.draw(data, options);
              }

        //--------------------------------------------------USERS--------------------------------------------------

            /////////////////////////////
            //byUserPIE - ALL
            function byUserPIE() {

                var data = google.visualization.arrayToDataTable(<?= json_encode($users);?>);

                //fix to display the value in legend
                var total = 0;
                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        total = total + data.getValue(i, 1);    
                }

                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        var label = data.getValue(i, 0);
                    var val = data.getValue(i, 1) ;
                    var percentual = ((val / total) * 100).toFixed(1); 
                    data.setFormattedValue(i, 0, label  + ' - '+val +' ('+ percentual + '%)');    
                }
                //fix to display the value in legend


                var options = {
                  title: 'Tickets Per User'
                };


                var chart = new google.visualization.PieChart(document.getElementById('byUserPIE'));

                chart.draw(data, options);
              }


            /////////////////////////////
            //byUserPIE  - USA
            function byUserUSAPIE() {

                var data = google.visualization.arrayToDataTable(<?= json_encode($users_regional);?>);

                //fix to display the value in legend
                var total = 0;
                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        total = total + data.getValue(i, 1);    
                }

                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        var label = data.getValue(i, 0);
                    var val = data.getValue(i, 1) ;
                    var percentual = ((val / total) * 100).toFixed(1); 
                    data.setFormattedValue(i, 0, label  + ' - '+val +' ('+ percentual + '%)');    
                }
                //fix to display the value in legend


                var options = {
                  title: 'Tickets Per User USA'
                };


                var chart = new google.visualization.PieChart(document.getElementById('byUserUSAPIE'));

                chart.draw(data, options);
              }

            /////////////////////////////
            //byUserPIE  - ASIA
            function byUserASIAPIE() {

                var data = google.visualization.arrayToDataTable(<?= json_encode($users_local);?>);

                //fix to display the value in legend
                var total = 0;
                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        total = total + data.getValue(i, 1);    
                }

                for (var i = 0; i < data.getNumberOfRows(); i++) {
                        var label = data.getValue(i, 0);
                    var val = data.getValue(i, 1) ;
                    var percentual = ((val / total) * 100).toFixed(1); 
                    data.setFormattedValue(i, 0, label  + ' - '+val +' ('+ percentual + '%)');    
                }
                //fix to display the value in legend


                var options = {
                  title: 'Tickets Per User ASIA'
                };


                var chart = new google.visualization.PieChart(document.getElementById('byUserASIAPIE'));

                chart.draw(data, options);
              }
      </script>
  </body>
</html>
