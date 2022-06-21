<?php

namespace Neopress;

use Exception;
use Laudis\Neo4j\Basic\UnmanagedTransaction as Transaction;

class Post {

	/**
	 * Merge a Post by its Post ID
	 *
	 * @param Int $post_id
	 *
	 * @return void
	 */
	public static function merge( int $post_id ) {
		// Check Post isn't revision
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Create a new Transaction
		$tx = NeoPress::client()->beginTransaction();

		try {
			// Store an array of Term ID's to merge later
			$terms = [];

			// For each category, add a MERGE query to our Batch
			$categories = get_the_category( $post_id );

			foreach ( $categories as $category ) {
				$terms[] = $category->term_id;

				Category::merge( $tx, $category );
			}

			// ...and the same for tags
			if ( $tags = get_the_tags( $post_id ) ) {
				foreach ( $tags as $tag ) {
					$terms[] = $tag->term_id;

					Tag::merge( $tx, $tag );
				}
			}

			// Update Post Details
			static::mergePost( $tx, $post_id );

			// Detach
			static::detachPost( $tx, $post_id );

			// Reattach
			static::relateTaxonomies( $tx, $post_id, $terms );

			// Merge Author
			$author = get_post_field( 'post_author', $post_id );

			// Create Author
			User::merge( $tx, $author );

			// Relate Author to Post
			static::relateAuthor( $tx, $post_id, $author );

			// Run it
			$tx->commit();
		} catch ( Exception $e ) {
			// Rollback
			$tx->rollback();

			// Error Handling
		}
	}

	/**
	 * Add a merge query to the transaction to update the post
	 *
	 * @param Transaction $tx
	 * @param int $post_id
	 *
	 * @return void
	 */
	private static function mergePost( Transaction $tx, int $post_id ) {
		$cypher = <<<'CYPHER'
            MERGE (p:Post {ID: $postId})
            ON CREATE SET p.created_at = timestamp()
            ON MATCH SET p.updated_at = timestamp()
            SET p.permalink = $permalink,
                p.title = $title,
                p.status = $status
        CYPHER;

		// Set Parameters
		$params = [
			'postId'    => $post_id,
			'permalink' => get_permalink( $post_id ),
			'title'     => get_the_title( $post_id ),
			'status'    => get_post_status( $post_id ),
		];

		// Add to Transaction
		$tx->run( $cypher, $params );
	}

	/**
	 * Detach a Post from its Taxonomies
	 */
	private static function detachPost( Transaction $tx, int $post_id ): void {
		$cypher = 'MATCH (p:Post {ID: $postId})-[r]-() DELETE r';
		$params = [ 'postId' => $post_id ];

		$tx->run( $cypher, $params );
	}

	/**
	 * Link a post to it's taxonomies
	 *
	 * @param Transaction $tx
	 * @param int $post_id
	 * @param array $terms
	 */
	private static function relateTaxonomies( Transaction $tx, int $post_id, array $terms ) {
		$cypher = <<<'CYPHER'
            MATCH (p:Post {ID: $postId})
            WITH p
            UNWIND $terms AS term_id
            MATCH (t:Taxonomy) WHERE t.term_id = term_id
            MERGE (p)-[:HAS_TAXONOMY]->(t)
        CYPHER;

		$params = [
			'postId' => $post_id,
			'terms'  => $terms
		];

		$tx->run( $cypher, $params );
	}

	/**
	 * Create a relationship between the Post and the Author
	 */
	private static function relateAuthor( Transaction $tx, int $post_id, int $user_id ): void {
		$cypher = <<<'CYPHER'
            MATCH (p:Post {ID: $postId})
            MATCH (u:User {user_id: $userId})
            MERGE (u)-[:AUTHORED]->(p)
        CYPHER;

		$tx->run( $cypher, [ 'postId' => $post_id, 'userId' => $user_id ] );
	}

}
