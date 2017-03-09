<?php

namespace Neopress;

use GraphAware\Neo4j\Client\Transaction\Transaction;

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

        $tx->push($cypher, ['user_id' => $user_id]);
    }

}
