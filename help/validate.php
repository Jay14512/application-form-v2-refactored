<?php


$fields = [
    "vn" => [
        "rules" => "required|min:2",
        "message" => "First Name must contain at least 2 characters.",
    ],
    "nn" => [
        "rules" => "required|min:2",
        "message" => "Last Name must at least contain 2 characters.",
    ],
    "email" => [
        "rules" => "required|email",
        "message" => "Invalid E-Mail",
    ],
    "plz" => [
        "rules" => "required",
        "message" => "Cannot be empty.",
    ],
    "ort" => [
        "rules" => "required",
        "message" => "Cannot be empty.",
    ],
    "bundesland" => [
        "rules" => "required",
        "message" => "Cannot be empty.",
    ],
    "dob" => [
        "rules" => "required|check_age:18",
        "message" => "The minimum age is 18 years.",
    ],
    "resume" => [
        "rules" => "required|is_pdf",
        "message" => [
            "required" => "At least 1 file has to be uploaded.",
            "is_pdf" => "Only PDF files allowed.",
        ],
    ],
    "agb" => [
        "rules" => "required",
        "message" => "Terms and conditions must be checked.",
    ],

];



function validate(array $rules, array &$data, array $files, bool $sanatize = true)
{
    if ($sanatize) {
        $keys = array_keys($rules);
        sanatize($keys, $data);
    }

    $errors = [];

    foreach ($rules as $key => $item) {
        if (!isset($item["rules"]) || !isset($item["message"])) {
            $errors[$key] = "Error in field: " . $key;
        } else {
            $validateRules = explode("|", $item["rules"]);
            foreach ($validateRules as $rule) {
                $ruleParams = explode(":", $rule);
                $fkt = $ruleParams[0];
                $param = $ruleParams[1] ?? null;

                if (empty($fkt)) {
                    dd("Error: Found empty rule: ", $key);
                }


                if (!function_exists($fkt)) {
                    dd("Function doesn't exist", $fkt);
                }

                //If a file is being checked, pass $files
                if ($key === "resume") {

                    if (!is_pdf($key, $data, $files)) {
                        $errors[$key] = is_array($item["message"]) ? $item["message"]["is_pdf"] : $item["message"];
                        break;
                    }
                } else {
                    if (!$fkt($key, $data, $param)) {
                        $errors[$key] = $item["message"];
                        break;
                    }
                }
            }
        }
    }

    return $errors;
}


function sanatize(array $keys, array $data): void
{
    foreach ($keys as $index) {
        $data[$index] = trim(htmlspecialchars($data[$index] ?? ""));
    }
}

function required(string $key, array $data): bool
{
    if (isset($data[$key]) && ($data[$key] == 0 || !empty($data[$key]))) {
        return true;
    }
    return false;
}

function strmin(string $key, array $data, $len): bool
{
    if (mb_strlen($data[$key]) >= (int) $len) {
        return true;
    }
    return false;
}

function strmax(string $key, array $data, $maxLen): bool
{
    if (mb_strlen($data[$key]) <= (int) $maxLen) {
        return true;
    }
    return false;
}

function email(string $key, array $data): bool
{
    if (filter_var($data[$key], FILTER_SANITIZE_EMAIL)) {
        return true;
    }
    return false;
}

function validate_plz_ort_bundesland($plz, $ort, $bundesland)
{
    // Path to CSV file
    $csvFile = 'plz_ort_bundesland.csv';

    // Check if file exists
    if (!file_exists($csvFile)) {
        return 'CSV File not found ';
    }

    // Read CSV file and put into array
    $csvData = array_map('str_getcsv', file($csvFile));

    // Iterate over each line in CSV file
    foreach ($csvData as $line) {
        // Extract CSV values and trim spaces/lowercase 
        $csvPlz = trim(strtolower($line[0]));
        $csvOrt = trim(strtolower($line[1]));
        $csvBundesland = trim(strtolower($line[2]));

        // Trim spaces and lowercase
        $plz = trim(strtolower($plz));
        $ort = trim(strtolower($ort));
        $bundesland = trim(strtolower($bundesland));

        // Check if CAP, County and City match
        if ($plz == $csvPlz && $ort == $csvOrt && $bundesland == $csvBundesland) {
            return true; // Match found
        }
    }

    // If no match was found
    return 'The combination of CAP, County and city does not match.';
}




function is_pdf(string $key, array $data, array $files): bool
{

    // Check if a file was uploaded
    if (!isset($files[$key]) || $files[$key]["error"] !== UPLOAD_ERR_OK) {
        return false;  // If no file was found return false
    }

    // Check if file is a PDF
    $fileType = mime_content_type($files[$key]["tmp_name"]);
    if ($fileType !== "application/pdf") {
        return false;  // Wenn die Datei keine PDF ist, gib false zurück
    }

    return true; // If both is true (file found and extension is pdf) return true
}

function check_age(string $key, array $data, $minAge): bool
{
    if (!isset($data[$key]) || empty($data[$key])) {
        return false;
    }

    // Convert Date "d-m-Y" into Unix-Timestamp format
    $dobArray = explode("-", $data[$key]);

    // Check if Date is in right order (Day, Month, Year) 
    if (count($dobArray) !== 3) {
        return false;  // invalid format
    }

    list($day, $month, $year) = $dobArray;

    // Create Date Timestamps
    $dobTimestamp = strtotime("$year-$month-$day");

    // If date is invalid strtotime() returns false
    if (!$dobTimestamp) {
        return false;
    }

    // Calculate current age
    $age = (time() - $dobTimestamp) / (60 * 60 * 24 * 365.25); // in years

    return $age >= $minAge;
}

