<?php
/**
 * @author Travis Swicegood <development@domain51.com>
 * @package SimpleTest
 * @subpackage UnitTester
 * @version $Id$
 */
class SimpleCollector {
    
    /**
     * Strips off any kind of slash at the end so as to
     * normalise the path
     * @param string $path    Path to normalise.
     */
    function _removeTrailingSlash($path) {
        return preg_replace('|[\\/]$|', '', $path);
    }

    /**
     * Scans the directory and adds what it can.
     * @param object $test    Group test with {@link GroupTest::addTestFile} method.
     * @param string $path    Directory to scan.
     * @see _attemptToAdd()
     */
    function collect(&$test, $path) {
        $path = $this->_removeTrailingSlash($path);
        if ($handle = opendir($path)) {
            while (($entry = readdir($handle)) !== false) {
                if (($entry == '..') || ($entry == '.')) {
                    continue;
                }
                if (is_dir($entry)) {
                    $this->_addFolder($test, $path . '/' . $entry);
                } else {
                    $this->_add($test, $path . '/' . $entry);
                }
            }
            closedir($handle);
        }
    }
    
    /**
     * This method determines what should be done with a given file and adds
     * it via {@link GroupTest::addTestFile()} if necessary.
     *
     * This method should be overriden to provide custom matching criteria, 
     * such as pattern matching, recursive matching, etc.  For an example, see
     * {@link SimplePatternCollector::_attemptToAdd()}.
     *
     * @param object $test      Group test with {@link GroupTest::addTestFile} method.
     * @param string $filename  A filename as generated by {@link collect()}
     * @see collect()
     * @access protected
     */
    function _add(&$test, $file) {
        $test->addTestFile($file);
    }
    
    /**
     * Adds a folder to the tests. Can use this to call the
     * collect() method on the GroupTest to achieve recursion.
     *
     * @param object $test    Group test with {@link GroupTest::addTestFile} method.
     * @param string $folder  A folder as generated by {@link collect()}
     * @access protected
     * @see collect()
     */
    function _addFolder(&$test, $folder) {
    }
}

/**
 * This attempts to collect files at a given location based on a given PCRE 
 * pattern.
 * @package SimpleTest
 * @subpackage UnitTester
 * @see SimpleCollector
 */
class SimplePatternCollector extends SimpleCollector {
    var $_pattern;
        
    /**
     * Ttakes three arguments, the first two of which are the same as
     * {@link SimpleCollector::SimpleCollector()}, the third specifies a
     * pattern that matches 
     * {@link http://us4.php.net/manual/en/reference.pcre.pattern.syntax.php PHP's PCRE}.
     *
     * No verification is done on the pattern, so it is incumbent about the
     * developer to insure it is a proper pattern.
     * Defaults to files ending in ".php"
     *
     * @param string $pattern   Perl compatible regex to test name against
     */
    function SimplePatternCollector($pattern = '/php$/i') {
        $this->_pattern = $pattern;
    }
    
    /**
     * Attempts to add files that match a given pattern.
     *
     * @see SimpleCollector::_attemptToAdd()
     * @param object $test    Group test with {@link GroupTest::addTestFile} method.
     * @param string $path    Directory to scan.
     * @access protected
     */
    function _add(&$test, $filename) {
        if (preg_match($this->_pattern, $filename)) {
            parent::_add($test, $filename);
        }
    }
}
?>