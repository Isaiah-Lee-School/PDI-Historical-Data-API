<?php

/**
 * Calls a python program through the command line that will retrieve stock data
 * @return string json string of the requested data
 */
function getFromYFinance($maxDateStored) {
    $currentDirectory = __DIR__;
    $scriptPath = $currentDirectory . "/PythonProject/PDI.py";
    $virtualEnvironmentPath = $currentDirectory . "/PythonProject/.venv/Scripts/python.exe";
    $command = escapeshellcmd("$virtualEnvironmentPath $scriptPath" . " '" . $maxDateStored . "'");
    $output = shell_exec($command);
    return $output;
}

/**
 * This method will perform a binary search on a target to find it within an array
 * @param $target is the value to be found
 * @param array $array is the array that will be searched for the target
 * @return bool returns true if the target was found, false otherwise
 */
function binarySearch($target, $array) {
    $found = false;
    $left = 0; 
    $right = count($array) - 1;
    while ($left <= $right) {
        $midPoint = floor(($left + $right) / 2);
        if($array[$midPoint] === $target) {
            $found = true;
            break;
        }
        if($array[$midPoint] < $target) {
            $left = $midPoint + 1;
        }
        else {
            $right = $midPoint - 1;
        }
    }

    return $found;
}

// --------------------- Main Program ------------------------------


require 'connect.php';

$sql = "SELECT quote_date FROM historical_data WHERE historical_quote_id = (SELECT max(historical_quote_id) FROM historical_data)";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetch();
$maxDate = "";

if($result) {
    $maxDate = $result['quote_date'];
}
else {
    $maxDate = "2012-06-27";
}

$pythonResult = getFromYFinance($maxDate);
$pythonOutputArray = json_decode($pythonResult, true);

if(count($pythonOutputArray) > 0) {
    for($i = 0; $i < count($pythonOutputArray); $i++) {
        try {
            $sql = "INSERT INTO historical_data (quote_date, open_price, high_price, low_price, close_price, volume) VALUES(?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            $addDate = filter_var($pythonOutputArray[$i]["('Date', '')"], FILTER_SANITIZE_SPECIAL_CHARS);
            $openPrice = (float)filter_var($pythonOutputArray[$i]["('Open', 'PDI')"], FILTER_VALIDATE_FLOAT);
            $highPrice = (float)filter_var($pythonOutputArray[$i]["('High', 'PDI')"], FILTER_VALIDATE_FLOAT);
            $lowPrice = (float)filter_var($pythonOutputArray[$i]["('Low', 'PDI')"], FILTER_VALIDATE_FLOAT);
            $closePrice = (float)filter_var($pythonOutputArray[$i]["('Close', 'PDI')"], FILTER_VALIDATE_FLOAT);
            $volume = (int)filter_var($pythonOutputArray[$i]["('Volume', 'PDI')"], FILTER_VALIDATE_INT);
            $stmt->execute([$addDate, $openPrice, $highPrice, $lowPrice, $closePrice, $volume]);
        }
        catch (PDOException $pdoException) {
            die($pdoException);
        }
    }
}

//--------------------------------------- API Code ---------------------------------------

$method = $_SERVER['REQUEST_METHOD'];
$prefix = "/trade_dave_app/pdi_data_updater";
$path = rtrim($_SERVER['REQUEST_URI']);
$pathParts = explode('/', $path);


//Get all quotes: http://localhost/trade_dave_app/pdi_data_updater/pdi_quotes
if(count($pathParts) === 4 && $pathParts[1] === "trade_app_dave" && $pathParts[2] === 'pdi_data_updater' && $pathParts[3] === 'pdi_quotes') {
    if($method === "GET") {
        $sql = "SELECT * FROM historical_data";
        $stmt = $pdo->query($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();

        header("Content-type: application/json");
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
}

//Get all quotes after specific point in time. 
//http://localhost/trade_app_dave/pdi_data_updater/pdi_quotes/range?startDate={startDate}&endDate={endDate}
if($method === "GET") {
    if(count($pathParts) === 5 && $pathParts[1] === "trade_app_dave" && $pathParts[2] === "pdi_data_updater" && $pathParts[3] === "pdi_quotes" && preg_match("/^range/", $pathParts[4])) {

        $startDate = filter_input(INPUT_GET, 'startDate', FILTER_SANITIZE_SPECIAL_CHARS);
        $endDate = filter_input(INPUT_GET, 'endDate', FILTER_SANITIZE_SPECIAL_CHARS);

        $pattern = "/\d{4}-\d{2}-\d{2}/";

        if(!(preg_match($pattern, $startDate)) || !(preg_match($pattern, $endDate))) {
            http_response_code(400);
            echo json_encode(["Error" => "One or both dates incorrect. Ensure Format: YYYY-MM-DD"]);
            exit();
        }

        $result = "";
        try{
            $sql = "SELECT * FROM historical_data WHERE quote_date >= ? AND quote_date <= ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            $result = $stmt->fetchAll();
        }
        catch (PDOException $pdoException) {
            http_response_code(400);
            echo json_encode(["Error" => "One or both dates incorrect. Ensure Format: YYYY-MM-DD"]);
            exit();
        }

        header("Content-type: application/json");
        echo json_encode($result, JSON_PRETTY_PRINT);


    }
}


?>