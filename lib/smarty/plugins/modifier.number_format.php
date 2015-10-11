<?php
/**
 * Smarty number_format modifier plugin
 *
 * Type:     modifier<br>
 * Name:     number_format<br>
 * Purpose:  format nubmers using PHP number_format(); same variables used
 * @author   Greg Kuwaye <greg dot kuwaye at gmail dot com>
 */
 
function smarty_modifier_number_format($number, $decimals = 2, $dec_point = '.', $thousands_sep = ',')
{
    return number_format($number, $decimals, $dec_point, $thousands_sep);
}
?>
