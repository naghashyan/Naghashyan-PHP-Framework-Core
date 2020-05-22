<?php
/**
 * AbstractSessionManager
 * this class provide abstract function
 * for users manipulates
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2009-2016
 * @package ngs.framework
 * @version 3.1.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\session {

  use ngs\dal\dto\AbstractDto;
  use ngs\request\AbstractAction;

  abstract class AbstractSessionManager {

    /**
     * Abstract method for get user
     * Children of the AbstractSessionManager class should override this method
     *
     * @abstract
     * @return mixed user Object| $user
     */
    abstract public function getUser();

    /**
     * Abstract method for set user,
     * Children of the AbstractSessionManager class should override this method
     *
     * @abstract
     * @access
     * @param mixed user Object| $user
     * @return
     */
    abstract public function setUser(string $user);


    /**
     * Abstract method for delete user,
     * Children of the AbstractSessionManager class should override this method
     *
     * @abstract
     * @param mixed user Object| $user
     * @return
     */
    abstract public function deleteUser();


    /**
     * Abstract method for validate request,
     * Children of the AbstractSessionManager class should override this method
     *
     * @abstract
     * @access
     * @param AbstractDto|AbstractAction Object $request
     * @return boolean
     */
    abstract public function validateRequest($request): bool;

  }

}
