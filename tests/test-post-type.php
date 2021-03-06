<?php

class LogBook_Posttype_Test extends WP_UnitTestCase
{
	public function test_logbook_post_type_should_exist()
	{
		$this->assertTrue( in_array( 'logbook', get_post_types() ) );
	}

	public function test_logbook_capabilities()
	{
		$post_type_object = get_post_type_object( 'logbook' );

		$this->set_current_user( 'administrator' );
		$this->assertTrue( current_user_can( $post_type_object->cap->edit_posts ) );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->create_posts ) );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->delete_posts ) );

		$this->set_current_user( 'editor' );
		$this->assertTrue( current_user_can( $post_type_object->cap->edit_posts ) );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->create_posts ) );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->delete_posts ) );

		$this->set_current_user( 'author' );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->edit_posts ) );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->create_posts ) );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->delete_posts ) );

		$this->set_current_user( 'contributor' );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->edit_posts ) );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->create_posts ) );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->delete_posts ) );

		$this->set_current_user( 'subscriber' );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->edit_posts ) );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->create_posts ) );
		$this->assertTrue( ! current_user_can( $post_type_object->cap->delete_posts ) );
	}

	/**
	 * Add user and set the user as current user.
	 *
	 * @param  string $role administrator, editor, author, contributor ...
	 * @return int The user ID
	 */
	private function set_current_user( $role )
	{
		$user = $this->factory()->user->create_and_get( array(
			'role' => $role,
		) );

		wp_set_current_user( $user->ID, $user->user_login );

		return $user->ID;
	}
}
