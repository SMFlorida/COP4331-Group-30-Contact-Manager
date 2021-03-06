<?php
	// User actions on a SET of contacts
	// All methods require a JWT token (userid)
	// 
	// GET - search a contact accodring to a query and page number
	// 
	// 
	// 
	// ANY OTHER METHOD - "Method Not Supported"
	// 
	switch ($_SERVER['REQUEST_METHOD'])
	{
		case 'GET':
			// $inData = json_decode(file_get_contents("php://input"), true);
			$serverKey = '5f2b5cdbe5194f10b3241568fe4e2b24';
			$ITEMS_PER_PAGE = 5;

			$inData = array();
			parse_str($_SERVER['QUERY_STRING'], $inData);

			// returnWithError( $_SERVER['HTTP_X_ACCESS_TOKEN'] );

			$searching = isset($inData['search']) ? $inData['search'] : '';
			$userId = 0;
			$token = '';
			$page = 0;

			// Check request body
			// (1) Token
			$userId = check_token($_SERVER["HTTP_X_ACCESS_TOKEN"]);


			// Check request body
			// (2) Page
			if (isset($inData["page"]))
			{
				if (!ctype_digit($inData["page"]))
				{
					// Bad Request
					http_response_code ( 400 );
					returnWithError( "Page must be an integer!" );
				}
				$page = (int)$inData["page"];
			}
			

			// Connect to database
			$conn = new mysqli("spadecontactmanager.com", "cop4311g_30", "Copcop24!!", "cop4311g_contactmanager");
			if ($conn->connect_error)
			{
				// Server error - unable to connect to db
				http_response_code(500);
				returnWithError($conn->connect_error);
			}
			else // Connected successfully
			{
				// $searching
				// $token
				// $page
				$datas = array();
				$startingPage = $page * $ITEMS_PER_PAGE;

				$sql = "SELECT ContactID as id, FirstName as firstName, LastName as lastName,
						EmailAddress as email, PhoneNumber as phone, Address as address, DateContactMade as createDate FROM Contact
						WHERE UserId = $userId AND (FirstName LIKE '%$searching%'
													OR LastName LIKE  '%$searching%'
													OR EmailAddress LIKE  '%$searching%'
													OR PhoneNumber LIKE  '%$searching%')
						ORDER BY FirstName, LastName
						LIMIT $startingPage, $ITEMS_PER_PAGE
					";
				$result = $conn->query($sql);

				$sql2 = "SELECT ContactID as id, FirstName as firstName, LastName as lastName,
						EmailAddress as email, PhoneNumber as phone, Address as address FROM Contact
						WHERE UserId = $userId AND (FirstName LIKE '%$searching%'
													OR LastName LIKE  '%$searching%'
													OR EmailAddress LIKE  '%$searching%'
													OR PhoneNumber LIKE  '%$searching%')
					";
				$result2 = $conn->query($sql2);
				//Counts total number of people that are being shown
				$resultcount = $result2->num_rows;
				
				//Calculating total number of pages displayed
				$total_pages = ceil($resultcount / $ITEMS_PER_PAGE);
				//$page_count = floor($resultcount / $ITEMS_PER_PAGE);

				//echo $page_count;
				// echo "123";

				// output data of each row
				while ($row = mysqli_fetch_assoc($result))
				{
					$datas[] = $row;
				}

				// echo "444";

				// echo "[]";
				// sendResultInfoAsJson( "{}" );
				sendResultInfoAsJson( "{\"contacts\": " . json_encode($datas) . ", \"total_pages\": ". $total_pages . "}" );

				// Cleanup
				$conn->close();
			}
			break;
	
		default:
			http_response_code ( 405 );
			returnWithError( "Method '" . $_SERVER['REQUEST_METHOD'] . "' not supported!" );
			break;
	}

	function getRequestInfo()
	{
		return json_decode(file_get_contents('php://input'), true);
	}

	function sendResultInfoAsJson( $obj )
	{
		header('Content-type: application/json');
		echo $obj;
		exit();
	}
	
	function returnWithError( $err )
	{
		$retValue = '{"error" :"' . $err . '"}';
		sendResultInfoAsJson( $retValue );
	}

	function check_token($token)
	{
		global $serverKey;
		require_once('jwt.php');

		// Decode token
		try { return JWT::decode($token, $serverKey, array('HS256'))->userId; }
		catch(Exception $e)
		{
			// Unauthorized
			http_response_code( 401 );
			returnWithError( "TOKEN ERROR: {$e->getMessage()}" );
		}
	}
	function IsNullOrEmptyString($str){
		return (!isset($str) || trim($str) === '');
	}
?>