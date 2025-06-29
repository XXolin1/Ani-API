<?php
class ControllerUserInformation
{
    private $paramGet;
    private $url;
    private $id;
    private $query;

    public function __construct($param, $url, $id)
    {
        $this->paramGet = $param;
        $this->url = $url;
        $this->id = $id;
    }

    public function controller(): mixed
    {
        require_once ("./models/userInformation.php");
        $this->query = new UserInformations($this->paramGet, $this->id);

        switch ($this->url[1]) {
            case "simpleInformation":
                $response = $this->query->simpleExtensionUserInformations();
                break;

            default:
                throw new Exception(message: "Erreur de requete.");
        }
        return $response;

    }
}
?>