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
can be used by changing just one line of code.

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
