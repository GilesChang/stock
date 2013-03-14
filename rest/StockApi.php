<?php
	/*--
	This is a RESTful stock api.
	select and update all stock in DB: get /rest/
	select and update a stock: get /rest/symbol/
	insert a stock in DB: post /rest/
	delest a stock in DB: delete /rest/symbol/
	get the resource from google stock api, which is a RESTful xml api.
	It will return the result by json.
	--*/
	class StockAPI{
		
		private $db = NULL;/*init Database*/
		private $httpCode = "";/*http response code*/
	
		/* init Database connection*/
		public function __construct() {
			$this -> dbConnect() ;					
		}
	
		/*do Database connection*/
		private function dbConnect() {
			$this -> db = mysqli_connect("localhost", "root", "", "StockRestful") ;
			if (mysqli_connect_errno() )
			{
				echo "Failed to connect to MySQL: " . mysqli_connect_error() ;
			}
		}
		
		/*get the received http request method*/
		private function getMethod() {
			return $_SERVER['REQUEST_METHOD'];
		}
		
		/*get the stock symbol from requset URL*/
		private function getSymbol() {
			$request = explode("/", substr($_SERVER['REQUEST_URI'], 1) ) ;
			return $request[2];
		}
		
		/*return http request with header and json code*/
		private function json($data) {
			if(is_array($data) ) {
				$data = json_encode($data) ;
			}
			header("HTTP/1.1 " . $this -> httpCode) ;
			header("Content-Type:application/json") ;
			echo $data;
			exit;
		}
		
		/*execute this api. starts from request method selection.*/
		public function runApi() {
			switch ($this -> getMethod() ) {
				case 'POST':
					$this -> rest_post() ;
					break;
				case 'GET':
					$this -> rest_get($this -> getSymbol() ) ;  
					break;
				case 'DELETE':
					$this -> rest_delete($this -> getSymbol() ) ;  
					break;
				default:
					$this -> httpCode = "406 Not Acceptable";
					break;
			}
		}
		
		/*select and update stocks by GET*/
		private function rest_get($symbol) {
			$stock = array() ;
			if(empty($symbol) ) {	//if there is no symbol, select and update all stocks in DB
				$strSymbol = "";
				$putResult = mysqli_query($this -> db, "SELECT symbol FROM stocks") ;
				$intNumRows = mysqli_num_rows($putResult) ;
				if($intNumRows > 0) { //if there are some stocks in DB
					while ($row = mysqli_fetch_row($putResult) ) {
						$strSymbol .= "stock=" . $row[0] . "&";
					}
					$xml = simplexml_load_file("http://www.google.com/ig/api?" . $strSymbol) ;
					for($i = 0; $i < $intNumRows; $i++) {
						$stock[$i]["symbol"] = $xml -> finance[$i] -> symbol["data"];
						$stock[$i]["company"] = $xml -> finance[$i] -> company["data"];
						$stock[$i]["price"] = $xml -> finance[$i] -> last["data"];
						$stock[$i]["high"] = $xml -> finance[$i] -> high["data"];
						$stock[$i]["low"] = $xml -> finance[$i] -> low["data"];
						$stock[$i]["change"] = $xml -> finance[$i] -> change["data"];
						$stock[$i]["volume"] = $xml -> finance[$i] -> volume["data"];
						mysqli_query($this -> db, "UPDATE stocks SET price = '" . $stock[$i]["price"] . "', high = '" . $stock[$i]["high"] . "', low = '" . $stock[$i]["low"] . "', changes = '" . $stock[$i]["change"] . "', volume = '" . $stock[$i]["volume"] . "'  WHERE symbol = '" . $stock[$i]["symbol"] . "'") ;
					}
					$this -> httpCode = "200 OK";
				}
				else{	//if no stock in DB
					$this -> httpCode = "204 No Content";
				}
			}
			else{	//if there is a specific stock symbol, select and update the stock.
				$xml = simplexml_load_file("http://www.google.com/ig/api?stock=" . $symbol) ;
				if(!empty($xml -> finance -> company["data"]) ) {	//if stock symbol is occor.
					$stock["symbol"] = $xml -> finance -> symbol["data"];
					$stock["company"] = $xml -> finance -> company["data"];
					$stock["price"] = $xml -> finance -> last["data"];
					$stock["high"] = $xml -> finance -> high["data"];
					$stock["low"] = $xml -> finance -> low["data"];
					$stock["change"] = $xml -> finance -> change["data"];
					$stock["volume"] = $xml -> finance -> volume["data"];
					$getResult = mysqli_query($this -> db, "SELECT * FROM stocks Where symbol ='" . $symbol . "'") ;
					if(mysqli_num_rows($getResult) > 0) { //if stock is in DB then update.
						mysqli_query($this -> db, "UPDATE stocks SET price = '" . $stock["price"] . "', high = '".$stock["high"] . "', low = '" . $stock["low"] . "', changes = '" . $stock["change"] . "', volume = '" . $stock["volume"] . "' WHERE symbol = '" . $stock["symbol"] . "'") ;
						$stock["addSub"] = false;
					}
					else{	//if stock is not in DB
						$stock["addSub"] = true;
					}
					$this -> httpCode = "200 OK";
				}
				else{	//if no such stock symbol;
					$this -> httpCode = "204 No Content";
				}
			}
			$this -> json($stock) ;
		}
		
		/*insert data in db by POST*/
		private function rest_post() {
			$stock = array() ;
			$xml = simplexml_load_file("http://www.google.com/ig/api?stock=" . $_POST["symbol"]) ;
			if(!empty($xml -> finance -> company["data"]) ) {	//if stock symbol is occor.
				$stock["symbol"] = $xml -> finance -> symbol["data"];
				$stock["company"] = $xml -> finance -> company["data"];
				$stock["price"] = $xml -> finance -> last["data"];
				$stock["high"] = $xml -> finance -> high["data"];
				$stock["low"] = $xml -> finance -> low["data"];
				$stock["change"] = $xml -> finance -> change["data"];
				$stock["volume"] = $xml -> finance -> volume["data"];
				mysqli_query($this -> db,"INSERT INTO stocks (symbol, company, price, high, low, changes, volume) VALUES ('" . $stock["symbol"] . "', '" . $stock["company"]."','".$stock["price"] . "', '" . $stock["high"] . "', '" . $stock["low"] . "', '" . $stock["change"] . "', '" . $stock["volume"] . "') ") ;
				$checkInsert = mysqli_query($this -> db, "SELECT * FROM stocks Where symbol = '". $stock["symbol"] . "'") ;
				if(mysqli_num_rows($checkInsert) > 0) {	//check if insert successful.
					$this -> rest_get(NULL) ;
				}
				else{
					$this -> httpCode = "500 Internal Server Error";
				}
			}
			else{	//if no such stock symbol;
				$this -> httpCode = "204 No Content";
			}
			$this -> json($stock) ;
		}
		
		/*delete data in db by DELETE*/
		private function rest_delete($symbol) {
			$stock = array() ;
			mysqli_query($this -> db,"DELETE FROM stocks Where symbol = '" . $symbol . "'") ;
			$checkDelete = mysqli_query($this -> db, "SELECT * FROM stocks Where symbol ='" . $symbol . "'") ;
			if(mysqli_num_rows($checkDelete) == 0) {	//check if insert successful.
				$this -> rest_get(NULL) ;
			}
			else{
				$this -> httpCode = "500 Internal Server Error";
			}
			$this -> json($stock) ;
		}
	
	}
	
	$api = new StockAPI;
	$api -> runApi() ;
	
?>