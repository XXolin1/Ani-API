<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once("./models/BdBase.php");

class Authentication extends BdBase
{
    private $user_id;
    private $pseudo;
    private $password;
    private $age;
    private $mail;
    private $req;
    private $data;
    private $jwt;
    private $decodedToken;

    public function __construct()
    {
        parent::__construct();
        header("Content-type: application/json");
    }

    public function connexion()
    {
        // Unifie la récupération des données POST ou JSON
        $payload = $_POST;
        if (empty($payload)) {
            $input = file_get_contents('php://input');
            $payload = json_decode($input, true) ?: [];
        }

        // Cas de renouvellement de token avec id_users
        if (isset($payload["id_users"])) {
            $this->user_id = $payload["id_users"];
            $this->jwt = $this->generateJwtToken();
            // Retourne toujours sous forme de tableau pour JSON
            return [
            'success' => true,
            'token'   => $this->jwt
        ];
        }

        // Cas connexion classique
        if (!isset($payload["mail"], $payload["password"])) {
            // Manque paramètres
            return [
                'success' => false,
                'error' => 'Paramètres mail ou password manquants'
            ];
        }

        $this->mail = $payload["mail"];
        $this->password = $payload["password"];

        if ($this->Login()) {
            $this->jwt = $this->generateJwtToken();
            return [
            'success' => true,
            'token'   => $this->jwt
        ];
        } else {
            // Authentification échouée
            return [
                'success' => false,
                'error' => 'Identifiants invalides ou utilisateur non trouvé'
            ];
        }
    }

    private function Login()
    {
        $this->req = "SELECT * FROM users WHERE mail=:mail;";
        $stmt = $this->conn->prepare($this->req);

        try {
            $stmt->execute([':mail' => $this->mail]);
        } catch (PDOException $e) {
            // Log ou gérer proprement l'erreur en prod
            return false;
        }

        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }

        // Vérifier si le mot de passe est correct
        if ($this->password === $user['password']) {
            $this->data = $user;
            $this->user_id = $user['id_users'];
            return true;
        } else {
            return false;
        }
    }

    private function generateJwtToken()
    {
        $payload = [
            'iss' => 'http://localhost/',
            'aud' => 'http://localhost/',
            'sub' => $this->user_id,
            'iat' => time(),
            'exp' => time() + 20, // valeur de dev
            'uid' => $this->user_id
        ];

        return JWT::encode($payload, $_ENV['JWT_SECRET'], $_ENV['JWT_ALGO']);
    }
}