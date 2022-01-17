<?php
/**
 * This file is part of FPDI
 *
 * @package   Chronopost\Chronorelais\Lib\Fpdi
 * @copyright Copyright (c) 2020 Chronopost\Chronorelais\Lib GmbH & Co. KG (https://www.Chronopost\Chronorelais\Lib.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace Chronopost\Chronorelais\Lib\Fpdi\PdfParser\Filter;

use Chronopost\Chronorelais\Lib\Fpdi\PdfParser\PdfParserException;

/**
 * Exception for filters
 *
 * @package Chronopost\Chronorelais\Lib\Fpdi\PdfParser\Filter
 */
class FilterException extends PdfParserException
{
    const UNSUPPORTED_FILTER = 0x0201;

    const NOT_IMPLEMENTED = 0x0202;
}
