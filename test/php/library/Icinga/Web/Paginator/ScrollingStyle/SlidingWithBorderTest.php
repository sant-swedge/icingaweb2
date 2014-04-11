<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web\Paginator\ScrollingStyle;

// @codingStandardsIgnoreStart
require_once realpath(ICINGA_LIBDIR . '/Icinga/Web/Paginator/ScrollingStyle/SlidingWithBorder.php');
// @codingStandardsIgnoreEnd

use \Zend_Config;
use \Zend_Paginator_Adapter_Interface;
use Icinga\Test\BaseTestCase;
use Icinga\Protocol\Statusdat\Reader;
use Icinga\Web\Paginator\Adapter\QueryAdapter;
use Icinga\Module\Monitoring\Backend;

class TestPaginatorAdapter implements Zend_Paginator_Adapter_Interface
{
    private $items = array();

    public function __construct()
    {
        for ($i=0; $i<1000; $i++) {
            $this->items[] = array(
                'a' => mt_rand(0, 100),
                'b' => mt_rand(0, 100)
            );
        }
    }

    /**
     * Returns an collection of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $out = array_slice($this->items, $offset, $itemCountPerPage, true);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->items);
    }

}

class SlidingwithborderTest extends BaseTestCase
{
    private $cacheDir;

    private $backendConfig;

    private $resourceConfig;

    public function setUp()
    {
        parent::setUp();
        $this->cacheDir = '/tmp'. Reader::STATUSDAT_DEFAULT_CACHE_PATH;

        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir);
        }

        $statusdatFile = BaseTestCase::$testDir . '/res/status/icinga.status.dat';
        $cacheFile = BaseTestCase::$testDir . '/res/status/icinga.objects.cache';

        $this->backendConfig = new Zend_Config(
            array(
                'type' => 'statusdat'
            )
        );
        $this->resourceConfig = new Zend_Config(
            array(
                'status_file'   => $statusdatFile,
                'object_file'  => $cacheFile,
                'type'          => 'statusdat'
            )
        );
    }

    public function testGetPages1()
    {
        $backend = new Backend($this->backendConfig, $this->resourceConfig);
        $query = $backend->select()->from('status');

        $adapter = new QueryAdapter($query);

        $this->assertEquals(30, $adapter->count());

        $scrolingStyle = new \Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder();

        $paginator = new \Zend_Paginator($adapter);

        $pages = $scrolingStyle->getPages($paginator);

        $this->assertInternalType('array', $pages);
        $this->assertCount(3, $pages);
    }

    public function testGetPages2()
    {
        $scrolingStyle = new \Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder();

        $adapter = new TestPaginatorAdapter();

        $paginator = new \Zend_Paginator($adapter);

        $pages = $scrolingStyle->getPages($paginator);

        $this->assertInternalType('array', $pages);

        $this->assertCount(13, $pages);
        $this->assertEquals('...', $pages[11]);
    }

    public function testGetPages3()
    {
        $scrolingStyle = new \Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder();

        $adapter = new TestPaginatorAdapter();

        $paginator = new \Zend_Paginator($adapter);
        $paginator->setCurrentPageNumber(9);

        $pages = $scrolingStyle->getPages($paginator);

        $this->assertInternalType('array', $pages);

        $this->assertCount(16, $pages);
        $this->assertEquals('...', $pages[3]);
        $this->assertEquals('...', $pages[14]);
    }
}
