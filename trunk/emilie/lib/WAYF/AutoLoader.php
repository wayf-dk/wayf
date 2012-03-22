<?php
/**
 * JAKOB
 *
 * @category   WAYF
 * @package    JAKOB
 * @subpackage Utilities
 * @author     Jonathan H. Wage <jonwage@gmail.com>
 * @author     Roman S. Borschel <roman@code-factory.org>
 * @author     Matthew Weier O'Phinney <matthew@zend.com>
 * @author     Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author     Fabien Potencier <fabien.potencier@symfony-project.org>
 * @version    $Id: AutoLoader.php 31 2011-08-19 10:52:10Z jach@wayf.dk $
 * @link       $URL: https://jakob.googlecode.com/svn/trunk/lib/WAYF/AutoLoader.php $
 */

/**
 * @namespace
 */
namespace WAYF;

/**
 * SPL Class Loader
 *
 * AutoLoader implementation that implements the technical interoperability
 * standards for PHP 5.3 namespaces and class names.
 * <br /><br /><b>Example:</b><br /><br />
 *
 * <pre>
 * // Example which loads classes from the WAYF package
 * $classLoader = new AutoLoader('WAYF', '/path/to/WAYF');
 * $classLoader->register();
 * </pre>
 *
 * @author   Jonathan H. Wage <jonwage@gmail.com>
 * @author   Roman S. Borschel <roman@code-factory.org>
 * @author   Matthew Weier O'Phinney <matthew@zend.com>
 * @author   Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author   Fabien Potencier <fabien.potencier@symfony-project.org>
 * @link     http://groups.google.com/group/php-standards/web/psr-0-final-proposal
 */
class AutoLoader
{
    /**
     * Extension used for files
     * @var string
     */
    private $_fileExtension = '.php';
    
    /**
     * Namespace loaded
     * @var string
     */
    private $_namespace;
    
    /**
     * Includepath loaded
     * @var string
     */
    private $_includePath;
    
    /**
     * Namespace seperator used
     * @var string
     */
    private $_namespaceSeparator = '\\';

    /**
     * Creates a new <tt>SplClassLoader</tt> that loads classes of the
     * specified namespace.
     * 
     * @param string $ns          The namespace to use.
     * @param string $includePath The include path to use.
     */
    public function __construct($ns = null, $includePath = null)
    {
        $this->_namespace = $ns;
        $this->_includePath = $includePath;
    }

    /**
     * Sets the namespace separator used by classes in the namespace of this class loader.
     * 
     * @param string $sep The separator to use.
     */
    public function setNamespaceSeparator($sep)
    {
        $this->_namespaceSeparator = $sep;
    }

    /**
     * Gets the namespace seperator used by classes in the namespace of this class loader.
     *
     * @return string The namespace
     */
    public function getNamespaceSeparator()
    {
        return $this->_namespaceSeparator;
    }

    /**
     * Sets the base include path for all class files in the namespace of this class loader.
     * 
     * @param string $includePath
     */
    public function setIncludePath($includePath)
    {
        $this->_includePath = $includePath;
    }

    /**
     * Gets the base include path for all class files in the namespace of this class loader.
     *
     * @return string The include path
     */
    public function getIncludePath()
    {
        return $this->_includePath;
    }

    /**
     * Sets the file extension of class files in the namespace of this class loader.
     * 
     * @param string $fileExtension
     */
    public function setFileExtension($fileExtension)
    {
        $this->_fileExtension = $fileExtension;
    }

    /**
     * Gets the file extension of class files in the namespace of this class loader.
     *
     * @return string The file extension usedn
     */
    public function getFileExtension()
    {
        return $this->_fileExtension;
    }

    /**
     * Installs this class loader on the SPL autoload stack.
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Uninstalls this class loader from the SPL autoloader stack.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $className The name of the class to load.
     */
    public function loadClass($className)
    {
        if (null === $this->_namespace || $this->_namespace.$this->_namespaceSeparator === substr($className, 0, strlen($this->_namespace.$this->_namespaceSeparator))) {
            $fileName = '';
            $namespace = '';
            if (false !== ($lastNsPos = strripos($className, $this->_namespaceSeparator))) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName = str_replace($this->_namespaceSeparator, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . $this->_fileExtension;

            require ($this->_includePath !== null ? $this->_includePath . DIRECTORY_SEPARATOR : '') . $fileName;
        }
    }
}
