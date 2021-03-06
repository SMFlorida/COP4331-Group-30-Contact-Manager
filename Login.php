<?php
$serverKey = '5f2b5cdbe5194f10b3241568fe4e2b24';

$inData = getRequestInfo();

$UserID = 0;
$Username = "";

//Making sure only method called is POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') 
{
	exit;
}
 
if (IsNullOrEmptyString($inData["Username"]) && IsNullOrEmptyString($inData["Password"]))
{
	// Bad Request
	http_response_code ( 400 );
	returnWithError( "Username and Password are required for Login!" );
}
elseif (IsNullOrEmptyString($inData["Username"]))
{
	// Bad Request
	http_response_code ( 400 );
	returnWithError( "Username is required for Login!" );
}
elseif (IsNullOrEmptyString($inData["Password"]))
{
	// Bad Request
	http_response_code ( 400 );
	returnWithError( "Password is required for Login!" );
}

else
{
	$conn = new mysqli("localhost", "cop4311g_30", "Copcop24!!", "cop4311g_contactmanager");
	
	if ($conn->connect_error)
	{
		// Server error
		http_response_code ( 500 );
		returnWithError( $conn->connect_error );
	}
	else
	{
		$sql = "SELECT UserID, Password, Name, DateLastLoggedIn FROM User WHERE Username='" . $inData["Username"] . "'";
		$result = $conn->query($sql);

		// Check if username matched as well as password verified
		if ($result->num_rows > 0 && password_verify($inData["Password"], ($row = $result->fetch_assoc())["Password"]))
		{
			$name = $row["Name"];
			$lastLogin = $row["DateLastLoggedIn"];
			$UserID = $row["UserID"];

			$sql = "UPDATE User SET DateLastLoggedIn=NOW() WHERE UserID=". $UserID;
			$conn->query($sql);
			
			returnWithInfo( $UserID, $name, $lastLogin );
		}
		else
		{
			// 401 - Unauthorized
			http_response_code ( 401 );
			returnWithError( "Username or password don't match" );
		}
		
		// Cleanup
		$conn->close();
	}
}
	
function getRequestInfo()
{
	return json_decode(file_get_contents('php://input'), true);
}

function sendResultInfoAsJson( $obj )
{
	header('Content-type: application/json');
	echo $obj;
}

function returnWithError( $err )
{
	$retValue = '{"error" :"' . $err . '"}';
	sendResultInfoAsJson( $retValue );
}

function returnWithInfo( $UserID, $name, $lastLogin )
{
	global $serverKey;
	
	require_once('jwt.php');

	// Encode UserID into a token
	$payloadArray = array();
	$payloadArray['userId'] = $UserID;
	$token = JWT::encode($payloadArray, $serverKey);

	// Respond with token
	sendResultInfoAsJson( '{"name": "' . $name . '", "lastLogin": "' . $lastLogin . '", "token": "' . $token . '"}' );
}

function IsNullOrEmptyString($str){
	return (!isset($str) || trim($str) === '');
}
?>
