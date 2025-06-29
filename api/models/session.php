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

        header("Content-type: text/plain");
    }

    /*
        public function creation()
        {
            if (!isset($_POST["login"], $_POST["password"], $_POST["email"], $_POST["age"])) {
                exit(0);
            }
            $this->pseudo = $_POST["login"];
            $this->password = $_POST["password"];
            $this->email = $_POST["email"];
            $this->age = $_POST["age"];
        }
    */

    public function connexion()
    {
        if (!isset($_POST["mail"], $_POST["password"])) {
            exit(0);
        }

        if ($_POST["id_users"]) {
            $this->user_id = $_POST["id_users"];
            $this->jwt = $this->generateJwtToken();
            return $this->jwt;
        } 
        else {
            $this->mail = $_POST["mail"];
            $this->password = $_POST["password"]; // voir password_hash pour cryptage.  

            if ($this->Login()) {

                $this->jwt = $this->generateJwtToken();

                return $this->jwt;

            } else {
                // Authentification echouee         
                return false;
            }
        }
    }


    private function Login()
    {
        $this->req = "SELECT * FROM users WHERE mail=:mail;"; // a modif
        $stmt = $this->conn->prepare($this->req);

        try {
            $stmt->execute([':mail' => $this->mail]);
        } catch (PDOException $e) {
            echo "Erreur lors de l'exécution de la requête : " . $e->getMessage();
        }

        // Vérifier s'il y a un utilisateur avec ce nom d'utilisateur
        $user = $stmt->fetch();
        if (!$user) {
            return false; // Aucun utilisateur trouvé avec ce nom d'utilisateur
        }

        // Verifier si le mot de passe est correct
        if ($this->password === $user['password']) {
            // Authentification réussie
            //$_SESSION['type_utilisateur'] = $user['type_utilisateur']; // Stocker le type d'utilisateur dans la session
            $this->data = $user;

            $this->user_id = $user['id_users']; // Stocker l'ID de l'utilisateur
            return true;
        } else {
            // Mot de passe incorrect
            return false;
        }
    }

    private function generateJwtToken() // Génération du token JWT
    {
        $payload = [
            'iss' => 'http://localhost/',                  // Émetteur (issuer)
            'aud' => 'http://localhost/',                  // Destinataire (audience)
            'sub' => $this->user_id,                          // Sujet (souvent identifiant unique ou email)
            'iat' => time(),                               // Date d’émission
            'exp' => time() + 3600,                        // Expiration à +1h
            'uid' => $this->user_id                        // ID utilisateur
        ];

        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }
}