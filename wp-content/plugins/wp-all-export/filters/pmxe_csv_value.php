<?php

function pmxe_pmxe_csv_value($value)
{
    return preg_replace("/^[=\+\-\@]/", "'$0", $value);
}