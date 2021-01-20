<?php


namespace Copper\Component\DB;

class DBConfigurator
{
    /** @var bool */
    public $enabled;

    /** @var string */
    public $host;
    /** @var string */
    public $dbname;
    /** @var string */
    public $user;
    /** @var string */
    public $password;

    /** @var string */
    public $engine;

    /** @var string */
    public $hashSalt;

    /** @var string */
    public $timezone;
}