<?php
namespace Resque\Tests;

require_once dirname(__FILE__) . '/bootstrap.php';

/**
 * \Resque\Stat tests.
 *
 * @package		Resque/Tests
 * @author		William POTTIER <wpottier@allprogrammic.com>
 * @copyright	(c) 2012 William POTTIER
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class StatTest extends TestCase
{
	public function testStatCanBeIncremented()
	{
		\Resque\Stat::incr('test_incr');
		\Resque\Stat::incr('test_incr');
		$this->assertEquals(2, $this->redis->get('resque:stat:test_incr'));
	}

	public function testStatCanBeIncrementedByX()
	{
		\Resque\Stat::incr('test_incrX', 10);
		\Resque\Stat::incr('test_incrX', 11);
		$this->assertEquals(21, $this->redis->get('resque:stat:test_incrX'));
	}

	public function testStatCanBeDecremented()
	{
		\Resque\Stat::incr('test_decr', 22);
		\Resque\Stat::decr('test_decr');
		$this->assertEquals(21, $this->redis->get('resque:stat:test_decr'));
	}

	public function testStatCanBeDecrementedByX()
	{
		\Resque\Stat::incr('test_decrX', 22);
		\Resque\Stat::decr('test_decrX', 11);
		$this->assertEquals(11, $this->redis->get('resque:stat:test_decrX'));
	}

	public function testGetStatByName()
	{
		\Resque\Stat::incr('test_get', 100);
		$this->assertEquals(100, \Resque\Stat::get('test_get'));
	}

	public function testGetUnknownStatReturns0()
	{
		$this->assertEquals(0, \Resque\Stat::get('test_get_unknown'));
	}
}