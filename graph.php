<?php
        session_start();

	if(!isset($_SESSION['login']) || $_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	require_once("/var/www/html/mysql.php");

	$okmsg = $errmsg = "";

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
    <script src="assets/js/Chart.bundle.min.js"></script>
    <meta http-equiv="refresh" content="300">
</head>
<body>
    <canvas id="myChart" height="40vh" width="80vw"></canvas>
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

<?php
        $date = date("Ymd");

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
                        $data .= "'".date("H", $row['dt']).":00', ";
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
                        if($data != "")
                                $data .= ',';
                        $data .= '';

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

        $from = date("Y-m-d g:i A", $firstTS);
        $to = date("Y-m-d g:i A", $lastTS);
?>
        var ctx = document.getElementById("myChart");
        var myChart = new Chart(ctx,
        {
            type: 'line',
            data: {
                labels: [<?=$data?>],
                datasets: [{
                    label: 'DNS Requests',
                    backgroundColor: window.chartColors.blue,
                    data: [<?=$data2?>],
                    borderWidth: 1
                },
                {
                    label: 'DNS Blocked',
                    backgroundColor: window.chartColors.red,
                    data: [<?=$data3?>],
                    borderWidth: 1
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
    </script>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
