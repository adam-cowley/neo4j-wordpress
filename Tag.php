<?php

namespace Neopress;

use GraphAware\Neo4j\Client\Transaction\Transaction;
use WP_Term;

class Tag {

    /**
     * Create a Cypher Query for a Category
     *
     * @param  Int $post_id
     * @return void
     */
    public static function merge(Transaction $tx, WP_Term $tag) {
        $cypher = sprintf('
            MERGE (t:Taxonomy:Tag {term_id: {term_id}})
            SET t += {tag}
        ');

        $tx->push($cypher, ['term_id' => $tag->term_id, 'tag' => (array) $tag]);
    }

}
