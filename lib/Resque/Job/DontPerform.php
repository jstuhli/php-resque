<?php
namespace Resque\Job;

/**
 * Exception to be thrown if a job should not be performed/run.
 *
 * @package		Resque/Job
 * @author		William POTTIER <wpottier@allprogrammic.com>
 * @copyright	(c) 2012 William POTTIER
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class DontPerform extends \Exception
{

}