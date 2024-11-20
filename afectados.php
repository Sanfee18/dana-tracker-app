<?php
include 'utils.php';

session_start();

$errors = [];

// Load municipios from the JSON file
$listaMunicipios = json_decode(file_get_contents('municipios_valencia.json'), true);

// Connect to the database
$mysqli = new mysqli("localhost", "root", "", "dana_valencia");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
  // Clear session variables
  unset($_SESSION['info_usuario']);
  unset($_SESSION['accepted_message']);
  unset($_SESSION['errors']);

  // Redirect to the index page
  header('Location: index.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
  // Retrieve the already formated user data from session
  $nombre_usuario = $_SESSION['info_usuario']['nombre'];
  $apellidos_usuario = $_SESSION['info_usuario']['apellidos'];
  $dni_usuario = $_SESSION['info_usuario']['dni'];
  $codigo_postal_usuario = $_SESSION['info_usuario']['codigoPostal'];

  // Start a transaction
  $mysqli->begin_transaction();

  try {
    // Check if the user already exists
    $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE dni = ?");
    $stmt->bind_param("s", $dni_usuario);
    $stmt->execute();
    $stmt->store_result();

    // If the user doesn't exist, insert it
    if ($stmt->num_rows === 0) {
      $stmt = $mysqli->prepare("INSERT INTO usuarios (nombre, apellidos, dni, codigo_postal) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $nombre_usuario, $apellidos_usuario, $dni_usuario, $codigo_postal_usuario);
      $stmt->execute();
      $user_id = $stmt->insert_id; // Get the user ID of the newly inserted user
    } else {
      // User already exists, get their existing user_id
      $stmt->bind_result($user_id);
      $stmt->fetch();
    }

    // Retrieve the affected people data from the form
    $nombres = $_POST['nombreAfectado'];
    $apellidos = $_POST['apellidosAfectado'];
    $sexos = $_POST['sexoAfectado'];
    $edades = $_POST['edadAfectado'];
    $dnis = $_POST['dniAfectado'];
    $municipios = $_POST['municipioAfectado'];
    $estados = $_POST['estadoAfectado'];

    // Insert each affected person
    for ($i = 0; $i < count($nombres); $i++) {
      $nombre = mb_strtoupper($nombres[$i], "UTF-8");
      $apellido = mb_strtoupper($apellidos[$i], "UTF-8");
      $dni = strtoupper($dnis[$i]);
      $edad = (int) $edades[$i];
      $sexo = $sexos[$i];
      $municipio = $municipios[$i];
      $estado = $estados[$i];

      if (!validarDNI($dni)) {
        $errors[] = "El DNI/NIE introducido para {$nombre} {$apellido} no es válido y no ha sido insertado. Por favor, verifica e intenta de nuevo.";
      } else {
        $stmt = $mysqli->prepare("INSERT INTO afectados (nombre, apellidos, dni, edad, sexo, municipio, estado, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisssi", $nombre, $apellido, $dni, $edad, $sexo, $municipio, $estado, $user_id);
        $stmt->execute();
      }
    }

    // Commit the transaction
    $mysqli->commit();

    if (!empty($errors)) {
      $_SESSION['errors'] = $errors;
      header('Location: afectados.php');
      exit();
    } else {
      // Redirect to home page
      header('Location: index.php');
      unset($_SESSION['info_usuario']);
      unset($_SESSION['accepted_message']);
      unset($_SESSION['errors']);
      exit();
    }
  } catch (Exception $e) {
    // Rollback the transaction in case of an error
    $mysqli->rollback();
    echo "Error: " . $e->getMessage();
  }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    .footer {
      /* Cambiado para no afectar la experiencia en móviles */
      position: relative;
      left: 0;
      bottom: 0;
      width: 100%;
    }
  </style>
  <title>Añadir Afectados | CIFRAS DANA VALENCIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
  <nav class="navbar bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-white" href="/">CIFRAS DANA VALENCIA</a>
      <div class="d-flex gap-3">
        <a class="btn btn-outline-light" role="button" href="https://atv.gva.es/es/dana2024" target="_blank">Ayudas Oficiales Dana</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <h2 class="text-center mb-5">Datos de los Afectados</h2>

    <?php if (!empty($_SESSION['errors'])): ?>
      <?php foreach ($_SESSION['errors'] as $error): ?>
        <div class="alert alert-danger">
          <?php echo $error; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <form id="afectadoForm" action="afectados.php" method="POST">
      <div id="afectadosContainer">
        <div class="afectado-group mb-4 border p-3">
          <div class="row mb-3">
            <div class="col-md-4">
              <label for="nombreAfectado" class="form-label">Nombre</label>
              <input type="text" class="form-control" name="nombreAfectado[]"
                placeholder="Introduce el nombre del afectado" required>
            </div>
            <div class="col-md-4">
              <label for="apellidosAfectado" class="form-label">Apellidos</label>
              <input type="text" class="form-control" name="apellidosAfectado[]"
                placeholder="Introduce los apellidos del afectado" required>
            </div>
            <div class="col-md-2">
              <label for="sexoAfectado" class="form-label">Sexo</label>
              <select class="form-select form-control" name="sexoAfectado[]" required>
                <option value="" disabled selected>Selecciona el sexo</option>
                <option value="MASCULINO">Masculino</option>
                <option value="FEMENINO">Femenino</option>
                <option value="OTRO">Otro</option>
              </select>
            </div>
            <div class="col-md-1">
              <label for="edadAfectado" class="form-label">Edad</label>
              <input type="number" class="form-control" name="edadAfectado[]" min="1" max="120" maxlength="3" required>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label for="dniAfectado" class="form-label">DNI / NIE</label>
              <input type="text" class="form-control" name="dniAfectado[]" maxlength="9"
                placeholder="Introduce el DNI o NIE del afectado"
                pattern="(^[0-9]{8}[A-Za-z]$)|(^[XYZ][0-9]{7}[A-Za-z]$)"
                title="Introduce un DNI (8 números y 1 letra) o NIE (X/Y/Z seguido de 7 números y 1 letra)" required>
            </div>
            <div class="col-md-4">
              <label for="municipioAfectado" class="form-label">Municipio</label>
              <select class="form-select form-control" name="municipioAfectado[]" required>
                <option value="" disabled selected>Seleccione un municipio</option>
                <?php foreach ($listaMunicipios['municipios'] as $municipio) {
                  echo "<option value='{$municipio}'>{$municipio}</option>";
                } ?>
              </select>
            </div>
            <div class="col-md-3">
              <label for="estadoAfectado" class="form-label">Estado</label>
              <select class="form-select form-control" name="estadoAfectado[]" required>
                <option value="" disabled selected>Selecciona el estado</option>
                <option value="FALLECIDO">Fallecido</option>
                <option value="DESAPARECIDO">Desaparecido</option>
              </select>
            </div>
          </div>

          <button type="button" class="btn btn-danger btn-sm remove-afectado">Eliminar</button>
        </div>
      </div>

      <div class="d-flex col-12 gap-2 mb-4">
        <button type="button" id="addAfectado" class="btn btn-dark">+ Añadir Otro Afectado</button>
      </div>

      <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <button type="submit" class="btn btn-primary" name="submit">Registrar Afectados</button>
        <button type="submit" class="btn btn-secondary cancel-btn" name="cancel">Cancelar</button>
      </div>
  </div>

  <div class="footer bg-dark text-white mt-5 py-3">
    <div class="container text-center">
      <div class="d-flex justify-content-center align-items-center gap-2">
        <a href="#" class="text-white text-decoration-none">Términos y Condiciones</a>
        <span class="mx-2">|</span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512" style="width: 20px; height: 20px; vertical-align: middle;">
          <path d="M165.9 397.4c0 2-2.3 3.6-5.2 3.6-3.3 .3-5.6-1.3-5.6-3.6 0-2 2.3-3.6 5.2-3.6 3-.3 5.6 1.3 5.6 3.6zm-31.1-4.5c-.7 2 1.3 4.3 4.3 4.9 2.6 1 5.6 0 6.2-2s-1.3-4.3-4.3-5.2c-2.6-.7-5.5 .3-6.2 2.3zm44.2-1.7c-2.9 .7-4.9 2.6-4.6 4.9 .3 2 2.9 3.3 5.9 2.6 2.9-.7 4.9-2.6 4.6-4.6-.3-1.9-3-3.2-5.9-2.9zM244.8 8C106.1 8 0 113.3 0 252c0 110.9 69.8 205.8 169.5 239.2 12.8 2.3 17.3-5.6 17.3-12.1 0-6.2-.3-40.4-.3-61.4 0 0-70 15-84.7-29.8 0 0-11.4-29.1-27.8-36.6 0 0-22.9-15.7 1.6-15.4 0 0 24.9 2 38.6 25.8 21.9 38.6 58.6 27.5 72.9 20.9 2.3-16 8.8-27.1 16-33.7-55.9-6.2-112.3-14.3-112.3-110.5 0-27.5 7.6-41.3 23.6-58.9-2.6-6.5-11.1-33.3 2.6-67.9 20.9-6.5 69 27 69 27 20-5.6 41.5-8.5 62.8-8.5s42.8 2.9 62.8 8.5c0 0 48.1-33.6 69-27 13.7 34.7 5.2 61.4 2.6 67.9 16 17.7 25.8 31.5 25.8 58.9 0 96.5-58.9 104.2-114.8 110.5 9.2 7.9 17 22.9 17 46.4 0 33.7-.3 75.4-.3 83.6 0 6.5 4.6 14.4 17.3 12.1C428.2 457.8 496 362.9 496 252 496 113.3 383.5 8 244.8 8zM97.2 352.9c-1.3 1-1 3.3 .7 5.2 1.6 1.6 3.9 2.3 5.2 1 1.3-1 1-3.3-.7-5.2-1.6-1.6-3.9-2.3-5.2-1zm-10.8-8.1c-.7 1.3 .3 2.9 2.3 3.9 1.6 1 3.6 .7 4.3-.7 .7-1.3-.3-2.9-2.3-3.9-2-.6-3.6-.3-4.3 .7zm32.4 35.6c-1.6 1.3-1 4.3 1.3 6.2 2.3 2.3 5.2 2.6 6.5 1 1.3-1.3 .7-4.3-1.3-6.2-2.2-2.3-5.2-2.6-6.5-1zm-11.4-14.7c-1.6 1-1.6 3.6 0 5.9 1.6 2.3 4.3 3.3 5.6 2.3 1.6-1.3 1.6-3.9 0-6.2-1.4-2.3-4-3.3-5.6-2z" />
        </svg>
        <a href="https://github.com/Sanfee18/dana-tracker-app" class="text-white text-decoration-none" target="_blank">Repositorio en GitHub</a>
        <span class="mx-2">|</span>
        <strong>Contacto:</strong><a href="mailto:sanfeytinfo@gmail.com" class="text-white text-decoration-none">sanfeytinfo@gmail.com</a>
      </div>
      <div class="mt-4">
        <p class="mb-0">&copy; 2024 Dana Tracker App por David Sanfelix. Todos los derechos reservados.</p>
      </div>
    </div>
  </div>

  <script>
    const afectadosContainer = document.getElementById('afectadosContainer');
    const addAfectadoButton = document.getElementById('addAfectado');

    // Function to update the visibility of the "Eliminar Afectado" buttons
    function updateRemoveButtonVisibility() {
      const removeButtons = document.querySelectorAll('.remove-afectado');
      if (removeButtons.length === 1) {
        removeButtons[0].style.display = 'none';
      } else {
        removeButtons.forEach(button => button.style.display = 'inline-block');
      }
    }

    // Call the function initially to check the first form group
    updateRemoveButtonVisibility();

    // Function to add a new "Afectado" form group
    addAfectadoButton.addEventListener('click', () => {
      const afectadoGroup = document.querySelector('.afectado-group').cloneNode(true);
      afectadoGroup.querySelectorAll('input').forEach(input => input.value = '');
      afectadosContainer.appendChild(afectadoGroup);

      // Add remove button functionality
      afectadoGroup.querySelector('.remove-afectado').addEventListener('click', () => {
        afectadoGroup.remove();
        updateRemoveButtonVisibility();
      });

      updateRemoveButtonVisibility();
    });

    // Initialize remove button for the first group
    document.querySelector('.remove-afectado').addEventListener('click', (event) => {
      if (document.querySelectorAll('.afectado-group').length > 1) {
        event.target.closest('.afectado-group').remove();
      }
      updateRemoveButtonVisibility();
    });
  </script>

  <script>
    document.querySelector('.cancel-btn').addEventListener('click', function(event) {
      // Get all the form inputs
      const inputs = document.querySelectorAll('input[required]');

      // Get all the form selects
      const selects = document.querySelectorAll('select[required]');

      // Remove the 'required' attribute from each input
      inputs.forEach(function(input) {
        input.removeAttribute('required');
      });

      // Remove the 'required' attribute from each select
      selects.forEach(function(select) {
        select.removeAttribute('required');
      });
    });
  </script>

</body>

</html>