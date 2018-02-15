<?php
        session_start();

        if($_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$page = 7;
	$pageTitle = "OpenVPN Settings";
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
		    Coming soon!
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
