<?php
/**
*PhP Script for autocomplete js library
*/
header("Access-Control-Allow_Origin: *");
header("Content-Type: text/json");
ini_set("memory_limit","1024M");

/**
*Database Parameter
*/
$db_server = ""; //this can be set to your database server ip if not the same as application server.
$db_user = ""; //this should be your database username create other username don't use root
$db_pass = ""; //Enter yout database password here. It is bad practice to allow user with password on your database server.
$db = ""; //Set this to the database that contains your datasource for the autocomplete

//POST data received from client on server
$crit = $_POST["criteria"];
$tablename = $_POST['table_name'];
$columnname = $_POST['column_name'];

//make database connection or use a database library
$db = new mysqli($db_server, $db_user, $db_pass, $db);

if(isset($crit) && !empty($crit)){
//Query the specified table and column for data
$sql = "select {$columnname} from {$tablename} where {$columnname} like '%{$crit}%'";
$dsql = $db->query($sql);

//Initialize your data array and set it to empty
$data = array();

/**
Fetch your data from the datasource resource associating to row 
becuase of convertion to json that will use simpleXml reader
which may have issue with numbered keys.
*/
while($row = $dsql->fetch_assoc()){
    $data[] = $row;
}

//convert to json and echo it out for the client.
echo json_encode($data);
}else{
	$msg["msg"] = "Data not Found";
	echo json_encode($msg);
}