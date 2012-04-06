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

namespace SelfService;

class Cryptographer
{
	public static $key = null;
	
	public static function getRandomKey() {
		$length = mcrypt_get_key_size('rijndael-128', 'ecb');
		$max = ceil($length / 40);
		$random = '';
		for ($i = 0; $i < $max; $i ++) {
			$random .= sha1(microtime(true).mt_rand(10000,90000));
		}
		return substr($random, 0, $length);
	}
		
	public static function encrypt($value, $key=null)
	{
		$key = ($key != null) ? $key : self::getKey();
		$iv_size = \mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		$iv = \mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$encryptedVal = \mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $value, MCRYPT_MODE_ECB, $iv);
		return trim(base64_encode($encryptedVal));
	}
	
	public static function decrypt($value, $key=null)
	{		
		$key = ($key != null) ? $key : self::getKey();
		$iv_size = \mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		$iv = \mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decryptedVal = \mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($value), MCRYPT_MODE_ECB, $iv);
		return trim($decryptedVal);
	}

	public static function getKey() {
		if(self::$key === null) {
			self::$key = self::getRandomKey();
		}
		return self::$key;
	}
}
