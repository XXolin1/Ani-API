<?php

use GrahamCampbell\ResultType\Success;
header("Access-Control-Allow-Origin: *"); 
header("Content-Type: application/json"); 
header("Access-Control-Allow-Methods: GET,HEAD,OPTIONS,POST,PUT");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");

$_POST = json_decode(file_get_contents("php://input"), true);

require_once __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// On récupère l'URI demandé en minuscules pour éviter les soucis de casse
$uri = strtolower($_SERVER['REQUEST_URI']);

// Liste des routes autorisées sans token, aussi en minuscules
$noAuthRoutes = ['/ani-api/api/connexion'];

// Normalisation de la route appelée
$uri = strtolower(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (!in_array($uri, $noAuthRoutes)) {
        // Récupération robuste du header Authorization
    $headers = getallheaders(); // Récupère tous les headers de la requête
    $authHeader = null;

    if (isset($headers['Authorization'])) { // Pour les serveurs qui respectent la casse
        $authHeader = $headers['Authorization'];
    } 
    elseif (isset($headers['authorization'])) {
        $authHeader = $headers['authorization']; // Pour les serveurs qui ne respectent pas la casse
    }


    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];

        require_once("./models/DecodeToken.php");
        $decodeToken = new DecodeToken();
        $result = $decodeToken->verify_jwt($token);

        switch ($result['status']) {
            case 'valid':
                $userId = $result['id_users'];
                // continue l'exécution
                break;

            case 'expired':
                http_response_code(403);
                echo json_encode([
                    "status" => "expired",
                    "message" => "Token expiré, renouvellement possible",
                    "id_users" => $result['id_users']
                ], JSON_UNESCAPED_UNICODE);
                exit;

            case 'invalid_signature':
            case 'invalid':
            default:
                http_response_code(403);
                echo json_encode([
                    "status" => "invalid",
                    "message" => $result['message'],
                    "id_users" => $result['id_users']
                ], JSON_UNESCAPED_UNICODE);
                exit;
        }

    } else {
        http_response_code(401);
        echo json_encode([
            "error" => "Token Bearer manquant ou mal formé"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}


try {

    if (!empty($_GET['demande'])) {
        $url = explode("/", filter_var($_GET['demande'], FILTER_SANITIZE_URL));
        $parametreGet = []; // pour get


        foreach ($_GET as $key => $value) { // pour les parametres des requetes get
            if ($key != "demande") {
                $parametreGet[$key] = $value;
            }
        }

        // a dev pour opti l'index + dynamique
        /*
        $dict = [
            "creation" => "accountCreation",
            "session" => "Authentication"
        ];

        foreach ($dict as $key => $value) {
            if ($url[0] == $key) {
                require_once ("./models/" . $value . ".php");
                $req = new $value();
                $response = $req->$key();
                sendResponse($response);
                exit(0);
            }
        }
        throw new Exception("La demande n'est pas valide, vérifiez l'url");
        */
        switch ($url[0]) {

            case "accountCreation":
                require_once("./controllers/controllerAccountCreation.php");
                $reqCreation = new ControllerAccountCreation($parametreGet, $url); // faire controller
                $Creation = $reqCreation->controller();
                sendResponse($Creation);
                exit(0);

            case "connexion":
                require_once("./models/session.php");
                $reqConnexion = new Authentication();
                $connexion = $reqConnexion->connexion();
                CheckConnexion($connexion);
                exit(0);

            case "entreprises":
                require_once("./controllerEntreprise.php");
                $reqEntreprise = new ControllerEntreprise($parametreGet, $url);
                $response = $reqEntreprise->controller();
                sendResponse($response);
                break;

            case "animes":
                require_once("./controllers/controllerAnime.php");
                $queryAnime = new ControllerAnime($parametreGet, $url);
                $response = $queryAnime->controller();
                sendResponse($response);
                break;

            case "userInformations":
                require_once("./controllers/controllerUserInformation.php");
                $reqUserInformation = new ControllerUserInformation($parametreGet, $url, 1); // a modif
                $response = $reqUserInformation->controller();
                sendResponse($response);
                break;

            default:
                throw new Exception("La demande n'est pas valide, vérifiez l'url");

        }
    } else {
        throw new Exception("Problème de récupération de données.");
    }
} catch (Exception $e) {
    $erreur = [         // message d'erreur
        "message" => $e->getMessage(),
        "code" => $e->getCode()
    ];
}

function sendResponse($response)
{
    echo json_encode($response, JSON_UNESCAPED_UNICODE); // encode en prenant en compte les caractères unicode.
}

function CheckConnexion($Connexion)
{
    if (isset($Connexion['success']) && $Connexion['success'] === false) {
        // Mauvaise authentification
        http_response_code(401);
        echo json_encode([
            'error' => $Connexion['error'] ?? 'Unauthorized'
        ]);
        exit;
    }

    // Si on a un token, connexion réussie
    if (isset($Connexion['success']) && isset($Connexion['token'])) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'token'   => $Connexion['token']
        ]);
        exit;
    }

    // Cas imprévu
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur interne'
    ]);
    exit;
}
