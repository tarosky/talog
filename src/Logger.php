<?php

namespace Talog;

class Logger
{
	const post_type = 'talog';

	public function __construct()
	{
		// Nothing to do for now.
	}

	/**
	 * Registers the logger to the specific hooks.
	 *
	 * @param string|array $hooks      An array of hooks to save log.
	 * @param callable $log     The callback function to return log message.
	 * @param callable $message The callback function to return long message of the log.
	 * @param string $log_level The Log level.
	 * @param int    $priority  An int value passed to `add_action()`.
	 * @param int    $accepted_args An int value passed to `add_action()`.
	 */
	public function watch( $hooks, $log, $message = null, $log_level = null, $priority = 10, $accepted_args = 1 )
	{
		$log_level = Log_Level::get_level( $log_level );

		if ( ! is_array( $hooks ) ) {
			$hooks = array( $hooks );
		}

		foreach ( $hooks as $hook ) {
			add_filter( $hook, function() use ( $log, $message, $log_level ) {
				/**
				 * Filters the log levels array to save logs.
				 *
				 * @param array $log_levels An array of the log levels.
				 * @reurn array
				 */
				$log_levels = apply_filters( 'talog_log_levels', Log_Level::get_all_levels() );
				if ( ! in_array( $log_level, $log_levels ) ) {
					return;
				}

				$args = func_get_args();
				if ( 'save_post' === current_filter() && 'talog' === get_post_type( $args[0] ) ) {
					return false; // To prevent infinite loop.
				}

				$this->save( $log, $message, $log_level, $args );

				if ( ! empty( $args[0] ) ) {
					return $args[0];
				}
			}, $priority, $accepted_args );
		}
	}

	/**
	 * Callback function to save log.
	 *
	 * @param callable $log     The callback function to return log message.
	 * @param callable $message The callback function to return long message of the log.
	 * @param string $log_level The log level.
	 * @param array $additional_args An array which is passed from the callback function of the hook.
	 *
	 * @return int|\WP_Error
	 */
	public function save( $log, $message = null, $log_level = null, $additional_args = array() )
	{
		$log_level = Log_Level::get_level( $log_level );

		if ( defined('WP_CLI') && WP_CLI ) {
			$is_cli = true;
		} else {
			$is_cli = false;
		}

		$user = $this->get_user();
		if ( empty( $user->ID ) ) {
			$user_id = 0;
		} else {
			$user_id = $user->ID;
		}

		$last_error = $this->error_get_last();
		$current_hook = $this->get_current_hook();

		$log_text = '';
		if ( ! empty( $log ) && is_callable( $log ) ) {
			$log_text = call_user_func( $log, array(
				'log_level' => $log_level,
				'last_error' => $last_error,
				'current_hook' => $current_hook,
				'user' => $user,
				'is_cli' => $is_cli,
				'additional_args' => $additional_args,
			) );
		}

		if ( empty( $log_text ) ) {
			return 0;
		}

		$message_text = '';
		if ( ! empty( $message ) && is_callable( $message ) ) {
			$message_text = call_user_func( $message, array(
				'log_level' => $log_level,
				'last_error' => $last_error,
				'current_hook' => $current_hook,
				'user' => $user,
				'is_cli' => $is_cli,
				'additional_args' => $additional_args,
			) );
		}

		$post_id = wp_insert_post( array(
			'post_type' => self::post_type,
			'post_title' => $log_text,
			'post_content' => $message_text,
			'post_status' => 'publish',
			'post_author' => intval( $user_id )
		) );

		// Followings will be used for `orderby` for query.
		update_post_meta( $post_id, '_talog_log_level', $log_level );
		update_post_meta( $post_id, '_talog_hook', $current_hook );

		update_post_meta( $post_id, '_talog', array(
			'log_level' => $log_level,
			'last_error' => $last_error,
			'hook' => $current_hook,
			'is_cli' => $is_cli,
			'server_vars' => $_SERVER,
		) );

		do_action( 'talog_after_save_log', $post_id );

		return $post_id;
	}

	protected function get_current_hook()
	{
		return current_filter();
	}

	protected function get_user()
	{
		return wp_get_current_user();
	}

	protected function error_get_last()
	{
		return error_get_last();
	}
}
