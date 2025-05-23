<?php

class BaseController
{
    protected function redirectTo($url) {
        header("Location: " . $url);
        exit();
    }

}