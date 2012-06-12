<?php
// Somewhere in our application, we need to register:
\Resque\Event::listen('afterEnqueue', array('\MyResquePlugin', 'afterEnqueue'));
\Resque\Event::listen('beforeFirstFork', array('\MyResquePlugin', 'beforeFirstFork'));
\Resque\Event::listen('beforeFork', array('\MyResquePlugin', 'beforeFork'));
\Resque\Event::listen('afterFork', array('\MyResquePlugin', 'afterFork'));
\Resque\Event::listen('beforePerform', array('\MyResquePlugin', 'beforePerform'));
\Resque\Event::listen('afterPerform', array('\MyResquePlugin', 'afterPerform'));
\Resque\Event::listen('onFailure', array('\MyResquePlugin', 'onFailure'));

class MyResquePlugin
{
	public static function afterEnqueue($class, $arguments)
	{
		echo "Job was queued for " . $class . ". Arguments:";
		print_r($arguments);
	}
	
	public static function beforeFirstFork($worker)
	{
		echo "Worker started. Listening on queues: " . implode(', ', $worker->queues(false)) . "\n";
	}
	
	public static function beforeFork($job)
	{
		echo "Just about to fork to run " . $job;
	}
	
	public static function afterFork($job)
	{
		echo "Forked to run " . $job . ". This is the child process.\n";
	}
	
	public static function beforePerform($job)
	{
		echo "Cancelling " . $job . "\n";
	//	throw new Resque_Job_DontPerform;
	}
	
	public static function afterPerform($job)
	{
		echo "Just performed " . $job . "\n";
	}
	
	public static function onFailure($exception, $job)
	{
		echo $job . " threw an exception:\n" . $exception;
	}
}