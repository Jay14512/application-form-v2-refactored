<?php
require_once(dirname(__FILE__) ."/help/debug.php");
require_once(dirname(__FILE__) ."/help/validate.php");



//1. Formular ausgeben 
//2. Daten einlesen 
if( $_SERVER["REQUEST_METHOD"] === "POST" ){ //3. checken ob Formular versendet wurde

 $errors= validate($fields,$_POST, $_FILES);


  //4. Daten validieren 
  //PLZ/Ort/Bundesland-Prüfung aufrufen
  $plz=$_POST["plz"];
  $ort=$_POST["ort"];
  $bundesland=$_POST["bundesland"];


  if( !isset($errors['bundesland']) ){
      //PLZ/Ort/Bundesland Validierung hinzufügen
      $plzError = validate_plz_ort_bundesland($plz, $ort, $bundesland);

      if ($plzError !== true) {
        //Wenn die funktion eine Fehlermeldung zurückgibt, in das Fehlerarray einfügen
        /*::PETER:: Damit der Fehler ausgegeben wird, solltest du den Key bundesland und nicht plz_ort_bundesland verwenden. Oder du gibst im Formular untern 
          <?php if (!empty($errors['plz_ort_bundesland'])): ?>
            <div class="is-danger"><?= $errors['bundesland'] ?></div>
          <?php endif; ?>
        aus.
        //$errors["plz_ort_bundesland"] = $plzError;
        */
        $errors["bundesland"] = $plzError;
      }
  }

//5. Bei Fehler: Fehlermeldung ausgeben (Fehlermeldungen müssen unten im Formular ausgegeben werden)
//6. Bei Erfolg: Daten verarbeiten, CSV Datei speichern, evt. in Datenbank speichern, e-mail versenden etc.

if (empty($errors)) {
  $successMessage = save_application_and_resume($_POST, $_FILES);
}
}

// Funktion zur Speicherung der Bewerbung und des Lebenslaufs
function save_application_and_resume(array $formData, array $fileData) {
  $csvFilePath = 'Bewerbungsformular/bewerbung.csv';
  //::Peter:: Hier solltest du noch überprüfen ob der Ordner Bewerbungsformular existiert => falls nicht dann musst du den Ordner erzeugen
  if( !is_dir('Bewerbungsformular')){
    mkdir('Bewerbungsformular',0755,true);
  }
  // CSV-Datei öffnen oder erstellen
  $csvFile = fopen($csvFilePath, 'a');

  if (!$csvFile) {
    return 'Fehler beim Öffnen der CSV-Datei.';
}

// Daten für CSV speichern
$dataToWrite = [
  $formData['vn'],
  $formData['nn'],
  $formData['email'],
  $formData['plz'] ?? '',
  $formData['ort'] ?? '',
  $formData['bundesland'],
  $formData['dob'] ?? '',
  ''
];

// Ordner für Lebenslauf
$uploadDir = 'Bewerbungsformular/lebenslauf/';
if (!is_dir($uploadDir)) {
  //::PETER:: Hier solltest du die Berechtigungen 0755 verwenden, ansonsten ist der Ordner für Angreifer offen.
  mkdir($uploadDir, 0777, true);
}

if (!empty($fileData['resume']['name'])) {
  $resumeName = basename($fileData['resume']['name']);
  $resumePath = $uploadDir . $resumeName;
  $fileExtension = pathinfo($resumeName, PATHINFO_EXTENSION);
  $counter = 1;

  while (file_exists($resumePath)) {
      $resumeName = pathinfo($resumeName, PATHINFO_FILENAME) . '_' . $counter . '.' . $fileExtension;
      $resumePath = $uploadDir . $resumeName;
      $counter++;
  }

  if (move_uploaded_file($fileData['resume']['tmp_name'], $resumePath)) {
      $dataToWrite[7] = $resumePath; // Lebenslauf-Pfad in CSV speichern
  } else {
      return 'Fehler beim Speichern des Lebenslaufs.';
  }
}

// CSV schreiben
fputcsv($csvFile, $dataToWrite);
fclose($csvFile);

return 'Die Bewerbung wurde erfolgreich gespeichert.';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bewerbung</title>
    <link rel="stylesheet" href="/bulma/css/bulma.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
  <?php
  if (isset($success) && $success){
    echo "<div class='is-success'>".$success."</div>";
  }
    ?>
    <main>
        <form action="#" method="post"  enctype="multipart/form-data" novalidate>

        <div class="field">
  <label class="label">Vorname</label>
  <div class="control">
  <input type="text" name="vn" id="vorname" class="input <?php if( isset($errors['vn']) ){ echo 'is-danger'; } ?>" placeholder="Vorname..." required value="<?= $_POST['vn'] ?? ''; ?>">
        <?php
            // Fehlermeldung für Vorname ausgeben
            if( isset($errors["vn"]) ){
                echo "<div class='is-danger'>".$errors["vn"]."</div>";
            }
        ?>

  </div>
</div>
        <div class="field">
  <label class="label">Nachname</label>
  <div class="control">
  <input type="text" name="nn" id="nachname" class="input <?php if( isset($errors['nn']) ){ echo 'is-danger'; } ?>" placeholder="Nachname..." required value="<?= $_POST['nn'] ?? ''; ?>">
            <?php
                //Fehlermeldung für Nachname ausgeben
                if( isset($errors["nn"]) ){
                    echo "<div class='is-danger'>".$errors["nn"]."</div>";
                }
            ?>
  </div>
</div>

<div class="field">
  <label class="label">Email</label>
  <div class="control">
  <input type="email" name="email" id="email" class="input <?php if( isset($errors['email']) ){ echo 'is-danger'; } ?>" placeholder="alexsmith@gmail.com" required value="<?= $_POST['email'] ?? ''; ?>">
            <?php
                //Fehlermeldung für E-Mail ausgeben
                if( isset($errors["email"]) ){
                    echo "<div class='is-danger'>".$errors["email"]."</div>";
                }
            ?>
  </div>
</div>

<div class="field">
  <label class="label">PLZ</label>
  <div class="control">
  <input type="text" name="plz" id="plz" class="input <?php if( isset($errors['plz']) ){ echo 'is-danger'; } ?>" placeholder="1010" required value="<?= $_POST['plz'] ?? ''; ?>">
            <?php
                //Fehlermeldung für PLZ ausgeben
                if( isset($errors["plz"]) ){
                    echo "<div class='is-danger'>".$errors["plz"]."</div>";
                }
            ?>
  </div>
</div>

<div class="field">
  <label class="label">Ort</label>
  <div class="control">
  <input type="text" name="ort" id="ort" class="input <?php if( isset($errors['ort']) ){ echo 'is-danger'; } ?>" placeholder="Ort" required value="<?= $_POST['ort'] ?? ''; ?>">
            <?php
                //Fehlermeldung für Ort ausgeben
                if( isset($errors["ort"]) ){
                    echo "<div class='is-danger'>".$errors["ort"]."</div>";
                }
            ?>
  </div>
</div>


<div class="field">
  <label class="label">Bundesland</label>
    <p>     <span class="select">
    
      <select name="bundesland">
        <option selected value="">---Bundesland wählen---</option>
        <option value="Wien" <?= ($_POST['bundesland'] ?? '') === 'Wien' ? 'selected' : '' ?>>Wien</option>
        <option value="Niederösterreich" <?= ($_POST['bundesland'] ?? '') === 'Niederösterreich' ? 'selected' : '' ?>>Niederösterreich</option>
        <option value="Burgenland" <?= ($_POST['bundesland'] ?? '') === 'Burgenland' ? 'selected' : '' ?>>Burgenland</option>
        <option value="Oberösterreich" <?= ($_POST['bundesland'] ?? '') === 'Oberösterreich' ? 'selected' : '' ?>>Oberösterreich</option>
        <option value="Salzburg" <?= ($_POST['bundesland'] ?? '') === 'Salzburg' ? 'selected' : '' ?>>Salzburg</option>
        <option value="Steiermark" <?= ($_POST['bundesland'] ?? '') === 'Steiermark' ? 'selected' : '' ?>>Steiermark</option>
        <option value="Tirol" <?= ($_POST['bundesland'] ?? '') === 'Tirol' ? 'selected' : '' ?>>Tirol</option>
        <option value="Vorarlberg" <?= ($_POST['bundesland'] ?? '') === 'Vorarlberg' ? 'selected' : '' ?>>Vorarlberg</option>
        <option value="Kärnten" <?= ($_POST['bundesland'] ?? '') === 'Kärnten' ? 'selected' : '' ?>>Kärnten</option>
      </select>
      <?php if (!empty($errors['bundesland'])): ?>
        <div class="is-danger"><?= $errors['bundesland'] ?></div>
    <?php endif; ?>
    </span>
  </p>
</div>

<div class="field">
  <label class="label" >Geburtsdatum</label>
  <div class="control">
  <input type="date" name="dob" id="dob" class="input <?php if( isset($errors['dob']) ){ echo 'is-danger'; } ?>" placeholder="tt.mm.yyyy" required value="<?= $_POST['dob'] ?? ''; ?>">
            <?php
                //Fehlermeldung für Nachname ausgeben
                if( isset($errors["dob"]) ){
                    echo "<div class='is-danger'>".$errors["dob"]."</div>";
                }
            ?>
  </div>
</div>

<div class="file has-name">
  <label class="file-label">
  <input type="file" name="resume" id="resume" class="file-input <?php if( isset($errors['resume']) ){ echo 'is-danger'; } ?>" placeholder="Datei.pdf" required value="<?= $_POST['resume'] ?? ''; ?>">
            <?php
                //Fehlermeldung für Nachname ausgeben
                if (isset($errors["resume"])) {
                  echo "<div class='is-danger'>" . $errors["resume"] . "</div>";
              }
              
            ?>
    <span class="file-cta">
      <span class="file-icon">
        <i class="fas fa-upload"></i>
      </span>
      <span class="file-label"> Datei wählen… </span>
    </span>
    <span class="file-name"> Datei.pdf</span>
  </label>
</div>


<label class="checkbox">
<input class="checkbox <?php if( isset($errors["agb"]) ){echo 'is-danger';} ?>" type="checkbox" value="AGB gelesen" id="agb" name="agb" required <?php 
                    echo isset($_POST["agb"]) && !isset($errors["agb"]) ? 'checked' : ''; 
                    ?>>
        Ich akzeptiere die AGB
    </label>
    <br>
    <?php
                //Fehlermeldung für AGB ausgeben
                    if( isset($errors["agb"]) ){
                        echo "<div class='is-danger'>".$errors["agb"]."</div>";
                    }
                ?>
</label>
<br>
<button class="button">Senden</button>

        </form>
    </main>
    
</body>
</html>




                
                
