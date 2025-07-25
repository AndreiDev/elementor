<?php
namespace Elementor\Testing\Includes;

use Elementor\Core\Files\Uploads_Manager;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use ElementorEditorTesting\Elementor_Test_Base;
use Elementor\Tracker;
use Elementor\Utils;

class Test_Tracker extends Elementor_Test_Base {
	public function test_get_settings_general_usage() {
		// Arrange.
		update_option( 'elementor_cpt_support', [ 'page' ] );
		update_option( 'elementor_disable_color_schemes', true );
		update_option( 'elementor_disable_typography_schemes', '' );

		// Act.
		$actual = Tracker::get_settings_general_usage();

		// Assert.
		$this->assertEqualSets( [
			'cpt_support' => [
				'page',
			],
			'disable_color_schemes' => true,
			'disable_typography_schemes' => false,
			'allow_tracking' => 'yes', // TODO: Probably should be excluded, but settings page should not know about tracker existence.
		], $actual );
	}

	public function test_get_settings_advanced_usage() {
		// Arrange.
		Plugin::$instance->icons_manager->register_admin_settings( Plugin::$instance->settings );

		update_option( Utils::EDITOR_BREAK_LINES_OPTION_KEY, '' );
		update_option( Uploads_Manager::UNFILTERED_FILE_UPLOADS_KEY, '1' );
		update_option( 'elementor_google_font', '1' );
		update_option( 'elementor_font_display', 'block' );
		update_option( 'elementor_meta_generator_tag', '1' );
		update_option( Icons_Manager::LOAD_FA4_SHIM_OPTION_KEY, 'yes' );

		// Act.
		$actual = Tracker::get_settings_advanced_usage();

		// Assert.
		$this->assertEqualSets( [
			'switch_editor_loader_method' => '',
			'enable_unfiltered_file_uploads' => '1',
			'google_font' => '1',
			'font_display' => 'block',
			'font_awesome_support' => 'yes',
			'meta_generator_tag' => '1',
		], $actual );
	}

	public function test_get_settings_performance_usage() {
		// Arrange.
		Plugin::$instance->icons_manager->register_admin_settings( Plugin::$instance->settings );

		update_option( 'elementor_css_print_method', 'internal' );
		update_option( 'elementor_optimized_image_loading', '1' );
		update_option( 'elementor_optimized_gutenberg_loading', '1' );
		update_option( 'elementor_lazy_load_background_images', '1' );

		// Act.
		$actual = Tracker::get_settings_performance_usage();

		// Assert.
		$this->assertEqualSets( [
			'css_print_method' => 'internal',
			'optimized_image_loading' => '1',
			'optimized_gutenberg_loading' => '1',
			'lazy_load_background_images' => '1',
		], $actual );
	}

	public function test_get_tools_general_usage() {
		// Arrange.
		// Load elementor_safe_mode settings.
		Plugin::$instance->modules_manager->get_modules( 'safe-mode' )->add_admin_button(
			Plugin::$instance->tools
		);

		update_option( 'elementor_safe_mode', 'global' );
		update_option( 'elementor_enable_inspector', 'enable' );

		// Act.
		$actual = Tracker::get_tools_general_usage();

		// Assert.
		$this->assertEqualSets( [
			'safe_mode' => 'global',
			'debug_bar' => 'enable',
		], $actual );
	}

	public function test_get_tools_version_control_usage() {
		// Arrange.
		update_option( 'elementor_beta', 'yes' );

		// Act.
		$actual = Tracker::get_tools_version_control_usage();

		// Assert.
		$this->assertEqualSets( [
			'beta_tester' => 'yes',
		], $actual );
	}

	public function test_get_tools_maintenance_usage() {
		// Arrange.
		Plugin::$instance->maintenance_mode->register_settings_fields( Plugin::$instance->tools );

		update_option( 'elementor_maintenance_mode_mode', 'coming_soon' );
		update_option( 'elementor_maintenance_mode_exclude_mode', 'logged_in' );
		update_option( 'elementor_maintenance_mode_exclude_roles', 'admin' );
		update_option( 'elementor_maintenance_mode_template_id', '1' );

		// Act
		$actual = Tracker::get_tools_maintenance_usage();

		// Assert.
		$this->assertEqualSets( [
			'maintenance_mode_mode' => 'coming_soon',
			'maintenance_mode_exclude_mode' => 'logged_in',
			'maintenance_mode_exclude_roles' => 'admin',
			'maintenance_mode_template_id' => '1',
		], $actual );
	}

	public function test_get_library_usage_extend() {
		// Arrange.
		$post_types = [ 'section', 'page' ];
		$post_statuses = [ 'draft', 'private', 'publish' ];
		$posts_per_status = 2;

		foreach ( $post_types as $post_type ) {
			foreach ( $post_statuses as $post_status ) {
				for ( $i = 0; $i < $posts_per_status; ++$i ) {
					$template = $this->factory()->documents->create_and_get_template( $post_type );

					$this->factory()->documents->update_object( $template->get_id(), [ 'post_status' => $post_status ] );
				}
			}
		}

		// Act.
		$library_usage = Tracker::get_library_usage_extend();

		// Assert.
		foreach ( $post_types as $post_type ) {
			foreach ( $post_statuses as $post_status ) {
				$this->assertEquals( $posts_per_status, $library_usage[ $post_type ][ $post_status ] );
			}
		}
	}

	public function test_has_terms_changed_when_tracking_not_allowed() {
		// Arrange
		update_option( 'elementor_allow_tracking', 'no' );

		// Act
		$result = Tracker::has_terms_changed();

		// Assert
		$this->assertFalse( $result );
	}

	public function test_has_terms_changed_when_last_update_time_not_set() {
		// Arrange
		update_option( 'elementor_allow_tracking', 'yes' );
		delete_option( 'elementor_allow_tracking_last_update' );

		// Act
		$result = Tracker::has_terms_changed();

		// Assert
		$this->assertTrue( $result );
	}

	public function test_has_terms_changed_when_last_update_time_before_terms_update() {
		// Arrange
		update_option( 'elementor_allow_tracking', 'yes' );
		$terms_updated = '2024-01-01';
		$last_update_time = strtotime( '2023-12-31 UTC' );
		update_option( 'elementor_allow_tracking_last_update', $last_update_time );

		// Act
		$result = Tracker::has_terms_changed( $terms_updated );

		// Assert
		$this->assertTrue( $result );
	}

	public function test_has_terms_changed_when_last_update_time_after_terms_update() {
		// Arrange
		update_option( 'elementor_allow_tracking', 'yes' );
		$terms_updated = '2024-01-01';
		$last_update_time = strtotime( '2024-01-02 UTC' );
		update_option( 'elementor_allow_tracking_last_update', $last_update_time );

		// Act
		$result = Tracker::has_terms_changed( $terms_updated );

		// Assert
		$this->assertFalse( $result );
	}
}
