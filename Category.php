<?php

namespace Neopress;

use Laudis\Neo4j\Contracts\UnmanagedTransactionInterface as Transaction;
use WP_Term;

class Category
{
    /**
     * Create a Cypher Query for a Category
     */
    public static function merge(Transaction $tx, WP_Term $category): void {
        $cypher = <<<'CYPHER'
            MERGE (t:Taxonomy:Category {term_id: $termId})
            SET t += $category
            CYPHER;

        $tx->run($cypher, ['termId' => $category->term_id, 'category' => (array) $category]);
    }

}
