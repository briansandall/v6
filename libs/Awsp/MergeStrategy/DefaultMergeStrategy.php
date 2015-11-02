<?php
/**
 * Default merging strategy uses a simple vertical stacking algorithm that can result in
 * a lot of wasted space depending on the dimensions and quantity of the packed items.
 *
 * @package Awsp MergeStrategy Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 09/25/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\MergeStrategy;

use \Awsp\Ship\Package as Package;

class DefaultMergeStrategy implements IMergeStrategy
{
    /**
     * @Override
     */
    public function merge(Package $packageA, Package $packageB, &$error = '') {
        $l = max($packageA->get('length'), $packageB->get('length'));
        $w = max($packageA->get('width'), $packageB->get('width'));
        $h = $packageA->get('height') + $packageB->get('height');
        $weight = $packageA->get('weight') + $packageB->get('weight');
        $combined = new Package($weight, array($l, $w, $h), $packageA->get('options'));
        // Don't forget to merge the package options into the combined package
        if (!$combined->mergeOptions($packageB, $error)) {
            return false;
        }
        return $combined;
    }
}
