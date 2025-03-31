<?php

namespace pachel;

class Button
{
    /**
     * @var bool|object $isVisible
     */
    public $isVisible = true;
    public $method = null;
    private $actions = [];
    private $name;
    private $text;
    public static
        $RUN_BEFORE_ACTION = 0,
        $RUN_AFTER_ACTION = 1,
        $RUN_WITHOUT_ACTION = 2;

    public function __construct($name)
    {
        if (is_string($name)) {
            $this->name = $name;
        }
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function addAction($method, $position)
    {

    }

    public function setIsVisible($method)
    {

    }
    public function isVisible()
    {
        return $this->isVisible;
    }
}