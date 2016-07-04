<?php
require_once('./config.php');

rb_dbsetup($db['server'], $db['user'], $db['password'], $db['db']);

require_once('./model.php');

global $context;
global $context_id;

//$context = 'instrument'; //'sector'; //'exchange';
if (isset($_GET['context']) && $_GET['context'] == 'exchange')
{
  $context = 'exchange';
  $context_id = $_GET['id'];
}
else if (isset($_GET['context']) && $_GET['context'] == 'sector')
{
  $context = 'sector';
  $context_id = $_GET['id'];
}
else if (isset($_GET['context']) && $_GET['context'] == 'instrument')
{
  $context = 'instrument';
  $context_id = $_GET['id'];
}
else
{
  $context = 'home';
  $context_id = 0;
}

?>
<!DOCTYPE html>
<html lang="en-gb">
<head>
    <?php
    echo '<title>Heraclitus</title>';
    ?>

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link href="./assets/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="./assets/css/bootstrap-responsive.min.css" rel="stylesheet"/>

    <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" rel="stylesheet">

    <link href="./assets/css/style.css" rel="stylesheet"/>    

    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    
    <link rel="shortcut icon" href="assets/img/favicon.png"/>

    <script type="text/javascript" src="//maps.googleapis.com/maps/api/js?sensor=false"></script> 

    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load('visualization', '1', {packages: ['corechart']});
    </script>

    <script type="text/javascript">
    function drawChart() {
      var data = google.visualization.arrayToDataTable(
      <?php
        switch ($context)
        {
          case 'sector':
            sector::history($context_id);
          break;

          case 'instrument':
             transaction::display($context_id);
             echo ', false';
          break;

          case 'exchange':
          case 'home':
            exchange::history();
          break;
        }
      ?>
      );

      <?php
        // Draw the chart.
        switch ($context)
        {
          case 'instrument':
            echo "var options = {
              legend: 'none'
            };

            var chart = new google.visualization.CandlestickChart(document.getElementById('visualization'));
            chart.draw(data, {legend:'none', width:900, height:400});";
          break;

          case 'exchange':
          default:
            echo "var options = {
              //title: 'Market Value',
              hAxis: {title: 'Timestamp',  titleTextStyle: {color: '#333'}},
              vAxis: {minValue: 0},
              width:900,
              height:400
            };

            var chart = new google.visualization.AreaChart(document.getElementById('visualization'));
            chart.draw(data, options)";
          break;
        }

      ?>
    }

    google.setOnLoadCallback(drawChart);
    </script>
    
    <!--?php include_once('analyticstracking.php') ?--> 

</head>
<body>
  <div class="row">
    <div class="col-lg-12">
      <div class="header header-div">
        <a href="./?"><img class="main-logo" src="./assets/img/heraclitus.png" /></a>
        <h3 class="cover-heading">Heraclitus</h3>
      </div>
    </div>
  </div>

  <div class="row-fluid">
      <div class="span1"></div>
      <div class="span2">
        <?php
          switch ($context)
          {
            case 'exchange':
              echo '<div class="title3">Sector</div>
              <hr class="divider">';
              sector::display_all_exchange($context_id);
            break;

            case 'sector':
              echo '<div class="title3">Instrument</div>
              <hr class="divider">';
              instrument::display_all_sector($context_id);
            break;

            case 'instrument':
              echo '<div class="title2">' . $context_id . instrument::delta($context_id) . '</div>
              <hr class="divider">';
            break;

            default: //case 'home':
              echo '<div class="title3">Exchange</div>
              <hr class="divider">';
              exchange::display_all();
            break;
          }
        ?>
        
      </div>

      <div class="span6">
        <div id="visualization" style="width: 900px; height: 400px;"> 
             
        </div>
      </div>
  </div>

  <div class="row-fluid">
      <div class="span1"></div>
      <div class="span9"> 
          <div class="text-center">
          <hr class="divider">
              <div class="title3">An <a href="http://olibe.nu">Olibenu</a> app.</div>
          </div>
      </div>
  </div>

</body>
</html>
