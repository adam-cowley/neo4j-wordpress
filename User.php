<?php

namespace Neopress;

use Laudis\Neo4j\Basic\UnmanagedTransaction as Transaction;

class User {

    /**
     * Create a Cypher Query for a Category
     *
     * @param  Int $post_id
     * @return void
     */
    public static function merge(Transaction $tx, $user_id) {
        $cypher = sprintf('
            MERGE (u:User {user_id: {user_id}})
        ');

        $tx->run($cypher, ['user_id' => $user_id]);
    }

}
