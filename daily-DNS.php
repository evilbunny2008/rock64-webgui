<?php
        session_start();

	if(!isset($_SESSION['login']) || $_SESSION['login'] != true)
        {
                header("location: /login.php");
                exit;
        }

	require_once("/var/www/html/mysql.php");

	$okmsg = $errmsg = "";

        $date = date("Ymd");

        if(isset($_REQUEST['fulldate']))
                $date = str_replace("-", "", $_REQUEST['fulldate']);

        if(isset($_REQUEST['date']))
                $date = $_REQUEST['date'];

        if(isset($_REQUEST['timeofday']) && $_REQUEST['timeofday'] == 'Yearly Graph')
        {
                header("location: yearly-DNS.php");
                exit;
        }

        if(isset($_REQUEST['timeofday']) && $_REQUEST['timeofday'] == 'Monthly Graph')
        {
                header("location: monthly-DNS.php");
                exit;
        }

        if(isset($_REQUEST['timeofday']) && $_REQUEST['timeofday'] == 'Daily Graph')
        {
                header("location: daily-DNS.php");
                exit;
        }

        if(isset($_REQUEST['timeofday']) && $_REQUEST['timeofday'] == '<<')
        {
                $year = substr($date, 0, 4);
                $mon = substr($date, 4, 2);
                $day = substr($date, 6, 2);

                $date = date("Ymd", mktime(0,0,0,$mon,$day-1,$year));
        }

        if(isset($_REQUEST['timeofday']) && $_REQUEST['timeofday'] == '>>')
        {
                $year = substr($date, 0, 4);
                $mon = substr($date, 4, 2);
                $day = substr($date, 6, 2);

                $date = date("Ymd", mktime(0,0,0,$mon,$day+1,$year));
        }

        $data = $data2 = $data3 = "";

        $year = substr($date, 0, 4);
        $mon = substr($date, 4, 2);
        $day = substr($date, 6, 2);

        $firstTS = 0;
        $lastTS = $start = date("U", mktime(0, 0, 0, $mon, $day, $year));
        $stop = $start + 86399;

        $query = "select *,UNIX_TIMESTAMP(`when`) as `dt` from `dnsStats` where `when` >= from_unixtime('$start') and `when` <= from_unixtime('$stop')";
        $res = mysqli_query($link, $query);
        while($row = mysqli_fetch_assoc($res))
        {
                if(date("i", $row['dt']) == 0)
                {
                        $data .= "'".date("H:i", $row['dt'])."', ";
                } else
                        $data .= "'', ";

                if($data2 != "")
                        $data2 .= ',';
                $data2 .= $row['forwarded'] + $row['cached'] + $row['config'];

                if($data3 != "")
                        $data3 .= ',';
                $data3 .= $row['config'];

                $lastTS = $row['dt'];
                if($firstTS == 0)
                        $firstTS = $lastTS;
        }

        if($lastTS == 0)
                $lastTS = $start;

        if($lastTS < $stop - 299)
        {
                for($i = $lastTS; $i <= $stop - 299; $i += 300)
                {
			$hr = date("H", $i);
			$min = date("i", $i);

                        if($data != "")
                                $data .= ',';

			if($min == 0)
				$data .= "'$hr:00'";

                        if($data2 != "")
				$data2 .= ",";
                        $data2 .= 'null';

                        if($data3 != "")
                                $data3 .= ',';
                        $data3 .= 'null';
                }
        }

        if($firstTS == 0)
        {
                $firstTS = $start;
                $lastTS = $stop;
        }

	$seconds = 300;

        $from = date("Y-m-d g:i A", $firstTS);
        $to = date("Y-m-d g:i A", $lastTS);

	$pageTitle = "Blacklist Settings";
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?=$pageTitle?></title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="webgui.css" rel="stylesheet" />
    <script src="assets/js/Chart.bundle.js"></script>
    <meta http-equiv="refresh" content="<?=$seconds?>;URL='<?=$_SERVER['SCRIPT_NAME']?>'">
</head>
<body>
        <form style="text-align:center;max-width:950px;padding-top:10px;min-height:25px;" method="GET" action="<?=$_SERVER['PHP_SELF']?>">
                <input class="button" type="submit" name="timeofday" value="<<" />
                <input class="button" type="submit" name="timeofday" value="Yearly Graph" />
                <input class="button" type="submit" name="timeofday" value="Monthly Graph" />
                <input class="button" type="submit" name="timeofday" value="Daily Graph" />
                <input class="button" type="submit" name="timeofday" value=">>" />
                <input type="hidden" name="date" value="<?=$date?>" />
        </form>
    <canvas id="myChart1" height="25vh" width="78vw"></canvas>
    <canvas id="myChart2" height="25vh" width="78vw"></canvas>
    <span id="seconds"><?=$seconds?></span>s before reload.
    <script type="text/javascript" charset="utf-8">
	window.chartColors = {
		red: 'rgb(255, 99, 132)',
		orange: 'rgb(255, 159, 64)',
		yellow: 'rgb(255, 205, 86)',
		green: 'rgb(75, 192, 192)',
		blue: 'rgb(54, 162, 235)',
		purple: 'rgb(153, 102, 255)',
		grey: 'rgb(231,233,237)'
	};

        var ctx1 = document.getElementById("myChart1");
        var myChart1 = new Chart(ctx1,
        {
            type: 'line',
            data: {
                labels: [<?=$data?>],
                datasets: [{
                    label: 'DNS Requests',
                    backgroundColor: window.chartColors.blue,
                    data: [<?=$data2?>],
                    pointRadius: 1,
                    pointHoverRadius: 5,
                    pointHitRadius: 5,
		    fill: true,
                }]
            },
            options: {
                title: { display: true, text: '<?=$from.' - '.$to?>' },
		tooltips: { mode: 'index', intersect: false },
                hover: { mode: 'nearest', intersect: true },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero:true
                        }
                    }]
                }
            }
        });

        var ctx2 = document.getElementById("myChart2");
        var myChart2 = new Chart(ctx2,
        {
            type: 'line',
            data: {
                labels: [<?=$data?>],
                datasets: [{
                    label: 'DNS Blocked',
                    backgroundColor: window.chartColors.red,
                    data: [<?=$data3?>],
                    pointRadius: 1,
                    pointHoverRadius: 5,
                    pointHitRadius: 5,
		    fill: true,
                }]
            },
            options: {
		tooltips: { mode: 'index', intersect: false },
                hover: { mode: 'nearest', intersect: true },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero:true
                        }
                    }]
                }
            }
        });

        var seconds = "<?=$seconds?>";
        setTimeout('location.reload(true)', seconds * 1000);

        function updateClock()
        {
            setTimeout('updateClock()', 1000);
            document.getElementById("seconds").innerHTML = --seconds;
        }

        setTimeout('updateClock()', 1000);

    </script>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
