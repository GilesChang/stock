<?php
	/*This class makes a cURL request to RESTful api and get json result. */
	class cURL{
	
		private $curlOption = Array() ;	/*make curl_setopt array*/
		private $URL = "http://localhost/stock/rest/";	/*request URL default*/
		private $curlMethod = "UPDATE";	/*request method default*/
		private $stockSymbol = "";	/*stock symbol default*/
		
		/*this function adds stock symbol to request URL. 
		called when the request method is UPDATE and DELETE
		(INSERT use POST method such that don't need to call this function)*/
		private function addSymbolURL() {
			$this -> URL .= $this ->stockSymbol;
		}
		
		/*called in curl_setopt[CURLOPT_POSTFIELD] to set post content*/
		private function arrayPost() {
			return array("symbol" => $this ->stockSymbol) ;
		}
		
		/*make array for curl_setopt_array. Define request URL, method, content, get response header and get response content*/
		private function makeOptionArray() {
			$this -> curlOption[CURLOPT_URL] = $this -> URL;
			if($this -> curlMethod == "INSERT") {
				$this -> curlOption[CURLOPT_POST] = true;
				$this -> curlOption[CURLOPT_POSTFIELDS] = $this -> arrayPost() ;
			}
			elseif($this -> curlMethod == "DELETE") {
				$this -> curlOption[CURLOPT_CUSTOMREQUEST] = "DELETE";
			}
			$this -> curlOption[CURLOPT_HEADER] = true;
			$this -> curlOption[CURLOPT_RETURNTRANSFER] = true;
		}
		
		/*set request method*/
		public function setMethod($method) {
			$this -> curlMethod = $method;
		}
		
		/*set stock symbol*/
		public function setSymbol($symbol) {
			$this -> stockSymbol = $symbol;
		}
		
		/*execute this curl app. get http response including header and body.
		decode json response*/
		public function curlExecute() {
			if(!empty($this -> stockSymbol) && $this ->curlMethod!="INSERT")
				$this -> addSymbolURL() ;
			$this -> makeOptionArray() ;
			$ch = curl_init() ;
			curl_setopt_array($ch, $this -> curlOption) ;
			$response = curl_exec($ch) ; 
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ;
			$body = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE) ) ;
			curl_close($ch) ;
			if($httpCode == 200) {
				return json_decode($body, true) ;
			}
			else{
				return Array("notOK" => True) ;
			}
		}
	}
	
	$stock_info = new cURL;
	
	/*get method and stock symbol to determine RESTful request. */
	if(isset($_POST["method"]) && isset($_POST["stock"]) && $_POST["method"] == "INSERT") {
		$stock_info -> setMethod("INSERT") ;
		$stock_info -> setSymbol($_POST["stock"]) ;
	}
	elseif(isset($_POST["method"]) && isset($_POST["stock"]) && $_POST["method"] == "DELETE") {
		$stock_info -> setMethod("DELETE") ;
		$stock_info -> setSymbol($_POST["stock"]) ;
	}
	if(isset($_GET["stock"]) ) {
		$stock_info -> setSymbol($_GET["stock"]) ;
	}
	$result = $stock_info -> curlExecute() ;
?>

<!--http code starts here-->
<!DOCTYPE html>
<html>
<head>
<style type="text/css">
table {
	border-collapse: collapse;
}
th {
	border-style: solid; 
	border-width: 1px;
}
td{
	border-style: solid; 
	border-width: 1px;
	text-align: center;
}
</style>
</head>
<body>
<p>Please enter Stock Symbol to search and subscribe.(Example: AAPL)</p>
<form name = "input" action = "http://localhost/stock/StockApp.php" method = "get">
	<p>Stock Symbol Search:<input type = "text" name = "stock">
	<input type = "submit" value = "Search"></p>
</form><br>
<?php
	/*show specific stock detail*/
	if(!isset($_GET["method"]) && isset($_GET["stock"]) ) {
		if(!isset($result["notOK"]) ) {
			echo "<table><tr>";
			echo "<th style = \"width: 80px;\">Symbol</th>";
			echo "<th style = \"width: 250px;\">Company</th>";
			echo "<th style = \"width: 80px;\">Price</th>";
			echo "<th style = \"width: 80px;\">High</th>";
			echo "<th style = \"width: 80px;\">Low</th>";
			echo "<th style = \"width: 80px;\">Change</th>";
			echo "<th style = \"width: 100px;\">volume</th>";
			echo "<th style = \"width: 100px;\">Subscribe</th>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>" . $result["symbol"][0] . "</td>";
			echo "<td>" . $result["company"][0] . "</td>";
			echo "<td>" . $result["price"][0] . "</td>";
			echo "<td>" . $result["high"][0] . "</td>";
			echo "<td>" . $result["low"][0] . "</td>";
			if($result["change"][0][0] == "+") {
				echo "<td><div style = \"color: green;\">" . $result["change"][0] . "</td>";
			}
			elseif($result["change"][0][0] == "-") {
				echo "<td><div style = \"color: red;\">" . $result["change"][0] . "</td>";
			}
			else{
				echo "<td>" . $result["change"][0] . "</td>";
			}
			echo "<td>" . number_format($result["volume"][0]) . "</td>";
			echo "<td>";
			echo "<form name = \"input\" action = \"http://localhost/stock/StockApp.php\" method = \"post\">";
			if($result["addSub"]) {
				echo "<input type = \"hidden\" value = \"INSERT\" name = \"method\">";
				echo "<input type = \"hidden\" value = \"" . $result["symbol"][0] . "\" name= \"stock\">";
				echo "<input type = \"submit\" value = \"Subscribe\">";
			}
			else{
				echo "<input type = \"hidden\" value = \"DELETE\" name = \"method\">";
				echo "<input type = \"hidden\" value = \"" . $result["symbol"][0] . "\" name = \"stock\">";
				echo "<input type = \"submit\" value = \"Unsubscribe\">";
			}
			echo "</form>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}
		echo "<br><a href = \"http://localhost/stock/StockApp.php\">Return to the user list</a>";
	}
	/*show all subscribe stock*/
	elseif(!isset($result["notOK"]) ) {
		echo "<table><tbody>";
		echo "<tr>";
		echo "<th style = \"width: 80px;\">Symbol</th>";
		echo "<th style = \"width: 250px;\">Company</th>";
		echo "<th style = \"width: 80px;\">Price</th>";
		echo "<th style = \"width: 80px;\">Change</th>";
		echo "<th style = \"width: 100px;\">Subscribe</th>";
		echo "</tr>";
		foreach($result as $value) {
			echo "<tr>";
			echo "<td><a href = \"http://localhost/stock/StockApp.php?stock=" . $value["symbol"][0] . "\">" . $value["symbol"][0] . "</a></td>";
			echo "<td>" . $value["company"][0] . "</td>";
			echo "<td>" . $value["price"][0] . "</td>";
			if($value["change"][0][0]=="+") {
				echo "<td><div style = \"color: green;\">" . $value["change"][0] . "</div></td>";
			}
			elseif($value["change"][0][0]=="-") {
				echo "<td><div style = \"color: red;\">" . $value["change"][0] . "</div></td>";
			}
			else{
				echo "<td>" . $value["change"][0] . "</td>";
			}
			echo "<td>";
			echo "<form name = \"input\" action = \"http://localhost/stock/StockApp.php\" method = \"post\">";
			echo "<input type = \"hidden\" value = \"DELETE\" name = \"method\">";
			echo "<input type = \"hidden\" value = \"" . $value["symbol"][0] . "\" name = \"stock\">";
			echo "<input type = \"submit\" value = \"Unsubscribe\">";
			echo "</form>";
			echo "</td>";
			echo "</tr>";			
		}
		echo "</tbody></table>";
	}
?>
</body>
</html>