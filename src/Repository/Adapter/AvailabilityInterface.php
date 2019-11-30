<?php

namespace Dotenv\Repository\Adapter;

interface AvailabilityInterface
{
    /**
     * Determines if the adapter is supported.
     *
     * @return bool
     */
    public function isSupported();
}
