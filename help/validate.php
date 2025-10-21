<?php


$fields = [
"vn"=>[
    "rules"=> "required|min:2",
    "message"=> "Vorname muss mind. 2 Zeichen lang sein.",
],
"nn"=>[
    "rules"=> "required|min:2",
    "message"=> "Nachname muss mind. 2 Zeichen lang sein.",
],
"email"=>[
    "rules"=> "required|email",
    "message"=> "Die angegebene E-Mail Adresse ist ungültig.",
],
"plz"=>[
    "rules"=> "required",
    "message"=> "Pflichtfeld",
],
"ort"=>[
    "rules"=> "required",
    "message"=> "Pflichtfeld",
],
"bundesland"=>[
    "rules"=>"required",
    "message"=> "Pflichtfeld",
],
"dob"=>[
    "rules"=> "required|check_age:18",
    "message"=>"Das Mindestalter beträgt 18 Jahre.",
],
"resume"=>[
    "rules"=> "required|is_pdf",
    "message"=>[
        "required"=> "Es muss mind. 1 Datei ausgewählt werden.",
        "is_pdf"=>"Es dürfen nur PDF-Dateien hochgeladen werden." ,
    ],
],
"agb"=>[
    "rules"=> "required",
    "message"=> "AGB müssen ausgewählt sein.",
],
    
];



function validate(array $rules, array &$data, array $files, bool $sanatize=true)
{
    if ($sanatize) {
        $keys = array_keys($rules);
        sanatize($keys, $data);
    }
    
    $errors = [];

    foreach ($rules as $key => $item) {
        if (!isset($item["rules"]) || !isset($item["message"])) {
            $errors[$key] = "Fehler im Feld: ".$key;
        } else {
            $validateRules = explode("|", $item["rules"]);
            foreach ($validateRules as $rule) {
                $ruleParams = explode(":", $rule);
                $fkt = $ruleParams[0];
                $param = $ruleParams[1] ?? null;
        
                if (empty($fkt)) {
                    dd("Fehler: Leere Regel gefunden bei Feld: ", $key);
                }                


                if (!function_exists($fkt)) {
                    dd("Funktion existiert nicht", $fkt);
                }

                // Falls eine Datei geprüft wird, übergebe $files
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


 function sanatize(array $keys, array $data):void
 {
 foreach ($keys as $index){
    $data[$index] = trim(htmlspecialchars($data[$index]?? ""));
 }
}

function required(string $key, array $data):bool
{
    if (isset($data[$key]) && ($data[$key]== 0 || !empty($data[$key]))){
        return true;
    }
    return false;
}

function strmin(string $key, array $data, $len):bool
{
    if (mb_strlen($data[$key]) >= (int) $len){
        return true;
    }
    return false; 
}

function strmax(string $key, array $data, $maxLen):bool
{
    if (mb_strlen($data[$key]) <= (int) $maxLen){
        return true;
    }
    return false;
}

function email(string $key, array $data):bool
{
if(filter_var($data[$key], FILTER_SANITIZE_EMAIL)){
    return true; 
    }
    return false; 
}

function validate_plz_ort_bundesland($plz, $ort, $bundesland) {
    // Pfad zur CSV-Datei
    $csvFile = 'plz_ort_bundesland.csv';
    
    // Überprüfen, ob die Datei existiert
    if (!file_exists($csvFile)) {
        return 'CSV-Datei nicht gefunden.';
    }

    // CSV-Datei einlesen und in ein Array laden
    $csvData = array_map('str_getcsv', file($csvFile));

    // Durch jede Zeile der CSV-Datei iterieren
    foreach ($csvData as $line) {
        // CSV-Daten in Variablen und Trimmen von Leerzeichen
        $csvPlz = trim(strtolower($line[0]));  // Umwandlung in Kleinbuchstaben und Entfernen von Leerzeichen
        $csvOrt = trim(strtolower($line[1]));  // Umwandlung in Kleinbuchstaben und Entfernen von Leerzeichen
        $csvBundesland = trim(strtolower($line[2]));  // Umwandlung in Kleinbuchstaben und Entfernen von Leerzeichen

        // Eingabewerte ebenfalls trimmen und in Kleinbuchstaben umwandeln
        $plz = trim(strtolower($plz));
        $ort = trim(strtolower($ort));
        $bundesland = trim(strtolower($bundesland));

        // Überprüfen, ob PLZ, Ort und Bundesland übereinstimmen
        if ($plz == $csvPlz && $ort == $csvOrt && $bundesland == $csvBundesland) {
            return true; // Übereinstimmung gefunden
        }
    }

    // Wenn keine Übereinstimmung gefunden wurde
    return 'Die Kombination aus PLZ, Ort und Bundesland ist ungültig.';
}




function is_pdf(string $key, array $data, array $files): bool
{

    // Überprüfen, ob eine Datei hochgeladen wurde
    //::Peter:: Überprüfung !== und nicht === verwenden. Nur dann ist beim Upload ein Fehler aufgetreten.
    //if (!isset($files[$key]) || $files[$key]["error"] === UPLOAD_ERR_OK) {
    if (!isset($files[$key]) || $files[$key]["error"] !== UPLOAD_ERR_OK) {
        return false;  // Wenn keine Datei ausgewählt wurde, gib false zurück
    }

    // Überprüfen, ob die Datei vom Typ PDF ist
    $fileType = mime_content_type($files[$key]["tmp_name"]);
    if ($fileType !== "application/pdf") {
        return false;  // Wenn die Datei keine PDF ist, gib false zurück
    }

    return true; // Wenn beides zutrifft (Datei vorhanden und PDF), gib true zurück
}

function check_age(string $key, array $data, $minAge): bool
{
    if (!isset($data[$key]) || empty($data[$key])) {
        return false;
    }

    // Versuche, das Datum im Format "d-m-Y" in einen Unix-Timestamp umzuwandeln
    $dobArray = explode("-", $data[$key]);

    // Überprüfen, ob das Datum in der richtigen Reihenfolge (Tag, Monat, Jahr) vorliegt
    if (count($dobArray) !== 3) {
        return false;  // Ungültiges Format
    }

    list($day, $month, $year) = $dobArray;

    // Erstellen eines Datums-Timestamps
    $dobTimestamp = strtotime("$year-$month-$day");

    // Wenn das Datum ungültig ist, gibt strtotime() false zurück
    if (!$dobTimestamp) {
        return false;
    }

    // Berechnen des aktuellen Alters
    $age = (time() - $dobTimestamp) / (60 * 60 * 24 * 365.25); // In Jahren

    return $age >= $minAge;
}

