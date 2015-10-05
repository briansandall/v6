<?php
/**
 * Interface for algorithms to combine two packages into one with no concern for shipping constraints.
 *
 * @package Awsp MergeStrategy Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 09/25/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\MergeStrategy;

interface IMergeStrategy
{

    /**
     * Combine two \Awsp\Ship\Package packages into a single package
     * @param string $error Message describing reason for failure, if any
     * @return The combined \Awsp\Ship\Package on success, or false if they could not be combined
     */
    function merge(\Awsp\Ship\Package $packageA, \Awsp\Ship\Package $packageB, &$error = '');

}
