<?php
	header("Content-type:text/plain");
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

	$server = "localhost";
	$username = "mabnmy2";
	$pass = "950519055163";
	$db_name = "db_mabnmy2";

	$con = new mysqli($server, $username, $pass, $db_name);
	if($con->connect_error)
	{
		die("Connection failed: ". $con->connect_error);
	}

	$data = file_get_contents('php://input');
	$obj = json_decode($data, true);
	if(array_key_exists('action', $obj[2]) && $obj[2]['action'] == 'submit')
	{
		SaveOrder($obj, $con);
	}
	else if($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_REQUEST["id"]))
	{
		ListOrder($con);
	}
	else if(isset($_REQUEST["id"]))
	{
		$id = $_REQUEST["id"];
		DeleteOrder($con, $id);
	}

	class Customer
	{
		public $id_customer;
		public $name;
		public $address;
		public $area;

		function __construct(array $data)
    {
    	$this->id_customer = $data['id_customer'];
			$this->name = $data['name'];
			$this->address = $data['address'];
			$this->area = $data['area'];
		}
	}
	class Order
	{
		public $item;
		public $price;
		public $qty;

		function __construct(array $data)
		{
			$this->item = $data['item'];
			$this->price = $data['price'];
			$this->qty = $data['qty'];
		}
	}

	function SaveOrder($obj, $con)
	{
		$cust = new Customer($obj[0]);

		$sql_check = "SELECT id_customer FROM `customer` WHERE name = '".$cust->{name}."'";
    $rslt_check = mysqli_query($con, $sql_check);
    $rows_check = mysqli_num_rows($rslt_check);

    if($rows_check > 0)
    {
    	while($row = mysqli_fetch_array($rslt_check, MYSQLI_ASSOC))
    	{
    		$cust->id_customer = $row['id_customer'];
    	}
    }
    else
    {
    	$stmt = $con->prepare("INSERT INTO `customer` (name, address, area) VALUES (?, ?, ?)");
			$stmt->bind_param("sss", $name, $address, $area);

			$name = $cust->{name};
			$address = $cust->{address};
			$area = $cust->{area};
			$stmt->execute();
			$cust->id_customer = $con->insert_id;
    }

		foreach($obj[1] as $orders)
		{
			$order = new Order($orders);
			$stmt = $con->prepare("INSERT INTO `order` (id_customer, item, price, qty) VALUES (?, ?, ?, ?)");
			$stmt->bind_param("isdi", $id_customer, $item, $price, $qty);

			$id_customer = $cust->{id_customer};
			$item = $order->{item};
			$price = $order->{price};
			$qty = $order->{qty};
			$stmt->execute();
		}
	}
	function ListOrder($con)
	{
		$cust_arr = array();
		$order_arr = array();
		$details = array();

		$sql_select = "SELECT * FROM customer";
		$res = mysqli_query($con, $sql_select);
		$rows = mysqli_num_rows($res);

		if($rows > 0){
			while($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
			{
				$cust = new Customer($row);

				$sql_odr = "SELECT * FROM `order` WHERE id_customer = '".$cust->{id_customer}."'";
				$res_odr = mysqli_query($con, $sql_odr);
	      $rows_odr = mysqli_num_rows($res_odr);
	      if($rows_odr > 0){
					while($row_odr = mysqli_fetch_array($res_odr, MYSQLI_ASSOC))
					{
						$order = new Order($row_odr);
						array_push($order_arr, $order);
					}
				}

				$temp_details = new stdClass();
				$temp_details->cust = $cust;
				$temp_details->order = $order_arr;
				array_push($details, $temp_details);
				$cust_arr = array();
				$order_arr = array();
			}
		}
  	 	echo json_encode($details);
	}
	function DeleteOrder($con, $id)
	{
		$delete_order = "DELETE FROM `order` WHERE id_customer = '$id'";
		$delete_cust = "DELETE FROM `customer` WHERE id_customer = '$id'";
		$con->query($delete_order);
		if($con->query($delete_cust) === TRUE)
			echo "Successfully Delete";
		else
			echo "Error: ".$con->error;
	}
?>