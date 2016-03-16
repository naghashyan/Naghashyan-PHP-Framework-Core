<?php
/**
 *
 * This class is a template for all authorized user classes.
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2015
 * @package ngs.framework.security.users
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
namespace ngs\framework\security\users {
  abstract class AbstractNgsUser {

    /**
     * Abstract method for set user Id
     * Children of the NgsAbstractUser class should override this method
     *
     * @abstract
     *
     * @return void
     */
    public abstract function setId($id);

    /**
     * Abstract method for get user Id
     * Children of the NgsAbstractUser class should override this method
     *
     * @abstract
     *
     * @return integer|$userId
     */
    public abstract function getId();

    /**
     * Abstract method for validate user,
     * Children of the NgsAbstractUser class should override this method
     *
     * @abstract
     *
     * @return boolean
     */
    public abstract function validate();

    /**
     * Abstract method for getting user LEVEL (type),
     * Children of the NgsAbstractUser class should override this method
     *
     * @return integer|$level
     */
    public abstract function getLevel();

    /**
     * Abstract method for getting userDto,
     * Children of the NgsAbstractUser class should override this method
     *
     * @return Object|$userDto
     */
    public abstract function getUserDto();

  }

}
