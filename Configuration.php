<?php

function require_all_php_in_folder($folder)
{
    foreach (glob(__DIR__ . "/$folder/*.php") as $file) {
        require_once $file;
    }
}

require_all_php_in_folder('core');
require_all_php_in_folder('controller');
require_all_php_in_folder('model');

include_once('vendor/mustache/src/Mustache/Autoloader.php');

class Configuration
{
    public function getDatabase(): Database
    {
        $config = $this->getIniConfig();

        return new Database(
            $config["database"]["server"],
            $config["database"]["user"],
            $config["database"]["dbname"],
            $config["database"]["pass"]
        );
    }

    public function getIniConfig()
    {
        return parse_ini_file("configuration/config.ini", true);
    }

    public function getRouter(): Router
    {
        return new Router("getLobbyController", "show", $this);
    }

    public function getViewer(): MustachePresenter
    {
        //return new FileView();
        return new MustachePresenter("view");
    }

    public function getLobbyController(): LobbyController
    {
        return new LobbyController(new UserModel($this->getDatabase()), $this->getViewer());
    }

    public function getRegisterController(): RegisterController
    {
        return new RegisterController(new UserModel($this->getDatabase()), $this->getViewer(), new Mailer());
    }

    public function getLoginController(): LoginController
    {
        return new LoginController(new UserModel($this->getDatabase()), $this->getViewer());
    }

    public function getProfileController(): ProfileController
    {
        return new ProfileController(new UserModel($this->getDatabase()), $this->getViewer());
    }

    public function getGameController(): GameController
    {
        return new GameController(new GameModel($this->getDatabase()),$this->getViewer());
    }
    public function getRankingController(): RankingController
    {
        return new RankingController(new RankingModel($this->getDatabase()),$this->getViewer());
    }

    public function getQuestionController(): QuestionController
    {
        return new QuestionController(new QuestionModel($this->getDatabase()),$this->getViewer());
    }

    public function getEditorController(): EditorController
    {
        return new EditorController(new UserModel($this->getDatabase()), $this->getViewer());
    }

    public function getAdminController(): AdminController
    {
        return new AdminController(new AdminModel($this->getDatabase()), $this->getViewer());
    }

    public function getGraphController(): GraphController
    {
        return new GraphController(new AdminModel($this->getDatabase()), $this->getViewer());
    }

}