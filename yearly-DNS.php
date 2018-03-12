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

	$start = mktime(0, 0, 0, 1, 1, $year);
	$stop = mktime(23, 59, 59, 12, 31, $year);

	for($i = 1; $i <= 12; $i++)
        {
		$s1 = mktime(0, 0, 0, $i, 1, $year);
                $s2 = mktime(23, 59, 59, $i+1, 0, $year);

		$query = "select unix_timestamp(`when`) as `when`, `config`, `config`+`cached`+`forwarded` as `total` from `daily` where `when` >= from_unixtime('$s1') and `when` <= from_unixtime('$s2') limit 1";
		$res = mysqli_query($link, $query);
                if(mysqli_num_rows($res) <= 0)
                {
			if($year != date("Y") || $i != date("m"))
			{
                                if($data != "")
                                        $data .= ',';
                                $data .= "null";

                                if($data2 != "")
                                        $data2 .= ',';
                                $data2 .= "null";
			} else {
				$query = "select `when`, sum(`config`) as `config`, sum(`config`)+sum(`cached`)+sum(`forwarded`) as `total` from `dnsStats` where `when` >= from_unixtime('$s1') and `when` <= from_unixtime('$s2') limit 1";
echo $query."<br>\n";
                                $dres = mysqli_query($link, $query);
                                $drow = mysqli_fetch_assoc($dres);

                                if($data != "")
                                        $data .= ',';
                                $data .= $drow['total'];

                                if($data2 != "")
                                        $data2 .= ',';
                                $data2 .= "".$drow['config'];

                                $lastTS = $drow['when'];

                                if($firstTS == 0)
                                        $firstTS = $lastTS;
                        }
		} else {
			$row = mysqli_fetch_assoc($res);

                        if($data != "")
                                $data .= ',';
                        $data .= $row['total'];

                        if($data2 != "")
                                $data2 .= ',';
                        $data2 .= "".$row['config'];

                        $lastTS = $row['datetime'];

                        if($firstTS == 0)
                                $firstTS = $lastTS;
		}
	}

	$seconds = 3600;

        if($firstTS == 0)
                $firstTS = $start;

        if($lastTS == 0)
                $lastTS = $stop;

        $from = date("Y-m-d g:i A", $firstTS);
        $to = date("Y-m-d g:i A", $lastTS);

        $count = 0;
        for($i = 1; $i <= 12; $i++)
        {
		$s1 = mktime(0, 0, 0, $i, 1, $year);

                if($showcat != "")
                        $showcat .= ',';

                $showcat .= "'".date("F", $s1)."'";
        }

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
    <meta http-equiv="refresh" content="<?=$seconds?>">
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
            type: 'bar',
            data: {
                labels: [<?=$showcat?>],
                datasets: [{
                    label: 'DNS Requests',
                    backgroundColor: window.chartColors.blue,
                    data: [<?=$data?>],
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
            type: 'bar',
            data: {
                labels: [<?=$showcat?>],
                datasets: [{
                    label: 'DNS Blocked',
                    backgroundColor: window.chartColors.red,
                    data: [<?=$data2?>],
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
