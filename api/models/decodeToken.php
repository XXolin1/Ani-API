<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class DecodeToken
{
    public function verify_jwt($token)
    {
        try {
            $decodedToken = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

            // Vérification personnalisée
            if (
                $decodedToken->iss !== 'http://localhost/' ||
                $decodedToken->aud !== 'http://localhost/' ||
                empty($decodedToken->sub)
            ) {
                throw new Exception("Token invalide : claims incorrects");
            }

            return [
                "status" => "valid",
                "id_users" => $decodedToken->uid
            ];

        } catch (ExpiredException $e) {
            // Décoder sans signature pour récupérer le payload
            $payload = $this->decodePayload($token);
            return [
                "status" => "expired",
                "id_users" => $payload['uid'] ?? null,
                "message" => "Token expiré"
            ];

        } catch (SignatureInvalidException $e) {
            return [
                "status" => "invalid_signature",
                "id_users" => null,
                "message" => "Signature du token invalide"
            ];

        } catch (Exception $e) {
            $payload = $this->decodePayload($token);
            return [
                "status" => "invalid",
                "id_users" => $payload['uid'] ?? null,
                "message" => "Token invalide"
            ];
        }
    }

    private function decodePayload($token)
    {
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            return json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        }
        return [];
    }
}
