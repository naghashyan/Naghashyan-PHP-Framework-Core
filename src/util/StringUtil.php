<?php
/**
 * Helper class that works with files
 * have 3 general function
 * 1. send file to user using remote or local file
 * 2. read local file dirs
 * 3. upload files
 *
 * @author Levon Naghashyan
 * <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2014-2023
 * @package ngs.framework.util
 * @version 4.5.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\util;

class StringUtil
{

    /**
     * Recursively calling generateTableFromArray function to collect array value into html table string
     *
     * @param $array
     *
     * @return string
     */
    public static function generateHtmlTableStringFromArray($array): string
    {
        $html = '<table style="border-collapse: collapse; width: 100%;">';

        foreach ($array as $key => $value) {
            $html .= '<tr>';
            $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 2px;">' . htmlspecialchars($key) . '</td>';
            $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 2px;">';

            if (is_array($value)) {
                $html .= self::generateHtmlTableStringFromArray($value);
            } else {
                $html .= htmlspecialchars($value);
            }

            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }
    
}
