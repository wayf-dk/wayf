<?php

namespace WAYF;

interface AttributeQuarry {
    public function __construct(array $options);

    public function setup();

    public function mine(array $options);
}
