<?php
/**
 * This file is part of FPDI
 *
 * @package   Chronopost\Chronorelais\Lib\Fpdi
 * @copyright Copyright (c) 2020 Chronopost\Chronorelais\Lib GmbH & Co. KG (https://www.Chronopost\Chronorelais\Lib.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace Chronopost\Chronorelais\Lib\Fpdi\PdfReader;

use Chronopost\Chronorelais\Lib\Fpdi\FpdiException;

/**
 * Exception for the pdf reader class
 *
 * @package Chronopost\Chronorelais\Lib\Fpdi\PdfReader
 */
class PdfReaderException extends FpdiException
{
    /**
     * @var int
     */
    const KIDS_EMPTY = 0x0101;

    /**
     * @var int
     */
    const UNEXPECTED_DATA_TYPE = 0x0102;

    /**
     * @var int
     */
    const MISSING_DATA = 0x0103;
}
