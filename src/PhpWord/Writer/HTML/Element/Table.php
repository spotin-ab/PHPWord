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

namespace PhpOffice\PhpWord\Writer\HTML\Element;

use PhpOffice\PhpWord\Style\Shading;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

/**
 * Table element HTML writer
 *
 * @since 0.10.0
 */
class Table extends AbstractElement
{
    /**
     * Write table
     *
     * @return string
     */
    public function write()
    {
        if (!$this->element instanceof \PhpOffice\PhpWord\Element\Table) {
          return '';
        }

        $content = '';
        $rows = $this->element->getRows();
        $rowCount = count($rows);
        if ($rowCount > 0) {
            $content .= '<table' . self::getTableStyle($this->element->getStyle()) . '>' . PHP_EOL;

            for ($i = 0; $i < $rowCount; $i++) {
                // Put different table conditions on the cells
                $rowOddOrEven = '';
                if ($i > 0) {
                    $rowOddOrEven = ($i % 2 == 0) ? 'band2Horz' : 'band1Horz';
                }
                $rowName = 'row';
                if ($i == 0) {
                    $rowName = 'firstRow';
                } else if ($i == $rowCount - 1) {
                    $rowName = 'lastRow';
                }
              
                /** @var $row \PhpOffice\PhpWord\Element\Row Type hint */
                $rowStyle = $rows[$i]->getStyle();
                // $height = $row->getHeight();
                $tblHeader = $rowStyle->isTblHeader();
                $content .= "<tr class=\"{$rowName} {$rowOddOrEven}\">" . PHP_EOL;
                $rowCells = $rows[$i]->getCells();
                $rowCellCount = count($rowCells);
                for ($j = 0; $j < $rowCellCount; $j++) {
                    $colName = 'col';
                    if ($j == 0) {
                        $colName = 'firstCol';
                    } else if ($j == $rowCellCount - 1) {
                        $colName = 'lastCol';
                    }
                    $cellStyle = $rowCells[$j]->getStyle();
                    $cellStyleCss = self::getTableStyle($cellStyle, $i == 0, $j == 0);
                    $cellBgColor = $cellStyle->getBgColor();
                    $cellBgColor === 'auto' && $cellBgColor = null; // auto cannot be parsed to hexadecimal number
                    $cellFgColor = null;
                    if ($cellBgColor) {
                        $red = hexdec(substr($cellBgColor, 0, 2));
                        $green = hexdec(substr($cellBgColor, 2, 2));
                        $blue = hexdec(substr($cellBgColor, 4, 2));
                        $cellFgColor = (($red * 0.299 + $green * 0.587 + $blue * 0.114) > 186) ? null : 'ffffff';
                    }
                    $cellColSpan = $cellStyle->getGridSpan();
                    $cellRowSpan = 1;
                    $cellVMerge = $cellStyle->getVMerge();
                    // If this is the first cell of the vertical merge, find out how man rows it spans
                    if ($cellVMerge === 'restart') {
                        for ($k = $i + 1; $k < $rowCount; $k++) {
                            $kRowCells = $rows[$k]->getCells();
                            if (isset($kRowCells[$j]) && $kRowCells[$j]->getStyle()->getVMerge() === 'continue') {
                                $cellRowSpan++;
                            } else {
                                break;
                            }
                        }
                    }
                    // Ignore cells that are merged vertically with previous rows
                    if ($cellVMerge !== 'continue') {
                        $cellTag = $tblHeader ? 'th' : 'td';
                        $cellColSpanAttr = (is_numeric($cellColSpan) && ($cellColSpan > 1) ? " colspan=\"{$cellColSpan}\"" : '');
                        $cellRowSpanAttr = ($cellRowSpan > 1 ? " rowspan=\"{$cellRowSpan}\"" : '');
                        $cellBgColorAttr = (is_null($cellBgColor) ? '' : " bgcolor=\"#{$cellBgColor}\"");
                        $cellFgColorAttr = (is_null($cellFgColor) ? '' : " color=\"#{$cellFgColor}\"");
                        $content .= "<{$cellTag} class=\"{$colName}\" {$cellStyleCss}{$cellColSpanAttr}{$cellRowSpanAttr}{$cellBgColorAttr}{$cellFgColorAttr}>" . PHP_EOL;
                        $writer = new Container($this->parentWriter, $rowCells[$j]);
                        $content .= $writer->write();
                        if ($cellRowSpan > 1) {
                            // There shouldn't be any content in the subsequent merged cells, but lets check anyway
                            for ($k = $i + 1; $k < $rowCount; $k++) {
                                $kRowCells = $rows[$k]->getCells();
                                if (isset($kRowCells[$j]) && $kRowCells[$j]->getStyle()->getVMerge() === 'continue') {
                                    $writer = new Container($this->parentWriter, $kRowCells[$j]);
                                    $content .= $writer->write();
                                } else {
                                    break;
                                }
                            }
                        }
                        $content .= "</{$cellTag}>" . PHP_EOL;
                    }
                }
                $content .= '</tr>' . PHP_EOL;
            }
            $content .= '</table>' . PHP_EOL;
        }

        return $content;
    }

    /**
     * Translates Table style in CSS equivalent
     *
     * @param string|\PhpOffice\PhpWord\Style\Table|\PhpOffice\PhpWord\Style\Cell|null $tableStyle
     * @param bool $useBorderTop
     * @param bool $useBorderTop
     * @return string
     */
  private static function getTableStyle($tableStyle = null, $useBorderTop=true, $useBorderLeft=true)
    {
        if ($tableStyle == null) {
            return '';
        }
        if (is_string($tableStyle)) {
            $style = ' class="' . $tableStyle;

            return $style . '"';
        }

        $style = self::getTableStyleString($tableStyle, $useBorderTop, $useBorderLeft);
        if ($style === '') {
            return '';
        }

        return ' style="' . $style . '"';
    }

    /**
     * Translates Table style in CSS equivalent
     *
     * @param string|\PhpOffice\PhpWord\Style\Table|\PhpOffice\PhpWord\Style\Cell $tableStyle
     * @param bool $useBorderTop
     * @param bool $useBorderTop
     * @return string
     */
    public static function getTableStyleString($tableStyle, $useBorderTop=true, $useBorderLeft=true)
    {
        $style = '';
        if (method_exists($tableStyle, 'getLayout')) {
            if ($tableStyle->getLayout() == \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED) {
                $style .= 'table-layout: fixed;';
            } elseif ($tableStyle->getLayout() == \PhpOffice\PhpWord\Style\Table::LAYOUT_AUTO) {
                $style .= 'table-layout: auto;';
            }
        }

        if (method_exists($tableStyle, 'getWidth')) {
            $width = $tableStyle->getWidth();
            if ($width > 0) {
                $width /= 50;
                $style .= " width: {$width}%;";
            }
        }

        if (method_exists($tableStyle, 'getVAlign')) {
            switch ($tableStyle->getVAlign()) {
            case VerticalJc::CENTER:
                $style .= ' vertical-align: middle;';
                break;
            case VerticalJc::BOTTOM:
                $style .= ' vertical-align: bottom;';
                break;
            default:
                $style .= ' vertical-align: top;';
                break;
            }
        }

        $dirs = array('Bottom', 'Right');
        if ($useBorderTop) $dirs[] = 'Top';
        if ($useBorderLeft) $dirs[] = 'Left';
        $testmethprefix = 'getBorder';
        foreach ($dirs as $dir) {
            $style .= self::getTableBorderStyleString($tableStyle, $testmethprefix . $dir, $dir);
        }

        $shading = $tableStyle->getShading();
        if ($shading !== null) {
          switch ($shading->getPattern()) {
          case Shading::PATTERN_CLEAR:
              $style .= ' background-color: #' . $shading->getFill() . ';';
              break;
          case Shading::PATTERN_SOLID:
              $style .= ' background-color: #' . $shading->getColor() . ';';
              break;
          }
        }

        return $style;
    }

    public static function getTableBorderStyleString($tableStyle, $testmethprefix, $dir) {
        $style = '';
        $testmeth = $testmethprefix . 'Style';
        if (method_exists($tableStyle, $testmeth)) {
            $outval = $tableStyle->{$testmeth}();
            if (is_string($outval) && 1 == preg_match('/^[a-z]+$/', $outval)) {
                if      ($outval == 'single') $outval = 'solid';
                else if ($outval == 'nil')    $outval = 'none';
                $style .= ' border-' . lcfirst($dir) . '-style: ' . $outval . ';';
            }
        }
        $testmeth = $testmethprefix . 'Color';
        if (method_exists($tableStyle, $testmeth)) {
            $outval = $tableStyle->{$testmeth}();
            if (is_string($outval)) {
                if (1 == preg_match('/^[a-z]+$/', $outval)) {
                    if ($outval == 'auto') $outval = '#000000';
                    $style .= ' border-' . lcfirst($dir) . '-color: ' . $outval . ';';
                } else if (is_string($outval) && 1 == preg_match('/^[0-9a-fA-F]+$/', $outval)) {
                    $style .= ' border-' . lcfirst($dir) . '-color: #' . $outval . ';';
                }
            }
        }
        $testmeth = $testmethprefix . 'Size';
        if (method_exists($tableStyle, $testmeth)) {
            $outval = $tableStyle->{$testmeth}();
            if (is_numeric($outval)) {
                // size is in twips - divide by 20 to get points (NOTE: for some reason it's 1/8 pt on Word)
                // echo $outval . '-> ' . ((string) ($outval / 8)) . 'pt<br>';
                $style .= ' border-' . lcfirst($dir) . '-width: ' . ((string) ($outval / 8)) . 'pt;';
            }
        }
        return $style;
    }
}
