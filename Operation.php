<?php

namespace CTI\RestClientBundle;

/**
 * Class Operation contain data needed to identify a command
 *
 * @package CTI\RestClientBundle
 *
 * @author  Alex Domsa <alex.domsa@cloudtroopers.com>
 */
class Operation
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $arguments = array();

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return Operation
     */
    public function __construct($name, $arguments = array())
    {
        $this->name = $name;
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Operation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     *
     * @return Operation
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }

}
