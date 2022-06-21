<?php

namespace Neopress;

use WP_Query;


class Recommend {
	private \Laudis\Neo4j\Basic\Session $session;

	public function __construct( \Laudis\Neo4j\Basic\Session $session ) {
		$this->session = $session;
	}

	/**
	 * Provide a simple list of recommendations by Taxonomy
	 */
	public function byTaxonomy( int $post_id ): WP_Query {
		$cypher = <<<'CYPHER'
            MATCH (p:Post)-[:HAS_TAXONOMY]->(:Taxonomy)<-[:HAS_TAXONOMY]-(recommended:Post)
            WHERE p.ID = $postId
            AND recommended.status = 'publish'
            RETURN id(recommended) AS ID, recommended.created_at
            ORDER BY recommended.created_at DESC
            LIMIT 5
            CYPHER;

		return $this->run( $cypher, [ 'postId' => $post_id ] );
	}

	/**
	 * As all of our posts are going to be returning the same information
	 * we should use a
	 */
	private function run( string $cypher, array $params ): WP_Query {
		$results = $this->session->run( $cypher, $params );

		// Get Post ID's from Query
		// TODO: A Map function in the SDK could be cool.
		// - I even implemented pluck to simplify it further :) - Ghlen
		return new WP_Query( [
			'post__in' => $results->pluck( 'id' )->toArray()
		] );
	}

	/**
	 * Provide a weighted list of Recommendations based on Taxonomies and
	 */
	public function byWeighting( int $post_id ): WP_Query {
		$cypher = <<<'CYPHER'
            MATCH (p:Post)-[:HAS_TAXONOMY|AUTHORED]-(target)-[:HAS_TAXONOMY|AUTHORED]-(recommended:Post)
            WHERE p.ID = $postId
            AND recommended.status = 'publish'
            WITH labels(target) AS labels,  recommended, CASE WHEN 'User' IN labels(target) THEN 10 ELSE 5 END AS weight
            RETURN id(recommended) AS ID, sum(weight) AS weighting
            ORDER BY weighting DESC
            LIMIT 5
        CYPHER;

		return $this->run( $cypher, [ 'postId' => $post_id ] );
	}

	/**
	 * Recommend unread posts similar to this post
	 */
	public function unreadForSession( int $post_id ): WP_Query {
		$cypher = <<<'CYPHER'
            MATCH (s:Session) WHERE s.session_id = $sessionId
            MATCH (p:Post)-[:HAS_TAXONOMY|AUTHORED]-(target)-[:HAS_TAXONOMY|AUTHORED]-(recommended:Post)
            WHERE p.ID = $postId
            AND recommended.status = 'publish'
            AND NOT ((s)-[:HAS_PAGEVIEW|VIEWED*2]->(p))
            WITH labels(target) AS labels,  recommended, CASE WHEN 'User' IN labels(target) THEN 10 ELSE 5 END AS weight
            RETURN id(recommended) AS ID, sum(weight) AS weighting
            ORDER BY weighting DESC
            LIMIT 5
            CYPHER;

		return $this->run( $cypher, [ 'postId' => $post_id, 'sessionId' => session_id() ] );
	}

	/**
	 * Recommend unread posts similar to this post
	 */
	public function unreadForUser( int $post_id ): WP_Query {
		$cypher = <<<'CYPHER'
            MATCH (s:Session) WHERE s.session_id = $sessionId
            MATCH (p:Post)-[:HAS_TAXONOMY|AUTHORED]-(target)-[:HAS_TAXONOMY|AUTHORED]-(recommended:Post)
            WHERE p.ID = $postId
            AND recommended.status = 'publish'
            AND NOT ((s)-[:HAS_PAGEVIEW|VIEWED*2]->(p))
            AND NOT ((s)<-[:HAS_SESSION]-(:User)-[:HAS_SESSION|HAS_PAGEVIEW|VIEWED]-(p))
            WITH labels(target) AS labels,  recommended, CASE WHEN 'User' IN labels(target) THEN 10 ELSE 5 END AS weight
            RETURN id(recommended) AS ID, sum(weight) AS weighting
            ORDER BY weighting DESC
            LIMIT 5
            CYPHER;

		return $this->run( $cypher, [ 'postId' => $post_id, 'sessionId' => session_id() ] );
	}
}
