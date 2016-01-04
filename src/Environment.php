<?php

namespace Dotenv;

/**
 * Represents System Environment and actions on it
 *
 */
class Environment
{
    /**
     * Are we immutable?
     *
     * @var bool
     */
    protected $immutable = true;

    /**
     * @var Variable[]
     */
    protected $variables = array();

    /**
     * @param boolean $immutable
     *
     * @return void
     */
    public function setImmutable($immutable)
    {
        $this->immutable = $immutable;
    }

    /**
     * @return boolean
     */
    public function isImmutable()
    {
        return $this->immutable;
    }
}
