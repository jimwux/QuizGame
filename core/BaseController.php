<?php

class BaseController
{
    protected function redirectTo($url) {
        header("Location: " . $url);
        exit();
    }

    public function validateSession() {
//        session_start();
        if (!isset($_SESSION['id'])) {
            header("Location: login");
            exit;
        }
    }

}