<?php
/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @see         https://github.com/PHPOffice/PHPWord
 * @copyright   2010-2018 PHPWord contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWord\Writer\HTML\Part;

use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\Writer\HTML;

/**
 * @since 0.11.0
 */
abstract class AbstractPart
{
    /**
     * @var \PhpOffice\PhpWord\Writer\HTML
     */
    private $parentWriter;

    /**
     * @return string
     */
    abstract public function write();

    /**
     * @param \PhpOffice\PhpWord\Writer\HTML $writer
     */
    public function setParentWriter(HTML $writer = null)
    {
        $this->parentWriter = $writer;
    }

    /**
     * @throws \PhpOffice\PhpWord\Exception\Exception
     *
     * @return \PhpOffice\PhpWord\Writer\HTML
     */
    public function getParentWriter()
    {
        if ($this->parentWriter !== null) {
            return $this->parentWriter;
        }
        throw new Exception('No parent WriterInterface assigned.');
    }
}
