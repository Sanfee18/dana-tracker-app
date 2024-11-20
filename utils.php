<?php
require 'vendor/autoload.php';

use Google\Cloud\RecaptchaEnterprise\V1\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;

function validarRecaptcha($recaptchaKey, $token, $project, $action)
{
    $errors = [];

    // Specify the path to the service account key file. This can be an environment variable or a hardcoded path for local development.
    $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: 'dana-tracker-recaptcha.json';
    $clientOptions = ['credentials' => $credentialsPath];

    // Initialize the reCAPTCHA client with the specified credentials.
    $client = new RecaptchaEnterpriseServiceClient($clientOptions);
    $projectName = $client->projectName($project);

    // Set the event properties with the site key and user token.
    $event = (new Event())->setSiteKey($recaptchaKey)->setToken($token);

    // Create an assessment request.
    $assessment = (new Assessment())->setEvent($event);

    try {
        $response = $client->createAssessment($projectName, $assessment);
        // Log and check if the response is valid.
        if ($response->getTokenProperties()->getValid() === false) {
            $errors[] = 'Invalid reCAPTCHA token: ' . InvalidReason::name($response->getTokenProperties()->getInvalidReason());
        }

        // Ensure the action matches the expected one.
        if ($response->getTokenProperties()->getAction() !== $action) {
            $errors[] = 'Unexpected action in reCAPTCHA token.';
        }

        // Check the risk score.
        $score = $response->getRiskAnalysis()->getScore();
        if ($score < 0.5) {
            $errors[] = 'reCAPTCHA score is too low (score: ' . $score . ').';
        }
    } catch (Exception $e) {
        $errors[] = 'reCAPTCHA validation error: ' . $e->getMessage();
    }

    return $errors;
}

function validarDNI($dni)
{
    $dni = strtoupper(trim($dni));
    $letras = ['T', 'R', 'W', 'A', 'G', 'M', 'Y', 'F', 'P', 'D', 'X', 'B', 'N', 'J', 'Z', 'S', 'Q', 'V', 'H', 'L', 'C', 'K', 'E'];

    if (preg_match('/^[XYZ]\d{7}[A-Z]$/', $dni)) {
        $dni = str_replace(['X', 'Y', 'Z'], ['0', '1', '2'], $dni);
    } elseif (!preg_match('/^\d{8}[A-Z]$/', $dni)) {
        return false;
    }

    $numero = substr($dni, 0, -1);
    $letra = substr($dni, -1);
    $letraCalculada = $letras[$numero % 23];

    return $letra === $letraCalculada;
}

function maskDNI($dni)
{
    $lastThree = substr($dni, -3);

    $maskedDNI = str_repeat('*', strlen($dni) - 3) . $lastThree;
    return $maskedDNI;
}
