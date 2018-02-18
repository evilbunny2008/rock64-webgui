<?php
        session_start();

        if($_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$_SESSION['login'] = false;
	$do = `sudo /var/www/html/scripts/reboot.sh > /dev/null 2>/dev/null &`;

        $pageTitle = "System Rebooting...";
        include_once("header.php");
?>
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row" style="padding-right:15px;padding-left:15px;">
                    <div class="col-md-12">
                        <h2><?=$pageTitle?></h2>
                    </div>
                </div>
                <hr />
                <div class="row" style="padding-right:15px;padding-left:15px;">
                    <p>Please wait while the system is rebooted.</p>
                </div>
            </div>
        </div>
<?php include_once("footer.php"); ?>
