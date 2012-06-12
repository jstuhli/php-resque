<?php
namespace Resque\Job;

/**
 * Interface for Job instance
 *
 * @package		Resque/Job
 * @author		William POTTIER <wpottier@allprogrammic.com>
 * @copyright	(c) 2012 William POTTIER
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
abstract class AbstractInstance {

    protected $args;
    protected $job;
    protected $queue;

    abstract public function perform();

    public function setArgs($args)
    {
        $this->args = $args;
    }

    public function setJob($job)
    {
        $this->job = $job;
    }

    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    public function tearDown() {}
    public function setUp() {}
}