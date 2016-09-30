<?php

namespace Mercator;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

class REST_API extends WP_REST_Controller {

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( 'mercator/v1', 'mappings', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),

			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		register_rest_route( 'mercator/v1', 'mappings/(?P<id>[\\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'             => array( 'PUT', 'PATCH' ),
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
			),

			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		return $this->get_item_permissions_check( $request );
	}

	/**
	 * Get a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$site  = $this->get_blog_id( $request );
		$items = array_map( function ( $mapping ) use ( $request ) {
			return $this->prepare_item_for_response( $mapping, $request );
		}, Mapping::get_by_site( $site ) );
		return new WP_REST_Response( $items );
	}

	/**
	 * Check if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$user_id = get_current_user_id();

		if ( is_super_admin( $user_id ) ) {
			return true;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$blog_id  = $this->get_blog_id( $request );
		$blog_ids = get_blogs_of_user( $user_id );

		if ( ! in_array( $blog_id, $blog_ids ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		return $this->prepare_item_for_response(
			Mapping::get( $request->get_param( 'id' ) ),
			$request
		);
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		return $this->get_item_permissions_check( $request );
	}

	/**
	 * Create one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$update = $this->prepare_item_for_database( $request );
		return $this->prepare_item_for_response( Mapping::create(
			$this->get_blog_id( $request ),
			$update->domain,
			$update->active
		), $request );
	}

	/**
	 * Check if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		return $this->get_item_permissions_check( $request );
	}

	/**
	 * Update one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$mapping = Mapping::get( $request->get_param( 'id' ) );
		$update  = $this->prepare_item_for_database( $request );
		if ( property_exists( $update, 'domain' ) ) {
			$mapping->set_domain( $update->domain );
		}
		if ( property_exists( $update, 'active' ) ) {
			$mapping->set_active( $update->active );
		}
		return $this->prepare_item_for_response( $mapping, $request );
	}

	/**
	 * Check if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->get_item_permissions_check( $request );
	}

	/**
	 * Delete one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		return new WP_REST_Response( Mapping::get( $request->get_param( 'id' ) )->delete() );
	}

	/**
	 * Prepare the item for create or update operation.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database( $request ) {

		$update = new \stdClass;
		if ( null !== $request->get_param( 'domain' ) ) {
			$update->domain = parse_url( $request->get_param( 'domain' ), PHP_URL_HOST );
		}
		if ( null !== $request->get_param( 'active' ) ) {
			$update->active = filter_var( $request->get_param( 'active' ), FILTER_VALIDATE_BOOLEAN );
		}

		return $update;
	}

	/**
	 * Prepare the item for the REST response.
	 *
	 * @param mixed           $item    WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $item, $request ) {
		return new WP_REST_Response( is_wp_error( $item ) ? $item : $this->mapping_to_data( $item, $request ) );
	}

	/**
	 * JSON Schema for mappings
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'mapping',
			'type'       => 'object',
			/*
			 * Base properties for every Alias.
			 */
			'properties' => array(
				'id'     => array(
					'description' => __( 'Unique identifier for the object.' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'domain' => array(
					'description' => __( 'The domain name of the alias' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				),
				'active' => array(
					'description' => __( 'Whether the alias is active or not' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $schema;
	}

	/**
	 * Convert a Mapping object to data we can return
	 *
	 * @param Mapping         $mapping
	 * @param WP_REST_Request $request
	 * @return array
	 */
	protected function mapping_to_data( $mapping, $request ) {
		$data = array(
			'id'     => absint( $mapping->get_id() ),
			'domain' => $mapping->get_domain(),
			'active' => $mapping->is_active(),
		);

		// Return blog ID if sent
		if ( null !== $request->get_param( 'blog' ) ) {
			$data['blog'] = $request->get_param( 'blog' );
		}

		return $data;
	}

	/**
	 * Get the site from the request body or the current blog ID
	 *
	 * @param WP_REST_Request $request
	 * @return int
	 */
	protected function get_blog_id( $request ) {
		return $request->get_param( 'blog' ) ?: get_current_blog_id();
	}

}
