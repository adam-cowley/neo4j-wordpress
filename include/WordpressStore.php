<?php

namespace Neopress;

use Exception;
use Laudis\Neo4j\Basic\UnmanagedTransaction as Transaction;
use Laudis\Neo4j\Contracts\TransactionInterface;
use WP_Term;
use function get_permalink;
use function get_post_field;
use function get_post_status;
use function get_the_category;
use function get_the_tags;
use function get_the_title;
use function wp_is_post_revision;

class WordpressStore {
	private \Laudis\Neo4j\Basic\Session $session;

	public function __construct( \Laudis\Neo4j\Basic\Session $session ) {
		$this->session = $session;
	}

	/**
	 * Merge a Post by its Post ID
	 */
	public function merge( int $post_id ): void {
		// Check Post isn't revision
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		$tx = $this->session->beginTransaction();

		try {
			// Store an array of Term ID's to merge later
			$terms = [];

			// For each category, add a MERGE query to our Batch
			$categories = get_the_category( $post_id );

			foreach ( $categories as $category ) {
				$terms[] = $category->term_id;

				$this->mergeCategory( $tx, $category );
			}

			// ...and the same for tags
			if ( $tags = get_the_tags( $post_id ) ) {
				foreach ( $tags as $tag ) {
					$terms[] = $tag->term_id;

					$this->mergeTag( $tx, $tag );
				}
			}

			// Update Post Details
			$this->mergePost( $tx, $post_id );

			// Detach
			$this->detachPost( $tx, $post_id );

			// Reattach
			$this->relateTaxonomies( $tx, $post_id, $terms );

			$author = get_post_field( 'post_author', $post_id );
			$this->mergeAuthor( $tx, $author );

			// Relate Author to Post
			$this->relateAuthor( $tx, $post_id, $author );

			$tx->commit();
		} catch ( Exception $e ) {
			$tx->rollback();

			throw $e;
		}
	}

	/**
	 * Create a Cypher Query for a Category
	 */
	private function mergeCategory( TransactionInterface $tx, WP_Term $category ): void {
		$cypher = <<<'CYPHER'
        MERGE (t:Taxonomy:Category {term_id: $termId})
        SET t += $category
        CYPHER;

		$tx->run( $cypher, [ 'termId' => $category->term_id, 'category' => $category->to_array() ] );
	}

	/**
	 * Create a Cypher Query for a Category
	 * @return void
	 */
	public function mergeTag( TransactionInterface $tx, WP_Term $tag ) {
		$cypher = <<<'CYPHER'
        MERGE (t:Taxonomy:Tag {term_id: $termId})
        SET t += $tag
        CYPHER;

		$tx->run( $cypher, [ 'termId' => $tag->term_id, 'tag' => (array) $tag ] );
	}

	/**
	 * Create a Cypher Query for a Category
	 */
	public function mergeAuthor( Transaction $tx, int $user_id ): void {
		$cypher = <<<'CYPHER'
        MERGE (u:User {user_id: $userId})
        CYPHER;

		$tx->run( $cypher, [ 'userId' => $user_id ] );
	}

	/**
	 * Add a merge query to the transaction to update the post
	 */
	private function mergePost( Transaction $tx, int $post_id ): void {
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
	private function detachPost( Transaction $tx, int $post_id ): void {
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
	private function relateTaxonomies( Transaction $tx, int $post_id, array $terms ) {
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
	private function relateAuthor( Transaction $tx, int $post_id, int $user_id ): void {
		$cypher = <<<'CYPHER'
            MATCH (p:Post {ID: $postId})
            MATCH (u:User {user_id: $userId})
            MERGE (u)-[:AUTHORED]->(p)
        CYPHER;

		$tx->run( $cypher, [ 'postId' => $post_id, 'userId' => $user_id ] );
	}
}
