<?php

namespace Neopress;

use WP_Query;


class Recommend {

    /**
     * Provide a simple list of recommendations by Taxonomy
     *
     * @param  int $post_id [description]
     * @return arrat
     */
    public static function byTaxonomy($post_id) {
        $cypher = '
            MATCH (p:Post)-[:HAS_TAXONOMY]->(:Taxonomy)<-[:HAS_TAXONOMY]-(recommended:Post)
            WHERE p.ID = {post_id}
            AND recommended.status = "publish"
            RETURN id(recommended) as ID, recommended.created_at
            ORDER BY recommended.created_at DESC
            LIMIT 5
        ';

        return static::run($cypher, ['post_id' => $post_id]);
    }


    /**
     * Provide a weighted list of Recommendations based on Taxonomies and
     *
     * @param  int $post_id
     * @return WP_Query
     */
    public static function byWeighting($post_id) {
        $cypher = '
            MATCH (p:Post)-[:HAS_TAXONOMY|AUTHORED]-(target)-[:HAS_TAXONOMY|AUTHORED]-(recommended:Post)
            WHERE p.ID = {post_id}
            AND recommended.status = "publish"
            WITH labels(target) as labels,  recommended, case when "User" in labels(target) then 10 else 5 end as weight
            RETURN id(recommended) as ID, sum(weight) as weighting
            ORDER BY weighting DESC
            LIMIT 5
        ';

        return static::run($cypher, ['post_id' => $post_id]);
    }

    /**
     * Recommend unread posts similar to this post
     *
     * @return WP_Query
     */
    public function unreadForSession($post_id) {
        $cypher = '
            MATCH (s:Session) WHERE s.session_id = {session_id}
            MATCH (p:Post)-[:HAS_TAXONOMY|AUTHORED]-(target)-[:HAS_TAXONOMY|AUTHORED]-(recommended:Post)
            WHERE p.ID = {post_id}
            AND recommended.status = "publish"
            AND NOT ((s)-[:HAS_PAGEVIEW|VIEWED*2]->(p))
            WITH labels(target) as labels,  recommended, case when "User" in labels(target) then 10 else 5 end as weight
            RETURN id(recommended) as ID, sum(weight) as weighting
            ORDER BY weighting DESC
            LIMIT 5
        ';

        return static::run($cypher, ['post_id' => $post_id, 'session_id' => session_id()]);
    }

        /**
     * Recommend unread posts similar to this post
     *
     * @return WP_Query
     */
    public function unreadForUser($post_id) {
        $cypher = '
            MATCH (s:Session) WHERE s.session_id = {session_id}
            MATCH (p:Post)-[:HAS_TAXONOMY|AUTHORED]-(target)-[:HAS_TAXONOMY|AUTHORED]-(recommended:Post)
            WHERE p.ID = {post_id}
            AND recommended.status = "publish"
            AND NOT ((s)-[:HAS_PAGEVIEW|VIEWED*2]->(p))
            AND NOT ((s)<-[:HAS_SESSION]-(:User)-[:HAS_SESSION|HAS_PAGEVIEW|VIEWED]-(p))
            WITH labels(target) as labels,  recommended, case when "User" in labels(target) then 10 else 5 end as weight
            RETURN id(recommended) as ID, sum(weight) as weighting
            ORDER BY weighting DESC
            LIMIT 5
        ';

        return return static::run($cypher, ['post_id' => $post_id, 'session_id' => session_id()]);
    }

    /**
     * As all of our posts are going to be returning the same information
     * we should use a
     *
     * @param  string $cypher
     * @param  array  $params
     * @return WP_Query
     */
    private static function run($cypher, array $params) {
        $results = Neopress::client()->run($cypher, $params);

        // Get Post ID's from Query
        // TODO: A Map function in the SDK could be cool.
        $ids = [];

        foreach ($results->getRecords() as $row) {
            array_push($ids, $row->get('ID'));
        }

        // Query
        return new WP_Query([
            'post__in' => $ids
        ]);
    }

}
