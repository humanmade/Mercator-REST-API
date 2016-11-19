<?php

namespace Mercator\REST;

use Mercator\Mapping;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

class Primary_Domain_Controller extends WP_REST_Controller {

	private $parent_controller;
	private $parent_namespace;
	private $rest_base;

	/**
	 * Primary_Domain_Controller constructor.
	 */
	public function __construct() {
		$this->parent_controller = new Mappings_Controller();
		$this->parent_namespace  = $this->parent_controller->namespace;
		$this->rest_base         = 'primary';
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->parent_namespace, $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => array(
					'mapping' => array(
						'sanitize_callback' => 'absint',
						'required'          => true,
					),
				),
			),

			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		return $this->parent_controller->get_item_permissions_check( $request );
	}

	/**
	 * Get one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		return $this->prepare_item_for_response( null, $request );
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
		$mapping_id = $request->get_param( 'mapping' );
		$mapping    = Mapping::get( $mapping_id );

		if ( is_wp_error( $mapping ) ) {
			return $mapping;
		}

		if ( null === $mapping ) {
			return new WP_Error( __( 'You must supply a valid mapping ID to set as the primary domain via the `mapping` parameter.', 'mercator' ) );
		}

		$result = $mapping->make_primary();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->prepare_item_for_response( null, $request );
	}


	/**
	 * Prepare the item for the REST response.
	 *
	 * @param mixed           $item    WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $item, $request ) {

		$blog = $this->get_blog_id( $request );
		$item = get_site( $blog );

		if ( is_wp_error( $item ) || is_null( $item ) ) {
			return new WP_REST_Response( $item );
		}

		// Get object vars
		$data = $item->to_array();

		return new WP_REST_Response( $data );
	}

	/**
	 * JSON Schema for mappings
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'site',
			'type'       => 'object',
			'required'   => array(
				'blog_id',
				'domain',
			),
			/*
			 * Base properties for every Alias.
			 */
			'properties' => array(
				'blog_id'      => array(
					'description' => __( 'Unique identifier for the object.' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'domain'       => array(
					'description' => __( 'The domain name of the site' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'path'         => array(
					'description' => __( 'The path name of the blog on a subfolder install' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'site_id'      => array(
					'description' => __( 'The network ID this blog belongs to.' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'registered'   => array(
					'description' => __( 'The date the blog was registered' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'last_updated' => array(
					'description' => __( 'The date the blog was last updated' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'public'       => array(
					'description' => __( 'Whether the blog is public or not' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'archived'     => array(
					'description' => __( 'Whether the blog is archived or not' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'mature'       => array(
					'description' => __( 'Whether the blog is for mature audiences or not' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'spam'         => array(
					'description' => __( 'Whether the blog is marked as spam or not' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'deleted'      => array(
					'description' => __( 'Whether the blog has been deleted or not' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'lang_id'      => array(
					'description' => __( 'The language code for the blog' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $schema;
	}

}
