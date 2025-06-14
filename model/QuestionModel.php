<?php

class QuestionModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

}