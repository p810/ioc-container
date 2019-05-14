<?php

namespace p810\Container;

use RuntimeException;

/**
 * Exception thrown if a parameter of a class's constructor couldn't be resolved when attempting to instantiate it.
 */
class UnresolvableArgumentException extends RuntimeException
{
}
