<?php
namespace Resque\Event;

/**
 * Resque job instance creation event
 *
 * @package		Resque/Event
 * @author		William POTTIER <wpottier@allprogrammic.com>
 * @copyright	(c) 2012 William POTTIER
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class CreateInstance
{
    /**
     * @var \Resque\Job The job that triggered the event
     */
    protected $job;

    /**
     * @var object Instance of the object that $this->job belongs to
     */
    protected $instance;

    /**
     * Instantiate a new instance of the event
     *
     * @param \Resque\Job $job The job that triggered the event
     */
    public function __construct($job)
    {
        $this->job = $job;
    }

    /**
     * Get the \Resque\Job instance that triggered the event.
     *
     * @return \Resque\Job Instance of the job that triggered the event.
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Set the instantiated object for $this->job that will be performing work.
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
    }

    /**
     * Get the instantiated object for $this->job that will be performing work, or null
     *
     * @return object Instance of the object that $this->job belongs to
     */
    public function getInstance()
    {
        return $this->instance ? $this->instance : null;
    }
}
?>