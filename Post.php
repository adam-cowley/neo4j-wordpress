<?php

namespace Neopress;

use Exception;
use GraphAware\Neo4j\Client\Transaction\Transaction;
use WP_Post;

class Post {

    /**
     * Add a merge query to the transaction to update the post
     *
     * @param  Transaction  $tx
     * @param  int          $post_id
     * @return void
     */
    private static function mergePost(Transaction $tx, $post_id) {
        // Write Cypher MERGE query
        $cypher = sprintf('
            MERGE (p:Post {ID: {post_id}})
            ON CREATE SET p.created_at = timestamp()
            ON MATCH SET p.updated_at = timestamp()
            SET p.permalink = {permalink},
                p.title = {title},
                p.status = {status}
        ');

        // Set Parameters
        $params = [
            'post_id' => $post_id,
            'permalink' => get_permalink( $post_id ),
            'title' => get_the_title( $post_id ),
            'status' => get_post_status( $post_id ),
        ];

        // Add to Transaction
        $tx->push($cypher, $params);
    }

    /**
     * Detach a Post from it's Taxonomies
     *
     * @param  Transaction $tx
     * @param  int         $post_id
     * @return void
     */
    private static function detachPost(Transaction $tx, $post_id) {
        $cypher = 'MATCH (p:Post {ID: {post_id}})-[r]-() DELETE r';
        $params = ['post_id' => $post_id];

        $tx->push($cypher, $params);
    }

    /**
     * Link a post to it's taxonomies
     *
     * @param Transaction $tx
     * @param int         $post_id
     * @param array       $terms
     */
    private static function relateTaxonomies(Transaction $tx, $post_id, array $terms) {
        $cypher = '
            MATCH (p:Post {ID: {post_id}})
            WITH p, {terms} as terms
            UNWIND terms AS term_id
            MATCH (t:Taxonomy) where t.term_id = term_id
            MERGE (p)-[:HAS_TAXONOMY]->(t)
        ';

        $params = [
            'post_id' => $post_id,
            'terms' => $terms
        ];

        $tx->push($cypher, $params);
    }

    /**
     * Create a relationship between the Post and the Author
     *
     * @param  Transaction $tx
     * @param  int         $post_id
     * @param  int         $user_id
     * @return void
     */
    private static function relateAuthor(Transaction $tx, $post_id, $user_id) {
        $cypher = '
            MATCH (p:Post {ID: {post_id}})
            MATCH (u:User {user_id: {user_id}})
            MERGE (u)-[:AUTHORED]->(p)
        ';

        $tx->push($cypher, ['post_id' => $post_id, 'user_id' => $user_id]);
    }

    /**
     * Merge a Post by it's Post ID
     *
     * @param  Int $post_id
     * @return void
     */
    public static function merge($post_id) {
        // Check Post isn't revision
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Create a new Transaction
        $tx = Neopress::client()->transaction();

        try {
            // Store an array of Term ID's to merge later
            $terms = [];

            // For each category, add a MERGE query to our Batch
            $categories = get_the_category($post_id);

            foreach ($categories as $category) {
                array_push($terms, $category->term_id);

                Category::merge($tx, $category);
            }

            // ...and the same for tags
            if ( $tags = get_the_tags($post_id) ) {
                foreach ($tags as $tag) {
                    array_push($terms, $tag->term_id);

                    Tag::merge($tx, $tag);
                }
            }

            // Update Post Details
            static::mergePost($tx, $post_id);

            // Detach
            static::detachPost($tx, $post_id);

            // Reattach
            static::relateTaxonomies($tx, $post_id, $terms);

            // Merge Author
            $author = get_post_field('post_author', $post_id);

            // Create Author
            User::merge($tx, $author);

            // Relate Author to Post
            static::relateAuthor($tx, $post_id, $author);

            // Run it
            $tx->commit();
        }
        catch (Exception $e) {
            // Rollback
            $tx->rollback();

            // Error Handling
        }
    }

}
