<?php
	include('settings.php');
	include('session.php');
	$jquery = 1;
	include('elements/header.php');
?>

<style>
	#parking h5 {font-size:1.125rem;}
	#parking .parking-site,#parking .parking-bay{font-weight:bold;font-size:1.25em;}
	#parking .parking-space{
		padding:2px;
	}
	#parking .parking-space span{background:#ffcd20;color:black;padding:5px 8px;border:2px solid black;border-radius:3px;font-weight:bold;font-family: Arial, sans-serif;letter-spacing: 0.05em;}
	#parking select.dis-select{width:100%;}
	#parking input.dis-field{width:100%;border:2px solid black;border-radius:3px; padding:2px;margin-bottom:10px;margin-top:10px;}
</style>
			<div class="row text-center main-title">
				<div class="col-lg-6 offset-lg-3 mt-1">
					<h1 class="page-header__title">Client Area</h1>
					<h2 class="page-header__subtitle">Instruction Videos</h2>
				</div>
			</div>
			<div class="row text-center">
				<div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
		
        ?>

        <p style="text-align:center;background-color:#eee">
            <span style="font-size:1.2em;font-weight:bold">In The Event of Fire</span><br>
            <video width="600" controls poster="images/vidposter-firealarm.png">
                <source src="../backoffice/pdf/Dephna-In-The-Event-of-Fire.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </p>

        <p style="text-align:center;background-color:#eee">
            <span style="font-size:1.2em;font-weight:bold">Fire, False Alarm</span><br>
            <video width="600" controls poster="images/vidposter-falsealarm.png">
                <source src="../backoffice/pdf/Dephna-False-Alarm-in-Unit.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </p>

        <p style="text-align:center;background-color:#eee">
            <span style="font-size:1.2em;font-weight:bold">Kitchens</span><br>
            <video width="600" controls poster="images/vidposter-kitchen.png">
                <source src="../backoffice/pdf/Dephna-Kitchen-Onboarding.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </p>

        <p style="text-align:center;background-color:#eee">
            <span style="font-size:1.2em;font-weight:bold">Cold Rooms</span><br>
            <video width="600" controls poster="images/vidposter-coldroom.png">
                <source src="../backoffice/pdf/Dephna-Coldroom-Onboarding.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </p>

        <p style="text-align:center;background-color:#eee">
            <span style="font-size:1.2em;font-weight:bold">Use of Compactor and Bailer</span><br>
            <video width="600" controls poster="images/vidposter-compactor.png">
                <source src="../backoffice/pdf/Dephna-Use-of-Compactor-and-Bailer.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </p>

        <p style="text-align:center;background-color:#eee">
            <span style="font-size:1.2em;font-weight:bold">Deliveries, Dispatch and Parking</span><br>
            <video width="600" controls poster="images/vidposter-deliveries.png">
                <source src="../backoffice/pdf/Dephna-Deliveries-Dispatch-and-Parking.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </p>

        <p style="text-align:center;background-color:#eee">
            <span style="font-size:1.2em;font-weight:bold">Intercom Setup</span><br>
            <video width="600" controls poster="images/vidposter-intercom.png">
                <source src="../backoffice/pdf/Dephna-Intercom-Setup-v3.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </p>

        <?php
		
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}

	print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";
?>
		</div>
	</div>

<?php			
	include('elements/footer.php');
?>
