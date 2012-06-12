<?php
namespace Resque\Job;

/**
 * Runtime exception class for a job that does not exit cleanly.
 *
 * @package		Resque/Job
 * @author		William POTTIER <wpottier@allprogrammic.com>
 * @copyright	(c) 2012 William POTTIER
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class DirtyExitException extends \RuntimeException
{

}