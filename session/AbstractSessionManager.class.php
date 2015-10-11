<?php
/**
 * AbstractSessionManager 
 * this class provide abstract function
 * for working with cookies and sessions
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2009-2015
 * @package ngs.framework
 * @version 2.1.0
 * 
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace ngs\framework\session {
	abstract class AbstractSessionManager {

		protected $args;

		/**
		 * Abstract method for get user from cookies, 
		 * Children of the AbstractSessionManager class should override this method
		 * 
		 * @abstract
		 * @access
		 * @param 
		 * @return mixed user Object| $user
		 */
		public abstract function getUser();

		/**
		 * Abstract method for set user into cookies, 
		 * Children of the AbstractSessionManager class should override this method
		 * 
		 * @abstract
		 * @access
		 * @param mixed user Object| $user
		 * @param bool $remember | true
		 * @param bool $useDomain | true
		 * @return 
		 */
		public abstract function setUser($user, $remember = false, $useDomain = true);
		


		/**
		 * Abstract method for delete user from cookies, 
		 * Children of the AbstractSessionManager class should override this method
		 * 
		 * @abstract
		 * @access
		 * @param mixed user Object| $user
		 * @param bool $useDomain | true
		 * @return 
		 */
		public abstract function deleteUser($user, $useDomain = true);
		
		
		/**
		 * Abstract method for delete user from cookies, 
		 * Children of the AbstractSessionManager class should override this method
		 * 
		 * @abstract
		 * @access
		 * @param load|action Object $request
		 * @param Object $user | null
		 * @return boolean
		 */
		public abstract function validateRequest($request, $user = null);

	}

}
