<?php
include 'utils.php';

// Database connection
$mysqli = new mysqli("localhost", "root", "", "dana_valencia");

// Execute queries and fetch results
$fallecidosCount = $mysqli->query("SELECT COUNT(*) AS count FROM afectados WHERE estado = 'fallecido'");
$desaparecidosCount = $mysqli->query("SELECT COUNT(*) AS count FROM afectados WHERE estado = 'desaparecido'");
$afectadosList = $mysqli->query("SELECT nombre, apellidos, dni, edad, sexo, municipio, estado FROM afectados");

$fallecidos = $fallecidosCount->fetch_assoc()['count'];
$desaparecidos = $desaparecidosCount->fetch_assoc()['count'];
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
        }
    </style>
    <title>CIFRAS DANA VALENCIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
    <div class="navbar navbar-expand-lg bg-dark">
        <div class="container-fluid">
            <div class="d-flex">
                <a class="navbar-brand fw-bold text-white" href="/">CIFRAS DANA VALENCIA</a>
                <a class="btn btn-outline-light md-2" role="button" href="https://atv.gva.es/es/dana2024" target="_blank">Ayudas Oficiales Dana ATV</a>
            </div>

            <div class="d-flex">
                <a class="btn btn-primary fw-bold" role="button" href="usuario.php">+ AÑADIR AFECTADO</a>
            </div>
        </div>
    </div>


    <div class="container mt-5 mb-3 d-flex justify-content-center">
        <div class="card text-center border-dark" style="max-width: 500px; width: 100%;">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h4 class="card-text fw-bold">Fallecidos</h4>
                        <h1 id="deadCount"><?php echo $fallecidos; ?></h1>
                    </div>
                    <div class="col-6">
                        <h4 class="card-text fw-bold">Desaparecidos</h4>
                        <h1 id="missingCount"><?php echo $desaparecidos; ?></h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container d-flex justify-content-center mb-5">
        <div class="card text-bg-light border-light">
            <div class="card-body">
                <blockquote class="blockquote mb-0">
                    <p class="small" style="font-style: italic;">"No hay paz sin justicia, no hay justicia sin verdad"</p>
                    <footer class="blockquote-footer"><cite title="Juan Pablo II" class="small">Juan Pablo II</cite></footer>
                </blockquote>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <h3>Personas Afectadas</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Apellidos</th>
                    <th>DNI</th>
                    <!-- <th>Edad</th> -->
                    <!-- <th>Sexo</th> -->
                    <th>Estado</th>
                    <th>Municipio</th>
                </tr>
            </thead>
            <tbody id="peopleList">
                <?php foreach ($afectadosList as $afectado) {
                    echo "<tr>";
                    echo "<td>" . mb_convert_case($afectado['nombre'], MB_CASE_TITLE, "UTF-8") . "</td>";
                    echo "<td>" . mb_convert_case($afectado['apellidos'], MB_CASE_TITLE, "UTF-8") . "</td>";
                    echo "<td>" . maskDNI($afectado['dni']) . "</td>";
                    // echo "<td>" . $afectado['edad'] . "</td>";
                    // echo "<td>" . ucfirst($afectado['sexo']) . "</td>";
                    echo "<td>" . mb_convert_case($afectado['estado'], MB_CASE_TITLE, "UTF-8") . "</td>";
                    echo "<td>" . strtoupper($afectado['municipio']) . "</td>";
                    echo "</tr>";
                } ?>
            </tbody>
        </table>
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


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>