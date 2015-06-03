
  <?php
/*

Notes for dmcd: (this script pulls data from angellist)

1.Script to connect to mySQL DB "crunchiot" and select table "angeliot" 
2.then sets up row of field names
3. uses cURL to pull JSON data from iot category of Crunchbase API: category_uuids=ed3a589dc9a73cbb9feb245f011e1d54
4. holds data in an array which is looped through with SQL which INSERTS into db table: `crunchiot`.`iotCompanies`
5. SQL order is echoed and success is printed (ideally)
*/

{   //Connect and Test MySQL and specific DB (return $dbSuccess = T/F)
        
      $hostname = "crunchiot.db.10718538.hostedresource.com";
      $username = "crunchiot";
      $password = "Crunchiot!1";      
      $databaseName = "crunchiot";


      $dbConnected = @mysql_connect($hostname, $username, $password);
      $dbSelected = @mysql_select_db($databaseName,$dbConnected);

      $dbSuccess = true;
      if ($dbConnected) {
        if (!$dbSelected) {
          echo "DB connection FAILED<br /><br />";
          $dbSuccess = false;
        }   
      } else {
        echo "MySQL connection FAILED<br /><br />";
        $dbSuccess = false;
      }
}  

  //Execute code ONLY if connections were successful  
if ($dbSuccess) {
  
  { //Setup ARRAY of field names 
    $iotCoField = array(
          '`id`' => '`id`',
          '`hidden`' => '`hidden`',
          '`updated`' => '`updated`',
          '`created`' => '`created`',
          '`name`' => '`name`',
          '`url`' => '`url`',     
    );
}

 //Use cURL to pull iot category companies from Crunchbase API & setup ARRAY of data ROWS
    //cycle through pages
    for($j=1; $j<18;$j++){

    //Initiaize cUrl
    $ch = curl_init();
    
    //set the url
    $url = 'https://api.angel.co/1/tags/2462/startups?page='.$j;    
    //Set options   
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);    
  //Execute 
    $var[$j] = curl_exec($ch); 
    //Close curl session / free resources
    curl_close($ch);

    //Decode the json string into an array
     $json = array_merge((array)$json,(array)json_decode($var[$j], true));
                                    
    //Loop through the results
    for($i=0; ($i<$json['per_page']); $i++){ 
      if(($json['startups'][$i]['hidden']) == "") {$json['startups'][$i]['hidden'] = "false"; } else { $json['startups'][$i]['hidden'] = "true"; }
      
       $iotCoData[$i] = array(
             $json['startups'][$i]['id'],
             $json['startups'][$i]['hidden'],
             //convert from UTC time to Unix Timestamp
             strtotime($json['startups'][$i]['updated_at']),
             strtotime($json['startups'][$i]['created_at']),
             $json['startups'][$i]['name'],
             $json['startups'][$i]['company_url']);
}


       $numRows =  sizeof($iotCoData);
      
    


{ //SQL statement with ARRAYS -> fieldnames part of INSERT statement 
    $iotSQLinsert = 'INSERT INTO `crunchiot`.`angeltestplus` (
                  '.$iotCoField['`id`'].',
                  '.$iotCoField['`hidden`'].',
                  '.$iotCoField['`updated`'].',
                  '.$iotCoField['`created`'].',
                  '.$iotCoField['`name`'].',
                  '.$iotCoField['`url`'].'
                  )';
                
  //VALUES  part of INSERT statement                  
    $iotSQLinsert .=  "VALUES ";      
    
    $i = 0;   
    while($i < $numRows) {      
      $iotSQLinsert .=  "(
                    '".$iotCoData[$i][0]."',
                    '".$iotCoData[$i][1]."',
                    '".$iotCoData[$i][2]."',
                    '".$iotCoData[$i][3]."',
                    '".$iotCoData[$i][4]."',
                    '".$iotCoData[$i][5]."'
                    )"; 

      if ($i < ($numRows - 1)) {
        $iotSQLinsert .=  ",";
      } 
      $i++;
    }

  //ON DUPLICATE KEY (if company record exists, update instead of insert)
     $iotSQLinsert .=  'ON DUPLICATE KEY UPDATE

                  '.$iotCoField['`id`']."="." VALUES(".$iotCoField['`id`'].')'.',
                  '.$iotCoField['`hidden`']."="." VALUES(".$iotCoField['`hidden`'].')'.',
                  '.$iotCoField['`updated`']."="." VALUES(".$iotCoField['`updated`'].')'.',
                  '.$iotCoField['`created`']."="." VALUES(".$iotCoField['`created`'].')'.',
                  '.$iotCoField['`name`']."="." VALUES(".$iotCoField['`name`'].')'.',
                  '.$iotCoField['`url`']."="." VALUES(".$iotCoField['`url`'].')';

 

} 

{ //Echo and Execute the SQL and test for success   
    echo "<strong><u>SQL:<br /></u></strong>";
    echo $iotSQLinsert."<br /><br />";
      
    if (mysql_query($iotSQLinsert))  {        
      echo "was SUCCESSFUL.<br /><br />";
    } else {
      echo "FAILED.<br /><br />";   
    }

  }

}
 }     
 //END ($dbSuccess)

?>