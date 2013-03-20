<?php
/*
 Copyright (c) 2011-2013 Ryan J. Geyer <me@ryangeyer.com>

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

namespace SelfService\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users",indexes={@ORM\index(name="oid_url", columns={"oid_url"})})
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class User
{
	/**
	 * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
	 * @var integer
	 */
	public $id;
	
	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	public $name;
	
	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	public $email;

	/**
	 * @ORM\Column(type="string", unique=true)
	 * @var string
	 */
	public $oid_url;
	
	/******************* Everything after this is deprecated, from before I was using Google for Auth ******************/
	
	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	protected $password;
	
	/**
	 * @ORM\Column(length=32, nullable=true)
	 * @var string
	 */
	protected $salt;
	
	/**
	 * The string value of the password, this is immediately converted to a hash
	 * using sha1()
	 * @param string $password
	 */
	public function setPassword($password) {
		$this->password = $this->hashPassword($password);
	}
	
	/**
	 * @return The sha1() hashed password
	 */
	public function getPassword() {
		return $this->password;
	}
	
	/**
	 * Compares the supplied password with the stored one, returning a boolean
	 * indicating if they match or not.
	 * 
	 * @param string $password The password to compare against the stored password
	 * 
	 * @return boolean 
	 */
	public function authenticatePassword($password) {
		return $this->password == $this->hashPassword($password);
	}
	
	/**
	 * Hashes a password with a salt of 32 characters
	 * 
	 * @param string $password The plaintext password to hash
	 * 
	 * @return string The hashed password
	 */
	private function hashPassword($password) {
		if($this->salt === null) {
			$this->salt = substr(sha1(uniqid(rand(), true)), 0, 32);
		}
		return sha1($this->salt . $password);
	}
}