?php
    // Initialize the session
    session_start();
    
    // If session variable is not set it will redirect to login page
    if(!isset($_SESSION['username']) || empty($_SESSION['username'])){
      header("location: login.php");
      exit;
    }
?>

<?php include 'connectdb.php';
    // Retrieve user name from current session
    $username = $_SESSION['username'];
    //printf ("%s\n",$username);
    // Select user_ID from username
    $sql = "SELECT user_ID FROM users WHERE username = '$username'";
    
    if ($result = mysqli_query($link, $sql)) {
            // Fetch one and one row
                while ($row = mysqli_fetch_row($result)) {
                    //printf ("%s\n",$row[0]);
                    $userID = $row['0'];
                }
            // Free result set
            mysqli_free_result($result);
            }
?>

<?php

	$post_at = "";
	$post_at_to_date = "";
	$queryCondition = "";
	
	//retrieve device_ID from dropdown menu
    $deviceID = $_POST["device"];

	//$post_at = date('Y-m-d');
	
	if(!empty($_POST["search"]["post_at"])) {
	    
		$post_at = $_POST["search"]["post_at"];
		list($fid,$fim,$fiy) = explode("-",$post_at);
		//$post_at = "$fiy-$fim-$fid";
	    
		
		//$post_at_todate = date('Y-m-d');
		
		if(!empty($_POST["search"]["post_at_to_date"])) {
			$post_at_to_date = $_POST["search"]["post_at_to_date"];
			list($tid,$tim,$tiy) = explode("-",$_POST["search"]["post_at_to_date"]);
			$post_at_todate = "$tiy-$tim-$tid";
		}
		
	    $queryCondition .= "AND readingtime BETWEEN '$fiy-$fim-$fid' AND '".$post_at_todate."'";
    
        $sql = "SELECT reading, alarm, readingtime from location WHERE device_ID = $deviceID " . $queryCondition . " ORDER BY readingtime asc";
    
	    //$sql = "SELECT reading, alarm from location WHERE device_ID = 1 AND readingtime BETWEEN '2017-12-30' AND '2018-01-02' ORDER BY readingtime desc";
	    $result = mysqli_query($link,$sql);
	}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Location</title>
    <!--responsive app-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <!--bar style-->
    <link rel="stylesheet" href="barstyle.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

    <!--ajax libraries-->
    <!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>-->
    <!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>-->
    <!--<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>-->
    <!--<script src="https://www.google.com/jsapi"></script>-->
    
    <!--calendar API-->
    <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
    <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
	<link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    
    <!--Date range style-->
    <style>
	.table-content{border-top:#CCCCCC 4px solid; width:50%;}
	.table-content th {padding:5px 20px; background: #F0F0F0;vertical-align:top;} 
	.table-content td {padding:5px 20px; border-bottom: #F0F0F0 1px solid;vertical-align:top;} 
	</style>
    
    <!--map style-->
    <style>
        html,
        body {
            height: 110%;
        }
       #map {
        height: 100%;
        width: 100%;
       }
    </style>
  </head>
  
  <body>
    <div class="part1">
        <ul>
            <li><a href="welcomesensor.php">+sensor</a></li>
            <!--map and report share the same session!-->
            <li><a href="location.php">map</a></li>
            <li><a href="report.php">report</a></li>
            <li><a href="logout.php">logout</a></li>
            <!--<li style="float:right"><a class="active" href="#about"> <?php echo $_SESSION['username']; ?></a></li>-->
        </ul>
        
    </div>
        
    <div class="panel panel-default">
        <div class="panel-body">
            <form name="frmSearch" method="post" action="" class="form-inline">
                
            	<!-- select device from dropdown menu -->
                <select id= "device" name= "device" class="form-control">   
                    <?php
                        //user_ID comes is in the context of every user session
                        $query = "SELECT device_ID,name FROM devices WHERE user_ID = $userID";
                        $result = mysqli_query($link,$query);
                    
                        while($row = mysqli_fetch_array($result)) {
                            //<option value="device_ID">name</option>
                            echo "<option value='" . $row['device_ID'] ."'>" . $row['name'] ."</option>";
                            
                        }
                        printf ("%s",$row['name']);
                        ?>
                </select>
                    
            	<input type="text" placeholder="From Date" id="post_at" name="search[post_at]"  value="<?php echo $post_at; ?>" class="input-control" />
            	<input type="text" placeholder="To Date" id="post_at_to_date" name="search[post_at_to_date]" value="<?php echo $post_at_to_date; ?>" class="input-control"  />
            	
            	<button type="submit" name="go" value="Search" class="btn btn-primary" >PM2.5</button>
            	<button type="submit" name="now" value="now" class="btn btn-primary" >PM2.5 now!</button>
            	<canvas width="350" height="30" id="colourbar"></canvas>
            	
            	
            	
            </form>
        </div>
        <?php 
            /* geolocation and pm25 used to display dots in the map
            	also, to save data in csv format
            */
            	    
            	    if($result = mysqli_query($link, $sql)){
            	        $geolocation = array();
                        $pm25 = array();
                        
                        $fp = fopen('pm25.csv', 'w');
                        while ($row = mysqli_fetch_array($result,MYSQLI_NUM)) {
                                fputcsv($fp, $row);
                            
                                //location field
                                $geolocation[] = $row['0'];
                                //printf, prints all the locations points
                                //printf ("%s (%s)\n",$row[1],$row[2]);
                                //echo json_encode($geolocation);
                                //pm25 field
                                $pm25[] = $row['1'];
                        }
                        fclose($fp);
                        // Free result set
                        mysqli_free_result($result);
                    }
        ?>
         <?php 
            /*query to fetch last PM2.5 value*/
            $deviceID = $_POST["device"];
            
            if(isset($_POST['now'])){
                
                $sql = "SELECT readingtime,reading, alarm FROM location WHERE device_ID = $deviceID ORDER BY readingtime DESC LIMIT 1";
                            
                $result = mysqli_query($link,$sql);
                            
                if ($result = mysqli_query($link, $sql)) {
                    
                    // Fetch last row  
                               
                    $timestamp = array();
                    $geolocation = array();
                    $pm25 = array();
                             
                    while ($row = mysqli_fetch_array($result,MYSQLI_NUM)) {
                                    
                            //timestamp column
                            $timestamp[] = $row['0'];
                                        
                            //location column
                            $geolocation[] = $row['1'];
                                        
                            //pm25 column
                            $pm25[] = $row['2'];
                            
                            //prints pm2.5 last inserted value
                            //printf ("PM2.5: %s ug/m3 at %s",$row[2],$row[0]);
                            printf ("last update: %s PM2.5: %s ug/m3",$row[0], $row[2]);
                           
                            }
                            // Free result set
                            mysqli_free_result($result);
                    }
            }
        ?>
    </div>
   
    <div class="panel-heading">
        <!-- download csv button -->
        <form action="http://www.smability.com/airquality/pm25.csv">
            <button type="submit" name="download" value="Download csv" class="btn btn-default" action="http://www.smability.com/airquality/pm25.csv">Download csv</button>
        </form>
    </div>

    <script>
        //date picker functionality
        $.datepicker.setDefaults({
                showOn: "button",
                buttonImage: "datepicker.png",
                buttonText: "Date Picker",
                buttonImageOnly: true,
                dateFormat: 'dd-mm-yy'  
            });
            
        $(function() {
                $("#post_at").datepicker();
                $("#post_at_to_date").datepicker();
        });
        
    </script>

    <!-- map field -->
    <div id="map"></div>
    
    <script>
        //coloured  bar
        var colourbar = document.getElementById('colourbar');
        var ctx = colourbar.getContext('2d');

        for (var pm25 = 0; pm25 < 1000; pm25++) {
    
            var x0 = pm25;
            var x1 = x0;
    
            var hue = -15.1*Math.log(1.0*pm25 + (1/(1.0*pm25)^2)) + 105;
            ctx.fillStyle = 'hsl(' + [hue, '70%', '50%'] + ')';
    
            ctx.fillRect(x0, 15, x1, 20);
            ctx.font = "10px Arial";
            ctx.textBaseline = "top";
            if (pm25==0){
            ctx.fillText(pm25, x0, 0);
            }else if (pm25==10){
            ctx.fillText(pm25, x0, 0);
            }else if (pm25==50){
            ctx.fillText(pm25, x0, 0);
            }else if (pm25==150){
            ctx.fillText(pm25, x0, 0);
            }else if (pm25==300){
            ctx.fillText(pm25, x0, 0);
            }
        }

        /*
        useful function for coldchain route monitoring
        
        var c = document.getElementById('canvas');
        var ctx = c.getContext('2d');
        var tmp = -10;
        // function to convert temperature in celcius to a color
        // temp range from -30 to 30
        function getColor(tmp) {
            var hue = 30 + 240 * (30 - tmp) / 60;
            ctx.fillStyle = 'hsl(' + [hue, '70%', '50%'] + ')';
            //fill the rectangle with the color
            ctx.fillRect(10, 10, 20, 20);
            // return color in hex
            return ctx.fillStyle;
            }
        console.log(getColor(tmp));
        */
        
    function initMap() {
        
        var latlngArray = <?php echo json_encode($geolocation); ?>; 
        var input = latlngArray[0];
        var latlngStr = input.split(",",2);
        var lat = parseFloat(latlngStr[0]);
        var lng = parseFloat(latlngStr[1]);
        
        var sensorlocation = new google.maps.LatLng(lat,lng);
        var map = new google.maps.Map(document.getElementById('map'), {
          zoom: 18,
          center: sensorlocation,
        });
        
        //alarm colum length,or table length,from current user, 
        var length;
        length = <?php echo sizeof($geolocation); ?>;
        //querying alarm colum, from current user, to pm array
        var pm = <?php echo json_encode($pm25); ?>;
        //querying location colum into an array
        var latlngArray = <?php echo json_encode($geolocation); ?>; 
        
        for (var i=0; i<length; i++){
            //pm25=alarm colum
            var pm25 = pm[i];
            //geolocation colum, string to float conversion
            var input = latlngArray[i];
            var latlngStr = input.split(",",2);
            var lat = parseFloat(latlngStr[0]);
            var lng = parseFloat(latlngStr[1]);
            //console.log(latlngArray[i],pm25);
            
            // function to convert PM2.5 to a color scale range 0 to 1000 particles
            function getColor(pm25) {
                
                var hue = -15.1*Math.log(1.0*pm25 + (1/(1.0*pm25)^2)) + 105;
                var color = 'hsl(' + [Math.round(hue), '70%', '50%'] + ')';
                
                //fill rectangle with the color
                //ctx.fillRect(10, 10, 20, 20);
                
                // return color in hex
                return color;
    
            }
          
            //device location
            latlngArray[i] = new google.maps.LatLng(lat, lng);
            
            //set the marker
            //output PM2.5 leves for every point
            //var markerLabel = pm[i];
            var marker = new google.maps.Marker({
            position:latlngArray[i],
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                fillColor: getColor(pm25),
                fillOpacity: 1,
                strokeWeight: 0,
                scale: 4
            },
            map: map,
            //label: markerLabel
            });
        }
        
      }
    </script>
    
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBKzkOLAEVB6KH5cpFCvT22GPBfh8gCnWo&callback=initMap&v=3.22">
    </script>
    
  </body>
</html>
