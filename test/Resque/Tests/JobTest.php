<?php
namespace Resque\Tests;

require_once dirname(__FILE__) . '/bootstrap.php';

/**
 * \Resque\Job tests.
 *
 * @package        Resque/Tests
 * @author        William POTTIER <wpottier@allprogrammic.com>
 * @copyright    (c) 2012 William POTTIER
 * @license        http://www.opensource.org/licenses/mit-license.php
 */
class JobTest extends TestCase {

    protected $worker;

    public function setUp () {
        parent::setUp();

        // Register a worker to test with
        $this->worker = new \Resque\Worker('jobs');
        $this->worker->registerWorker();
    }

    public function testJobCanBeQueued () {
        $this->assertTrue((bool)\Resque\Resque::enqueue('jobs', 'Test_Job'));
    }

    public function testQeueuedJobCanBeReserved () {
        \Resque\Resque::enqueue('jobs', 'Test_Job');

        $job = \Resque\Job::reserve('jobs');
        if ($job == false) {
            $this->fail('Job could not be reserved.');
        }
        $this->assertEquals('jobs', $job->queue);
        $this->assertEquals('Test_Job', $job->payload['class']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testObjectArgumentsCannotBePassedToJob () {
        $args       = new \stdClass;
        $args->test = 'somevalue';
        \Resque\Resque::enqueue('jobs', 'Test_Job', $args);
    }

    public function testQueuedJobReturnsExactSamePassedInArguments () {
        $args = array(
            'int'        => 123,
            'numArray'   => array(
                1,
                2,
            ),
            'assocArray' => array(
                'key1' => 'value1',
                'key2' => 'value2'
            ),
        );
        \Resque\Resque::enqueue('jobs', 'Test_Job', $args);
        $job = \Resque\Job::reserve('jobs');

        $this->assertEquals($args, $job->getArguments());
    }

    public function testAfterJobIsReservedItIsRemoved () {
        \Resque\Resque::enqueue('jobs', 'Test_Job');
        \Resque\Job::reserve('jobs');
        $this->assertFalse(\Resque\Job::reserve('jobs'));
    }

    public function testRecreatedJobMatchesExistingJob () {
        $args = array(
            'int'        => 123,
            'numArray'   => array(
                1,
                2,
            ),
            'assocArray' => array(
                'key1' => 'value1',
                'key2' => 'value2'
            ),
        );

        \Resque\Resque::enqueue('jobs', 'Test_Job', $args);
        $job = \Resque\Job::reserve('jobs');

        // Now recreate it
        $job->recreate();

        $newJob = \Resque\Job::reserve('jobs');
        $this->assertEquals($job->payload['class'], $newJob->payload['class']);
        $this->assertEquals($job->payload['args'], $newJob->getArguments());
    }

    public function testFailedJobExceptionsAreCaught () {
        $payload     = array(
            'class' => 'Failing_Job',
            'args'  => null
        );
        $job         = new \Resque\Job('jobs', $payload);
        $job->worker = $this->worker;

        $this->worker->perform($job);

        $this->assertEquals(1, \Resque\Stat::get('failed'));
        $this->assertEquals(1, \Resque\Stat::get('failed:' . $this->worker));
    }

    /**
     * @expectedException \Resque\Exception
     */
    public function testJobWithoutPerformMethodThrowsException () {
        \Resque\Resque::enqueue('jobs', 'Test_Job_Without_Perform_Method');
        $job         = $this->worker->reserve();
        $job->worker = $this->worker;
        $job->perform();
    }

    /**
     * @expectedException \Resque\Exception
     */
    public function testInvalidJobThrowsException () {
        \Resque\Resque::enqueue('jobs', 'Invalid_Job');
        $job         = $this->worker->reserve();
        $job->worker = $this->worker;
        $job->perform();
    }

    public function testJobWithSetUpCallbackFiresSetUp () {
        $payload = array(
            'class' => 'Test_Job_With_SetUp',
            'args'  => array(
                'somevar',
                'somevar2',
            ),
        );
        $job     = new \Resque\Job('jobs', $payload);
        $job->perform();

        $this->assertTrue(\Test_Job_With_SetUp::$called);
    }

    public function testJobWithTearDownCallbackFiresTearDown () {
        $payload = array(
            'class' => 'Test_Job_With_TearDown',
            'args'  => array(
                'somevar',
                'somevar2',
            ),
        );
        $job     = new \Resque\Job('jobs', $payload);
        $job->perform();

        $this->assertTrue(\Test_Job_With_TearDown::$called);
    }

    public function testJobWithNamespace () {
        \Resque\Redis::prefix('php');
        $queue   = 'jobs';
        $payload = array('another_value');
        \Resque\Resque::enqueue($queue, '\Test_Job_With_TearDown', $payload);

        $this->assertEquals(\Resque\Resque::queues(), array('jobs'));
        $this->assertEquals(\Resque\Resque::size($queue), 1);

        \Resque\Redis::prefix('resque');
        $this->assertEquals(\Resque\Resque::size($queue), 0);
    }

    public function testCreateInstanceEventOverridesDefault () {
        $callback = array(
            $this,
            'createInstanceCallback'
        );
        \Resque\Event::listen('createInstance', $callback);
        $payload  = array(
            'class' => 'Test_Job_With_SetUp',
            'args'  => '',
        );
        $job      = new \Resque\Job('jobs', $payload);
        $instance = $job->getInstance();
        \Resque\Event::stopListening('createInstance', $callback);

        $this->assertInstanceOf('\Test_Job_With_TearDown', $instance);
    }

    public function createInstanceCallback ($event) {
        $event->setInstance(new \Test_Job_With_TearDown());
    }
}