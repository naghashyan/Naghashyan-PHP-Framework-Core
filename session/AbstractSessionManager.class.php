<?php
/**
 * AbstractSessionManager 
 * this class provide abstract function
 * for users manipulates
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2009-2015
 * @package ngs.framework
 * @version 2.2.0
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

		/**
		 * Abstract method for get user
		 * Children of the AbstractSessionManager class should override this method
		 * 
		 * @abstract
		 * @return mixed user Object| $user
		 */
		public abstract function getUser();

		/**
		 * Abstract method for set user,
		 * Children of the AbstractSessionManager class should override this method
		 * 
		 * @abstract
		 * @access
		 * @param mixed user Object| $user
		 * @return 
		 */
		public abstract function setUser($user);
		


		/**
		 * Abstract method for delete user,
		 * Children of the AbstractSessionManager class should override this method
		 * 
		 * @abstract
		 * @param mixed user Object| $user
		 * @return
		 */
		public abstract function deleteUser($user);
		
		
		/**
		 * Abstract method for validate request,
		 * Children of the AbstractSessionManager class should override this method
		 * 
		 * @abstract
		 * @access
		 * @param load|action Object $request
		 * @return boolean
		 */
		public abstract function validateRequest($request);

	}

}
