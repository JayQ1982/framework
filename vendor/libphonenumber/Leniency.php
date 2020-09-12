<?php

namespace framework\vendor\libphonenumber;

use framework\vendor\libphonenumber\Leniency\Possible;
use framework\vendor\libphonenumber\Leniency\StrictGrouping;
use framework\vendor\libphonenumber\Leniency\Valid;
use framework\vendor\libphonenumber\Leniency\ExactGrouping;

class Leniency
{
    public static function POSSIBLE()
    {
        return new Possible;
    }

    public static function VALID()
    {
        return new Valid;
    }

    public static function STRICT_GROUPING()
    {
        return new StrictGrouping;
    }

    public static function EXACT_GROUPING()
    {
        return new ExactGrouping;
    }
}
