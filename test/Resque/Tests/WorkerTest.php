<?php
namespace Resque\Tests;
require_once dirname(__FILE__) . '/bootstrap.php';

/**
 * \Resque\Worker tests.
 *
 * @package		Resque/Tests
 * @author		William POTTIER <wpottier@allprogrammic.com>
 * @copyright	(c) 2012 William POTTIER
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class WorkerTest extends TestCase
{
	public function testWorkerRegistersInList()
	{
		$worker = new \Resque\Worker('*');
		$worker->registerWorker();

		// Make sure the worker is in the list
		$this->assertTrue((bool)$this->redis->sismember('resque:workers', (string)$worker));
	}

	public function testGetAllWorkers()
	{
		$num = 3;
		// Register a few workers
		for($i = 0; $i < $num; ++$i) {
			$worker = new \Resque\Worker('queue_' . $i);
			$worker->registerWorker();
		}

		// Now try to get them
		$this->assertEquals($num, count(\Resque\Worker::all()));
	}

	public function testGetWorkerById()
	{
		$worker = new \Resque\Worker('*');
		$worker->registerWorker();

		$newWorker = \Resque\Worker::find((string)$worker);
		$this->assertEquals((string)$worker, (string)$newWorker);
	}

	public function testInvalidWorkerDoesNotExist()
	{
		$this->assertFalse(\Resque\Worker::exists('blah'));
	}

	public function testWorkerCanUnregister()
	{
		$worker = new \Resque\Worker('*');
		$worker->registerWorker();
		$worker->unregisterWorker();

		$this->assertFalse(\Resque\Worker::exists((string)$worker));
		$this->assertEquals(array(), \Resque\Worker::all());
		$this->assertEquals(array(), $this->redis->smembers('resque:workers'));
	}

	public function testPausedWorkerDoesNotPickUpJobs()
	{
		$worker = new \Resque\Worker('*');
		$worker->pauseProcessing();
		\Resque\Resque::enqueue('jobs', 'Test_Job');
		$worker->work(0);
		$worker->work(0);
		$this->assertEquals(0, \Resque\Stat::get('processed'));
	}

	public function testResumedWorkerPicksUpJobs()
	{
		$worker = new \Resque\Worker('*');
		$worker->pauseProcessing();
		\Resque\Resque::enqueue('jobs', 'Test_Job');
		$worker->work(0);
		$this->assertEquals(0, \Resque\Stat::get('processed'));
		$worker->unPauseProcessing();
		$worker->work(0);
		$this->assertEquals(1, \Resque\Stat::get('processed'));
	}

	public function testWorkerCanWorkOverMultipleQueues()
	{
		$worker = new \Resque\Worker(array(
			'queue1',
			'queue2'
		));
		$worker->registerWorker();
		\Resque\Resque::enqueue('queue1', 'Test_Job_1');
		\Resque\Resque::enqueue('queue2', 'Test_Job_2');

		$job = $worker->reserve();
		$this->assertEquals('queue1', $job->queue);

		$job = $worker->reserve();
		$this->assertEquals('queue2', $job->queue);
	}

	public function testWorkerWorksQueuesInSpecifiedOrder()
	{
		$worker = new \Resque\Worker(array(
			'high',
			'medium',
			'low'
		));
		$worker->registerWorker();

		// Queue the jobs in a different order
		\Resque\Resque::enqueue('low', 'Test_Job_1');
		\Resque\Resque::enqueue('high', 'Test_Job_2');
		\Resque\Resque::enqueue('medium', 'Test_Job_3');

		// Now check we get the jobs back in the right order
		$job = $worker->reserve();
		$this->assertEquals('high', $job->queue);

		$job = $worker->reserve();
		$this->assertEquals('medium', $job->queue);

		$job = $worker->reserve();
		$this->assertEquals('low', $job->queue);
	}

	public function testWildcardQueueWorkerWorksAllQueues()
	{
		$worker = new \Resque\Worker('*');
		$worker->registerWorker();

		\Resque\Resque::enqueue('queue1', 'Test_Job_1');
		\Resque\Resque::enqueue('queue2', 'Test_Job_2');

		$job = $worker->reserve();
		$this->assertEquals('queue1', $job->queue);

		$job = $worker->reserve();
		$this->assertEquals('queue2', $job->queue);
	}

	public function testWorkerDoesNotWorkOnUnknownQueues()
	{
		$worker = new \Resque\Worker('queue1');
		$worker->registerWorker();
		\Resque\Resque::enqueue('queue2', 'Test_Job');

		$this->assertFalse($worker->reserve());
	}

	public function testWorkerClearsItsStatusWhenNotWorking()
	{
		\Resque\Resque::enqueue('jobs', 'Test_Job');
		$worker = new \Resque\Worker('jobs');
		$job = $worker->reserve();
		$worker->workingOn($job);
		$worker->doneWorking();
		$this->assertEquals(array(), $worker->job());
	}

	public function testWorkerRecordsWhatItIsWorkingOn()
	{
		$worker = new \Resque\Worker('jobs');
		$worker->registerWorker();

		$payload = array(
			'class' => 'Test_Job'
		);
		$job = new \Resque\Job('jobs', $payload);
		$worker->workingOn($job);

		$job = $worker->job();
		$this->assertEquals('jobs', $job['queue']);
		if(!isset($job['run_at'])) {
			$this->fail('Job does not have run_at time');
		}
		$this->assertEquals($payload, $job['payload']);
	}

	public function testWorkerErasesItsStatsWhenShutdown()
	{
		\Resque\Resque::enqueue('jobs', 'Test_Job');
		\Resque\Resque::enqueue('jobs', 'Invalid_Job');

		$worker = new \Resque\Worker('jobs');
		$worker->work(0);
		$worker->work(0);

		$this->assertEquals(0, $worker->getStat('processed'));
		$this->assertEquals(0, $worker->getStat('failed'));
	}

	public function testWorkerCleansUpDeadWorkersOnStartup()
	{
		// Register a good worker
		$goodWorker = new \Resque\Worker('jobs');
		$goodWorker->registerWorker();
		$workerId = explode(':', $goodWorker);

		// Register some bad workers
		$worker = new \Resque\Worker('jobs');
		$worker->setId($workerId[0].':1:jobs');
		$worker->registerWorker();

		$worker = new \Resque\Worker(array('high', 'low'));
		$worker->setId($workerId[0].':2:high,low');
		$worker->registerWorker();

		$this->assertEquals(3, count(\Resque\Worker::all()));

		$goodWorker->pruneDeadWorkers();

		// There should only be $goodWorker left now
		$this->assertEquals(1, count(\Resque\Worker::all()));
	}

	public function testDeadWorkerCleanUpDoesNotCleanUnknownWorkers()
	{
		// Register a bad worker on this machine
		$worker = new \Resque\Worker('jobs');
		$workerId = explode(':', $worker);
		$worker->setId($workerId[0].':1:jobs');
		$worker->registerWorker();

		// Register some other false workers
		$worker = new \Resque\Worker('jobs');
		$worker->setId('my.other.host:1:jobs');
		$worker->registerWorker();

		$this->assertEquals(2, count(\Resque\Worker::all()));

		$worker->pruneDeadWorkers();

		// my.other.host should be left
		$workers = \Resque\Worker::all();
		$this->assertEquals(1, count($workers));
		$this->assertEquals((string)$worker, (string)$workers[0]);
	}

	public function testWorkerFailsUncompletedJobsOnExit()
	{
		$worker = new \Resque\Worker('jobs');
		$worker->registerWorker();

		$payload = array(
			'class' => 'Test_Job'
		);
		$job = new \Resque\Job('jobs', $payload);

		$worker->workingOn($job);
		$worker->unregisterWorker();

		$this->assertEquals(1, \Resque\Stat::get('failed'));
	}
}