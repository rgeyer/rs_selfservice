<?php
/*
Copyright (c) 2011 Ryan J. Geyer <me@ryangeyer.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SelfService\SmartyViews;

/*
 * All classes/files which consume this should just include the following
 * boilerplate.
 * 
 * $temp_path = str_replace('.phtml', '.tpl', __FILE__);
 * $layout_dir = realpath(__DIR__ . '/../../layouts');
 * include 'SelfService/SmartyViews/SmartyView.php';
 */

$smarty = new SmartyRsSelfService;

foreach($this->getVars() as $key => $val) {
	$smarty->assign($key, $val);
}

$smarty->assign('layout_dir', $layout_dir);

// $temp_path should be set in the view *.phtml file since it has
// access to __FILE__ that can be easily converted from *.phtml
// to *.tpl
$smarty->display($temp_path);