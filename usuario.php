<?php
include 'utils.php';

session_start();

// Check if the user has already accepted the message to avoid showing it again
if (!isset($_SESSION['accepted_message'])) {
  $_SESSION['accepted_message'] = false;
}

$nombre = $_POST['nombre'] ?? '';
$apellidos = $_POST['apellidos'] ?? '';
$dni = $_POST['dni'] ?? '';
$codigoPostal = $_POST['codigoPostal'] ?? '';

$errors = [];

$recaptchaKey = '6LfH9X8qAAAAAA_8HzVrS9DCVBXtVjksCMcRvuZZ';
$projectId = 'dana-tracker-54778';
$action = 'identify_user';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get the reCAPTCHA token from the form submission.
  $token = $_POST['g-recaptcha-response'] ?? '';

  // Validate the reCAPTCHA 
  $recaptchaErrors = validarRecaptcha($recaptchaKey, $token, $projectId, $action);

  if (!empty($recaptchaErrors)) {
    $errors[] = 'La validación de reCAPTCHA ha fallado. Por favor, intentelo de nuevo.';
  } else {
    // Validate the DNI
    $dni = strtoupper($_POST['dni']);

    if (!validarDNI($dni)) {
      $errors[] = 'El DNI/NIE introducido no es válido. Por favor, verifica e intenta de nuevo.';
    } else {
      // Store user info in the session
      $_SESSION['info_usuario'] = [
        'dni' => $dni,
        'nombre' => mb_strtoupper($_POST['nombre'], "UTF-8"),
        'apellidos' => mb_strtoupper($_POST['apellidos'], "UTF-8"),
        'codigoPostal' => $_POST['codigoPostal']
      ];

      // Redirect to the next page
      header('Location: afectados.php');
      exit();
    }
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
      position: realtive;
      left: 0;
      bottom: 0;
      width: 100%;
    }

    .grecaptcha-badge {
      visibility: hidden;
    }
  </style>
  <script src="https://www.google.com/recaptcha/api.js"></script>
  <title>Datos Personales | CIFRAS DANA VALENCIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<nav class="navbar bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-white" href="/">CIFRAS DANA VALENCIA</a>
    <div class="d-flex gap-3">
      <a class="btn btn-outline-light" role="button" href="https://atv.gva.es/es/dana2024" target="_blank">Ayudas Oficiales Dana</a>
    </div>
  </div>
</nav>

<div class="modal fade" id="datosPersonalesModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5">Introduce tus datos personales</h1>
      </div>
      <div class="modal-body">
        Antes de continuar, debes ingresar tus datos personales. Esto es necesario para asociar tu identidad a los datos que ingreses sobre los afectados.
        <div class="alert alert-info mt-3">
          Esta información se utilizará únicamente para verificar tu identidad en el caso de la introducción de datos falsos en la base de datos.
        </div>
      </div>
      <div class="modal-footer">
        <form method="POST" action="usuario.php">
          <button type="submit" name="accept" class="btn btn-primary mt-3">Aceptar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if (!$_SESSION['accepted_message']): ?>
  <script>
    window.addEventListener('load', function() {
      var myModal = new bootstrap.Modal(document.getElementById('datosPersonalesModal'));
      myModal.show();
    });
  </script>
<?php endif; ?>

<?php
// Handle form acceptance (redirect after accepting)
if (isset($_POST['accept'])) {
  $_SESSION['accepted_message'] = true;
  header("Location: usuario.php");
  exit();
}
?>

<div class="container my-5">
  <h2 class="text-center mb-5">Datos Personales</h2>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
      <div class="alert alert-danger">
        <?php echo $error; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>


  <form class="container" id="usuarioForm" action="usuario.php" method="POST">
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" class="form-control" id="nombre" name="nombre"
          placeholder="Introduce tu nombre"
          value="<?php echo htmlspecialchars($nombre); ?>"
          required>
      </div>
      <div class="col-md-4">
        <label for="apellidos" class="form-label">Apellidos</label>
        <input type="text" class="form-control" id="apellidos" name="apellidos"
          placeholder="Introduce tus apellidos"
          value="<?php echo htmlspecialchars($apellidos); ?>"
          required>
      </div>
    </div>
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <label for="dni" class="form-label">DNI / NIE</label>
        <input type="text" class="form-control" id="dni" name="dni" maxlength="9"
          placeholder="Introduce tu DNI o NIE"
          value="<?php echo htmlspecialchars($dni); ?>"
          pattern="(^[0-9]{8}[A-Za-z]$)|(^[XYZ][0-9]{7}[A-Za-z]$)"
          title="Introduce un DNI (8 números y 1 letra) o NIE (X/Y/Z seguido de 7 números y 1 letra)"
          required>
      </div>
      <div class="col-md-3">
        <label for="codigoPostal" class="form-label">Código postal</label>
        <input type="text" class="form-control" id="codigoPostal" name="codigoPostal"
          maxlength="5"
          placeholder="Introduce tu código postal"
          value="<?php echo htmlspecialchars($codigoPostal); ?>"
          pattern="^[0-9]{5}$"
          title="El código postal debe tener 5 dígitos"
          required>
      </div>
    </div>
    <div class="col-12">
      <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" value="" id="aceptarTerminos" required>
        <label class="form-check-label" for="aceptarTerminos">
          Acepto los términos y condiciones
        </label>
      </div>
    </div>
    <div class="d-flex col-12 gap-2">
      <a role="button" class="btn btn-secondary" href="index.php">Volver</a>
      <button type="submit" class="g-recaptcha btn btn-primary"
        data-sitekey="6LfH9X8qAAAAAA_8HzVrS9DCVBXtVjksCMcRvuZZ"
        data-callback='onSubmit'
        data-action='identify_user'>Continuar</button>
    </div>
    <div class="d-flex col-12">
      <small class="mt-2 text-muted" style="font-size: 0.8rem; opacity: 0.7;">
        This site is protected by reCAPTCHA and the Google
        <a href="https://policies.google.com/privacy">Privacy Policy</a> and
        <a href="https://policies.google.com/terms">Terms of Service</a> apply.
      </small>
    </div>
  </form>
</div>


<div class="footer bg-dark text-white py-3">
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
  function onSubmit(token) {
    document.getElementById("usuarioForm").submit();
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
  crossorigin="anonymous"></script>
</body>

</html>