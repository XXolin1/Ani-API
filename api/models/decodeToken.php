<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class DecodeToken
{
    public function __construct()
    {
        //// Assurez-vous que la bibliothèque JWT est chargée
        //require_once "/vendor/autoload.php";
        //if (!isset($_ENV['JWT_SECRET'])) {
        //    throw new Exception("La clé secrète JWT n'est pas définie dans l'environnement.");
        //}
    }

    function verify_jwt($token)
    {
        try {
            $decodedToken = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

            // Vérifications de sécurité personnalisées
            if (
                $decodedToken->iss !== 'http://localhost/' ||
                $decodedToken->aud !== 'http://localhost/' ||
                empty($decodedToken->sub)
            ) {
                throw new Exception("Token invalide");
            }

            if ($decodedToken->exp < time()) {
                throw new Exception("Token expiré");
            }

            $id_users = $decodedToken->uid;

            return $id_users;

        } catch (ExpiredException $e) {
            // Décodage sans vérification de signature pour récupérer le payload
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                $id_users = $payload['uid'] ?? null;
            } else {
                $id_users = null;
            }

            http_response_code(403);
            echo json_encode([
                "message" => "Token expiré, renouvellement possible",
                "erreur" => $e->getMessage(),
                "id_users" => $id_users
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (SignatureInvalidException $e) {
            http_response_code(403);
            echo json_encode([
                "message" => "Signature du token invalide",
                "erreur" => $e->getMessage(),
                "id_users" => null
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            // Gestion des autres erreurs
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                $id_users = $payload['uid'] ?? null;
            } else {
                $id_users = null;
            }

            http_response_code(403);
            echo json_encode([
                "message" => "Token invalide",
                "erreur" => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}