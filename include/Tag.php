<?php

namespace Neopress;

use Laudis\Neo4j\Basic\UnmanagedTransaction as Transaction;
use WP_Term;

class Tag {

	/**
	 * Create a Cypher Query for a Category
	 * @return void
	 */
	public static function merge( Transaction $tx, WP_Term $tag ) {
		$cypher = <<<'CYPHER'
            MERGE (t:Taxonomy:Tag {term_id: $termId})
            SET t += $tag
        CYPHER;

		$tx->run( $cypher, [ 'termId' => $tag->term_id, 'tag' => (array) $tag ] );
	}

}
