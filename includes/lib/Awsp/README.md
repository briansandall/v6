Adapted from Alex Fraundorf's project at https://bitbucket.org/alexfraundorf_com/awsp-ship

Purpose:

The original example code by Alex Fraundorf assumes that the packages are already
known, but for many, especially E-Commerce sites, this is likely not the case.

The purpose of this adaptation, therefore, is to provide a means to pack products,
specifically so that any packing algorithm may be used, and that any algorithm may
be easily swapped for any other.

A default packing implementation is provided that packs all items individually,
subject to the shipper's size and weight requirements. More complex algorithms that
fit multiple products per box, favor packages below the 'large package' threshold,
expect data Objects instead of arrays for the items to pack, etc., once developed,
can be used by changing as little as one line of code.

It is my hope that developers using this code will contribute packing algorithms,
constraints, vendor-specific implementations, etc. back to this repository so that
it eventually contains many useful tools for anyone implementing an ecommerce site.

WHY USE AWSP SHIPPING?

- Do you ship products in boxes larger than a few inches on each side?

- Are you unable to process shipping for orders with multiple items due to weight limitations?

- Have you consistently been losing money on shipping?

- Tired of explaining to customers why their final cost is higher than quoted?

If any of those sound familiar, consider switching to an AWSP Shipping module.

Advantages:

- Provides more realistic shipping quotes so you don't lose money

- Able to provide quotes for orders whose weight exceeds the shipper's limits*

- Same response times as standard shipping modules

- Just as simple to setup as standard shipping modules, but far more customizable

- Highly customizable (if you are or have a developer):

	> Add improved packing algorithms for even more accurate quotes

	> Add constraints to model how your items are really packed

( * Assuming that the items are at least able to be shipped individually )

Most standard shipping modules are designed for stores that sell products whose
shipping costs do not significantly vary (e.g. flat rates) or those for whom the
product weight is the major factor in shipping cost (e.g. weight-based shipping).

However, many shipping companies have begun charging based on both weight AND
container dimensions, in which case most simple weight-based algorithms result
in what appear to be lower shipping costs - at least until you actually ship the
product, at which time you will be responsible for either asking the customer
for more money, or making up the difference yourself.

The difference between the weight-based quote and actual shipping costs can be
quite significant, even for a product that has exactly the same weight.

Take the following examples, based on real rates from UPS:

      L x  W x  H  Weight  (Billed)  Standard   Awsp   You Lose

      6"   6"   6"  5 lbs  ( 5 lbs)   $18.13   $18.13     $0.00

     10"  10"  10"  5 lbs  ( 7 lbs)   $18.13   $19.32     $1.19

     16"  12"   8"  5 lbs  (10 lbs)   $18.13   $23.12     $4.99

     16"  16"  16"  5 lbs  (25 lbs)   $18.13   $47.66    $29.53

     24"  24"  24"  5 lbs  (32 lbs)   $18.13   $58.08    $39.95

    108"  16"  12"  5 lbs (125 lbs)   $18.13  $208.30   $190.17

Note that 108" is the maximum shippable length of a package with UPS, and the
total of length + 2 x (width + height) cannot exceed 130 inches, so the last
package on the list is the largest possible for that length.

Clearly 5 lbs. is not always equal, yet standard shipping modules will quote the
customer the same price in every single case. If your products ship in containers
any larger than a small box, you could be losing serious amounts of money: as much
as $200 or more on a single package in the most extreme circumstances, and anywhere
from $5 to $40 PER PACKAGE under more typical conditions.

For orders containing larger quantities of a variety of items, the AWSP algorithms
err on the side of caution, resulting in quotes that may be slightly higher than
observed once the items are actually packed and shipped. If you have the expertise
to write a more sophisticated packing algorithm, however, it is simple to swap it in
to the module and use it instead.

In the meantime, what would you rather tell your customer:

"Shipping was less than expected, so we're refunding you the difference"

OR

"Shipping was more than expected; we can't ship your order until you give us more money"

Copyright (c) 2015 Brian Sandall
Original license and disclaimer(s) apply (see below).

-------------------------------------------------------------------------------------------------------
Original Readme
-------------------------------------------------------------------------------------------------------
-- Stay tuned.  A new and improved version of this module is coming soon!





Copyright (c) 2012-2013 Alex Fraundorf and AffordableWebSitePublishing.com LLC

This readme file was updated on 04/24/2013.

This package was written for the article published on PHPmaster.com (http://phpmaster.com/abstracting-shipping-apis/).
Please see the article for a detailed explanation of the package and how to use it.

-------------------------------------------------------------------------------------------------------

NOTICE: This is beta software.  Although it has been tested, there may be bugs and there is plenty of 
room for improvement.  Use at your own risk.

If you need help integrating this software or you would like a commercially viable version of it, the 
author of it is available for hire!  Contact Alex Fraundorf via www.AlexFraundorf.com.

-------------------------------------------------------------------------------------------------------

System Requirements:

PHP 5.3 or later
SoapClient (for UPS plugin) - should be included in your PHP distribution by default

-------------------------------------------------------------------------------------------------------

UPS notes:

In order to use this software you will need several things from UPS (United Parcel Service).
1. A valid UPS shipper number.
2. An online account at www.ups.com (you will need a valid shipper number first).  Make sure you choose a username 
and password you are comfortable keeping for a while.  You will need to use both with every API call.
3. Sign up for API access at www.ups.com/upsdeveloperkit.
Once approved, you will receive an API key that you will need to use for every API call.  Note: At 
the time of this writing, there is a known issue with this section of UPS's site and Chrome will return 
a blank page.  You will need to use a different browser.

Once you have obtained these items, enter them in the includes/config.php file.


-------------------------------------------------------------------------------------------------------

The MIT License - http://www.opensource.org/licenses/mit-license.php

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and 
associated documentation files (the "Software"), to deal in the Software without restriction, 
including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, 
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial 
portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT 
NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. 
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
