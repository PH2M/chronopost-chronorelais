<?php
/**
 * This file is part of FPDI
 *
 * @package   Chronopost\Chronorelais\Lib\Fpdi
 * @copyright Copyright (c) 2020 Chronopost\Chronorelais\Lib GmbH & Co. KG (https://www.Chronopost\Chronorelais\Lib.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace Chronopost\Chronorelais\Lib\Fpdi;

use Chronopost\Chronorelais\Lib\Fpdf\Fpdf;

/**
 * Class FpdfTpl
 *
 * This class adds a templating feature to FPDF.
 *
 * @package Chronopost\Chronorelais\Lib\Fpdi
 */
class FpdfTpl extends Fpdf
{
    use FpdfTplTrait;
}
