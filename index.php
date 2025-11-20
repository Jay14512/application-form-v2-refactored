<?php
require_once(dirname(__FILE__) . "/help/debug.php");
require_once(dirname(__FILE__) . "/help/validate.php");



//1. Display Form
//2. Read Data 
if ($_SERVER["REQUEST_METHOD"] === "POST") { //3. check if form has been sent

  $errors = validate($fields, $_POST, $_FILES);


  //4. Validate Data
  //check CAP/County/City
  $plz = $_POST["plz"];
  $ort = $_POST["ort"];
  $bundesland = $_POST["bundesland"];


  if (!isset($errors['bundesland'])) {
    //add Validation for CAP/County/City
    $plzError = validate_plz_ort_bundesland($plz, $ort, $bundesland);

    if ($plzError !== true) {
      // If the function returns an error message, add it to the error array.
/*
   To display the error correctly,  the key 'bundesland' is used
   instead of 'plz_ort_bundesland'.  
*/
      $errors["bundesland"] = $plzError;
    }
  }

  //5. If error: show error message (error messagaes always need to be  shown under the form)
//6. If success: Data processing, save CSV File, save to Database, send e-mail etc.

  if (empty($errors)) {
    $successMessage = save_application_and_resume($_POST, $_FILES);
  }
}

// Function for saving application and CV
function save_application_and_resume(array $formData, array $fileData)
{
  $csvFilePath = 'Bewerbungsformular/bewerbung.csv';
  // verify if folder Application form exists => if not, generate folder
  if (!is_dir('Bewerbungsformular')) {
    mkdir('Bewerbungsformular', 0755, true);
  }
  // open or generate CSV File 
  $csvFile = fopen($csvFilePath, 'a');

  if (!$csvFile) {
    return 'Fehler beim Öffnen der CSV-Datei.';
  }

  // Save Data for CSV
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

  // Folder for CV 
  $uploadDir = 'Bewerbungsformular/lebenslauf/';
  if (!is_dir($uploadDir)) {
    //use permissions 0755, else folder is open for attackers 
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
      $dataToWrite[7] = $resumePath; // save CV path in CSV 
    } else {
      return 'Fehler beim Speichern des Lebenslaufs.';
    }
  }

  // write CSV 
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
  <title>Application</title>
  <link rel="stylesheet" href="/bulma/css/bulma.min.css">
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <?php
  if (isset($success) && $success) {
    echo "<div class='is-success'>" . $success . "</div>";
  }
  ?>
  <main>
    <h1>Application Form</h1>
    <form action="#" method="post" enctype="multipart/form-data" novalidate>

      <div class="field">
        <label class="label">First Name</label>
        <div class="control">
          <input type="text" name="vn" id="vorname" class="input <?php if (isset($errors['vn'])) {
            echo 'is-danger';
          } ?>" placeholder="First Name..." required value="<?= $_POST['vn'] ?? ''; ?>">
          <?php
          // show error message for first name 
          if (isset($errors["vn"])) {
            echo "<div class='is-danger'>" . $errors["vn"] . "</div>";
          }
          ?>

        </div>
      </div>
      <div class="field">
        <label class="label">Last Name</label>
        <div class="control">
          <input type="text" name="nn" id="nachname" class="input <?php if (isset($errors['nn'])) {
            echo 'is-danger';
          } ?>" placeholder="Last Name..." required value="<?= $_POST['nn'] ?? ''; ?>">
          <?php
          //show error message for last name 
          if (isset($errors["nn"])) {
            echo "<div class='is-danger'>" . $errors["nn"] . "</div>";
          }
          ?>
        </div>
      </div>

      <div class="field">
        <label class="label">E-Mail</label>
        <div class="control">
          <input type="email" name="email" id="email" class="input <?php if (isset($errors['email'])) {
            echo 'is-danger';
          } ?>" placeholder="alexsmith@gmail.com" required value="<?= $_POST['email'] ?? ''; ?>">
          <?php
          //show error message for e-mail
          if (isset($errors["email"])) {
            echo "<div class='is-danger'>" . $errors["email"] . "</div>";
          }
          ?>
        </div>
      </div>

      <div class="field">
        <label class="label">CAP</label>
        <div class="control">
          <input type="text" name="plz" id="plz" class="input <?php if (isset($errors['plz'])) {
            echo 'is-danger';
          } ?>" placeholder="1010" required value="<?= $_POST['plz'] ?? ''; ?>">
          <?php
          //show error message for CAP 
          if (isset($errors["plz"])) {
            echo "<div class='is-danger'>" . $errors["plz"] . "</div>";
          }
          ?>
        </div>
      </div>

      <div class="field">
        <label class="label">County</label>
        <div class="control">
          <input type="text" name="ort" id="ort" class="input <?php if (isset($errors['ort'])) {
            echo 'is-danger';
          } ?>" placeholder="e.g. Vienna" required value="<?= $_POST['ort'] ?? ''; ?>">
          <?php
          //show error message for city
          if (isset($errors["ort"])) {
            echo "<div class='is-danger'>" . $errors["ort"] . "</div>";
          }
          ?>
        </div>
      </div>


      <div class="field">
        <label class="label">City</label>
        <p> <span class="select">

            <select name="bundesland">
              <option selected value="">---Select City---</option>
              <option value="Wien" <?= ($_POST['bundesland'] ?? '') === 'Wien' ? 'selected' : '' ?>>Vienna</option>
              <option value="Niederösterreich" <?= ($_POST['bundesland'] ?? '') === 'Niederösterreich' ? 'selected' : '' ?>>Lower Austria</option>
              <option value="Burgenland" <?= ($_POST['bundesland'] ?? '') === 'Burgenland' ? 'selected' : '' ?>>Burgenland
              </option>
              <option value="Oberösterreich" <?= ($_POST['bundesland'] ?? '') === 'Oberösterreich' ? 'selected' : '' ?>>
                Upper Austria</option>
              <option value="Salzburg" <?= ($_POST['bundesland'] ?? '') === 'Salzburg' ? 'selected' : '' ?>>Salzburg
              </option>
              <option value="Steiermark" <?= ($_POST['bundesland'] ?? '') === 'Steiermark' ? 'selected' : '' ?>>Styria
              </option>
              <option value="Tirol" <?= ($_POST['bundesland'] ?? '') === 'Tirol' ? 'selected' : '' ?>>Tyrol</option>
              <option value="Vorarlberg" <?= ($_POST['bundesland'] ?? '') === 'Vorarlberg' ? 'selected' : '' ?>>Vorarlberg
              </option>
              <option value="Kärnten" <?= ($_POST['bundesland'] ?? '') === 'Kärnten' ? 'selected' : '' ?>>Carinthia
              </option>
            </select>
            <?php if (!empty($errors['bundesland'])): ?>
              <div class="is-danger"><?= $errors['bundesland'] ?></div>
            <?php endif; ?>
          </span>
        </p>
      </div>

      <div class="field">
        <label class="label">DOB</label>
        <div class="control">
          <input type="date" name="dob" id="dob" class="input <?php if (isset($errors['dob'])) {
            echo 'is-danger';
          } ?>" placeholder="tt.mm.yyyy" required value="<?= $_POST['dob'] ?? ''; ?>">
          <?php
          //show error message for birthdate
          if (isset($errors["dob"])) {
            echo "<div class='is-danger'>" . $errors["dob"] . "</div>";
          }
          ?>
        </div>
      </div>

      <div class="file has-name">
        <label class="file-label">
          <input type="file" name="resume" id="resume" class="file-input <?php if (isset($errors['resume'])) {
            echo 'is-danger';
          } ?>" placeholder="Datei.pdf" required value="<?= $_POST['resume'] ?? ''; ?>">
          <?php
          //show error message for resumé
          if (isset($errors["resume"])) {
            echo "<div class='is-danger'>" . $errors["resume"] . "</div>";
          }

          ?>
          <span class="file-cta">
            <span class="file-icon">
              <i class="fas fa-upload"></i>
            </span>
            <span class="file-label"> Choose file... </span>
          </span>
          <span class="file-name"> file.pdf</span>
        </label>
      </div>


      <label class="checkbox">
        <input class="checkbox <?php if (isset($errors["agb"])) {
          echo 'is-danger';
        } ?>" type="checkbox" value="AGB gelesen" id="agb" name="agb" required <?php
         echo isset($_POST["agb"]) && !isset($errors["agb"]) ? 'checked' : '';
         ?>>
        I agree to the general terms and conditions
      </label>
      <br>
      <?php
      //show error message for general terms and conditions
      if (isset($errors["agb"])) {
        echo "<div class='is-danger'>" . $errors["agb"] . "</div>";
      }
      ?>
      </label>
      <br>
      <button class="button">Submit</button>

    </form>
  </main>
  <footer class="site-footer">
    <p>&copy; 2025 Joshua Jason. All rights reserved</p>
  </footer>
</body>

</html>