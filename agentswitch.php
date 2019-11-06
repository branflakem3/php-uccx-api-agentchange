<?php
// CSS for On/Off Button
echo '<style>
.onoff {
    width: 50%;
    width: 36px;
    height: 24px;
    margin: 0 auto;
	display: block;
	text-align: center;
    background-image: url(./icons/Off_Icon_sm.png);
}
.onoff:hover {
    background-image: url(./icons/On_Icon_sm.png);
}
</style>';
//==========================================//
// Set variables below to your environment  //
//==========================================//
$apiserver = "x.x.x.x";
$apibasicauth = base64_encode("UCCX_ADMIN_USER:PASSWORD");
$currentpageurl = "site/currentpage.php"
$queue1 = "Customer Service Grp";
$queue1ResGrpID = '2';
$queue2 = "Operator Grp";
$queue2ResGrpID = '3';

//==========================================//
// Begin Code - See Exclusion section to    //
// exclude specific extensions from listing //
//==========================================//
if ($_GET['user']) {
    $api_url2 = "https://".$apiserver."/adminapi/resource/".$_GET['user'];
	$headers2 = array('http'=>
		array(
		'method'=>"GET",
		'header'=>"Accept: Application/JSON\r\n" .
"Authorization: Basic ".$apibasicauth."\r\n"
	)
	);
	$context2 = stream_context_create($headers2);
	// Read JSON file
	$json_data2 = file_get_contents($api_url2, false, $context2);
	// Decode JSON data into PHP array
	$user_data2 = json_decode($json_data2, true);
	switch ($user_data2['resourceGroup']["@name"]) {
		case $queue1:
			$mvto = $queue2;
			$resourceGrpID=$queue2ResGrpID;
			break;
		case $queue2:
			$mvto = $queue1;
			$resourceGrpID=$queue1ResGrpID;
			break;
	}
	$user_data2['resourceGroup']["@name"] = $mvto;
	$resURL="https://".$apiserver."/adminapi/resourceGroup/".$resourceGrpID;
	$user_data2['resourceGroup']["refURL"] = $resURL;
	$json_data2 = json_encode($user_data2);

// SUBMIT JSON DATA TO SERVER=================================================
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $api_url2,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "PUT",
      CURLOPT_POSTFIELDS => $json_data2,
      CURLOPT_HTTPHEADER => array(
        "accept: Application/JSON",
        "authorization: ".$apibasicauth."\r\n",
        "content-type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
    } else {
      echo $response;
    }
	echo "<h2>Applying requested change to system...REFRESHING</h2>";
// REFRESH PAGE TO UPDATE TABLE
    header( "refresh:1");
}

//RESOURCE LISTING BELOW THIS LINE============================================
$api_url = 'https://'.$apiserver.'/adminapi/resource/';

$headers = array('http'=>
		array(
		'method'=>"GET",
		'header'=>"Accept: Application/JSON\r\n" .
"Authorization: Basic ".$apibasicauth."\r\n"
	)
);
$context = stream_context_create($headers);

// Read JSON file
$json_data = file_get_contents($api_url, false, $context);

// Decode JSON data into PHP array
$user_data = json_decode($json_data, true);

//SORT User Data
usort($user_data['resource'],function($a,$b){ return $a['firstName'] < $b['firstName'] ? -1 : 1;});

// Traverse array and display user data
echo "<table>";
echo "<tr><th>Name</th><th>Group</th><th>Ext</th><th>Switch?</th></tr>";

$resourcecount = count($user_data['resource']);
$usernumber=0;

//==========================================//
// Exclusion Section-Extensions to not list //
//==========================================//
//Prevent the following users from being switched with this utility
$userextensions = array("1234","1235","1236","1237");
//Don't even list the users below
$excludedusers = array("1234","1235");

foreach ($user_data['resource'] as $key => $value) {
	$colortag = "";
    switch ($value["resourceGroup"]["@name"]) {
        case "Customer Service Grp":
    		$colortag = "style='background-color:#b3ffb3'";
            break;
        case "Main Number Grp":
            $colortag = "style='background-color:#ccffff'";
            break;        
	}
    $switchurl = "http://".$currentpageurl."?user=" . $value["userID"] . "&ID=".$usernumber;
    
	$switchcode = "<a href=javascript:SubmitIt('". $switchurl ."','". $value["firstName"] . "','" . $value["lastName"] ."'); class='onoff'>";
   
    // Disable switching for extensions that are listed above in the $userextensions variable
    if (in_array($value["extension"],$userextensions)) {
        $switchcode = " ";
    }
    // Hide users listed above in the $excludedusers variable
    if (in_array($value["extension"],$excludedusers)) {
        //nothing needed to hide them
    }
    //List the rest
    else {
        echo "<tr ".$colortag."><td>" . $value["firstName"] . " " . $value["lastName"] . "</td><td>" . $value["resourceGroup"]["@name"] . "</td><td>" . $value["extension"] . 
        "</td><td>".$switchcode."</td></tr>";
    }
    
    $usernumber++;
  }
echo "</table>";
echo '<script type="text/javascript">

function SubmitIt(submiturl,fname,lname) {
var answer = confirm ("WARNING: Are you sure you want to switch the user below??\r\n"+ fname +" "+ lname)
if (answer)
window.location=submiturl;
}
</script>
';

?>
