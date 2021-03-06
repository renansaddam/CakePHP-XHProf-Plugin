<?php

/**
 * XHProf
 *
 */
class XHProf {

/**
 * Whether it initiated or not
 *
 * @var bool
 */
	protected static $_initiated = false;

/**
 * Base configuration
 *
 * @var array
 */
	protected static $_baseConfig = array(
		'replaceRunId' => '%XHProfRunId%',
		'namespace' => APP_DIR,
		'library' => null,
		'flags' => 0,
		'ignored_functions' => array(
			'call_user_func',
			'call_user_func_array',
		),
	);

/**
 * Whether profiling has started or not
 *
 * @var bool
 */
	protected static $_started = false;

/**
 * Start xhprof profiler
 *
 * ### Options
 *
 * - `flags`
 * - `ignored_functions`
 *
 * @param array $options List options passed to xhprof_enable, if none default configuration will be used
 * @return void
 */
	public static function start($options = array()) {
		if (!self::$_initiated) {
			self::_initialize();
		}

		// Merge default configuration into provided options
		$options += (array)Configure::read('XHProf');

		// Start profiling
		xhprof_enable($options['flags'], array(
			'ignored_functions' => $options['ignored_functions'],
		));

		// Set as started
		self::$_started = true;
	}

/**
 * Whether profiling has started or not
 *
 * @return bool
 */
	public static function started() {
		return self::$_started;
	}

/**
 * Stop xhprof profiler
 *
 * @return array Profiler data from the run
 */
	public static function stop() {
		// Reset started
		self::$_started = false;

		return xhprof_disable();
	}

/**
 * Stop and save the xhprof profiler run
 *
 * @return string Saved run id
 */
	public static function finish() {
		// Stop profiling
		$data = self::stop();

		// Save the run
		$xhprof = new XHProfRuns_Default();
		$runId = $xhprof->save_run($data, Configure::read('XHProf.namespace'));

		return $runId;
	}

/**
 * Initialize default options and include necessary files
 *
 * @return void
 */
	protected static function _initialize() {
		// Can't profile without xhprof
		if (!extension_loaded('xhprof')) {
			throw new RuntimeException('XHProf extension is not loaded.');
		}

		// Merge base configuration
		$options = (array)Configure::read('XHProf') + self::$_baseConfig;
		Configure::write('XHProf', $options);

		// Include libraries
		if (!class_exists('XHProfRuns_Default')) {
			$path = $options['library'] . DS . 'utils' . DS;
			$files = array(
				$path . 'xhprof_lib.php',
				$path . 'xhprof_runs.php',
			);
			foreach ($files as $file) {
				if (!include $file) {
					throw new RuntimeException(sprintf(
						'Couldn\'t include library file: %s.',
						$file
					));
				}
			}
		}

		// All good to go
		self::$_initiated = true;
	}

}
