<?php

session_start();

require_once ("./models/BdBase.php");

class UserInformations extends BdBase
{

    // variable
    private $req;
    private $data;
    private $id;

    // constructeur

    public function __construct($param, $id)
    {
        parent::__construct();

        foreach ($param as $key => $value) {
            // Assigner la valeur de $param à la propriété de l'objet
            $this->$key = $value;
        }

        $this->id = $id;

        // pour le POST (non utilisé pour le moment)
        $data = array(

        );

        foreach ($data as $element) {
            if (isset ($_POST[$element])) {
                $this->{$element} = $_POST[$element];
            }
        }
    }

    // méthode de requete
    public function simpleExtensionUserInformations()
    {
        $this->req = "SELECT users.uid, users.username, users.mail FROM users WHERE users.id_users = :id_user;";

        $stmt = $this->conn->prepare($this->req);
        $stmt->bindParam(':id_user', $this->id);
        $this->prepaQuery($stmt);
        return $this->data;
    }


    // méthode appelé par la méthode requete afin de fonctionner.
    public function prepaQuery($stmt)
    {
        try {
            $stmt->execute(); // execution de la requete.
        } catch (Exception $e) {
            var_dump($e);
        }
        $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    }

    public function getIdUser()
    {
        
    }
}