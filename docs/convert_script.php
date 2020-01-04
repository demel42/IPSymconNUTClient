<?php

declare(strict_types=1);

$ident = $_IPS['ident'];
$value = $_IPS['value'];

$ret = '';
if ($ident == 'battery.runtime') {
    $ret = $value / 60.0;
}

echo $ret;