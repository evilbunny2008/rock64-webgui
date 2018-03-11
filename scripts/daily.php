#!/usr/bin/php
<?php
	if(getmyuid() != 0)
	{
		echo "You must run this script as root.\n";
		exit(1);
	}

	require_once("/var/www/html/mysql.php");

	$query = "select unix_timestamp(`when`) as `when` from `dnsStats` order by `when` ASC limit 1";
        $res = mysqli_query($link, $query);
        $row = mysqli_fetch_assoc($res);

        $start = 0;
        if(isset($row['when']))
                $start = $row['when'];

        if($start == 0)
        {
                echo "Unable to find start time in the database\n";
                exit;
        }

        $start = date("U", mktime(0, 0, 0, date("m", $start), date("d", $start), date("Y", $start)));
        $now = date("U") - 86400;

	for($i = $start; $i <= $now; $i += 86400)
	{
		$query = "select * from `daily` where `when`=from_unixtime('$i')";
		$res = mysqli_query($link, $query);
                if(mysqli_num_rows($res) > 0)
                        continue;

		$query = "select sum(`cached`) as `cached`, sum(`config`) as `config`, sum(`forwarded`) as `forwarded` from `dnsStats` where `when`>=from_unixtime('$i') and `when` <= from_unixtime('".($i + 86399)."')";
		$res = mysqli_query($link, $query);
                $row = mysqli_fetch_assoc($res);

		$query = "insert into `daily` set `when`=from_unixtime('$i'), `cached`='${row['cached']}', `config`='${row['config']}', `forwarded`='${row['forwarded']}'";
		mysqli_query($link, $query);
	}
