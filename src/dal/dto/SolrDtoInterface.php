<?php
/**
 *
 * SolrDto parent Interface for all solr dtos
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2018
 * @package ngs.framework.dal.dto
 * @version 3.5.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\dal\dto;

interface SolrDtoInterface
{

    public function getSchemeArray(): array;
}