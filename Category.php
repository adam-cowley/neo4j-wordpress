<?php

namespace Neopress;

use GraphAware\Neo4j\Client\Transaction\Transaction;
use WP_Term;

class Category {

    /**
     * Create a Cypher Query for a Category
     *
     * @param  Int $post_id
     * @return void
     */
    public static function merge(Transaction $tx, WP_Term $category) {
        $cypher = sprintf('
            MERGE (t:Taxonomy:Category {term_id: {term_id}})
            SET t += {category}
        ');

        $tx->push($cypher, ['term_id' => $category->term_id, 'category' => (array) $category]);
    }

}
