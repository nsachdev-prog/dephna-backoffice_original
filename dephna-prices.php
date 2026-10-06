<?php
$page = "Home";
include('settings.php');
include('session.php');
include('header.php');

if ($_SESSION['user']) {
	print "		<td class=\"main\" align=\"left\" valign=\"top\" style=\"padding-left:50px;\">";	
?>
<div id="dephna-prices">
<h1>Dephna Prices</h1>
<h2>Kitchens At Cumberland, Coronation and Acton Lane - approximate size 450 sqft</h2>
<ol>
  <li>&pound;1200 plus utilities at</li>
<li>&pound;1500 plus ut with 2m canopy min 1 year hire</li>
<li>3 month dep and 1 month advance</li>
</ol>
 
<h2>Kitchens At Portal and 190 Acton Lane - approximate size 450 sqft</h2>
<ol>
<li>&pound;1500 plus utilities</li>
<li>&pound;1800 with a 2m canopy min 1 year hire</li>
<li>3 month deposit and 1 month advance.</li>
</ol>
 
<h2>Hourly Kitchen Prices</h2>
<ol>
<li>&pound;30 per hour plus vat Monday to Friday 8am to 5pm</li>
<li>&pound;50 per hour from 5pm to 10pm Monday to Friday</li>
<li>Weekends &pound;50 per hour 8 am to 5pm only for regulars</li>
</ol>
 
<h2>Hourly Kitchen Deposits</h2>
<ol>
<li>&pound;150 deposit</li>
<li>&pound;1000 plus dep if client books kitchen for a month</li>
<li>&pound;30 for use of Dephna liability insurance</li>
</ol>
<h2>Cold Room (100sqft)</h2>
<ol>
<li>Long term 3 month hire  &pound;300 plus utility (3months dep and 1 month advance)</li>
<li>One month Hire &pound;300 plus utilities - 6 week dep and 1 month advance</li>
<li>One week hire &pound;135 plus VAT Plus UT - &pound;100 deposit ( all paid prior to use)</li>
<li>One day Hire &pound;35 plus VAT - (&pound;100 deposit all paid prior to use)</li>
<li>Hourly Overnight Storage &pound;25 plus VAT</li>
</ol>
<h2>Dry Storage (100sqft)</h2>
<ol>
  <li> &pound;175 plus VAT per month</li>
  <li>3 months deposit and 1 month in advance</li>
</ol>
<h2>Parking</h2>
<ol>
  <li>&pound;10 for a full day (only coronation hourly clients)</li>
  <li>&pound;5 half a day (and for all other sites)</li>
  <li>&pound;100 for month
    </p>
  </li>
</ol>
<h2>Fork Lift Hire</h2>
<ol>
  <li>   &pound;15 </li>
  <li>&pound;10 per pallet
    </p>
  </li>
</ol>
<h2>Photocopying/scanning </h2>
  <ol>
    <li> 20p per sheet</li>
    <li>10p per sheet
      </p>
    </li>
  </ol>
<h2>Meeting Rooms</h2>
<ol>
  <li>
    &pound;20 per hour
    </p>
  </li>
</ol>
<h2>Fobs&amp; key cards/lost/broken</h2>
<ol>
  <li>&pound;10  per copy</li>
</ol>
<h2>Telephone Line:</h2>
<ol>
  <li>  &pound;30 + VAT per Month </li>
  <li>Adapter Free but must be returned or &pound;5.00 fine
    </p>
  </li>
</ol>
<h2>Internet access to 1 room: </h2>
<ol>
  <li>  &pound;40.00 +VAT per mont</li>
  <li>Cat5 Internet Cable:10.00 one off charge
    </p>
  </li>
</ol>
<h2>Cardboard boxes not crushed:&nbsp;&nbsp; </h2>
<ol>
  <li>
    a fine of &pound;25.00 will be issued
    </p>
  </li>
</ol>
<h2>Offices Per Month (approximate)</h2>
<ol>
<li>1-2 man - from &pound;350* </li>
<li>2-3 man - from &pound;500*</li>
<li>4-5 man - from &pound;650*</li>
<li>5-6 man - from &pound;850*</li>
<li>10 man - from &pound;1200.00*</li>
</ol>
</div>
<?php
	print "		</td>\n";
}
include('footer.php');
?>
