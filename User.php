<?php

namespace Neopress;

use Laudis\Neo4j\Basic\UnmanagedTransaction as Transaction;

class User {

    /**
     * Create a Cypher Query for a Category
     */
    public static function merge(Transaction $tx, int $user_id): void {
        $cypher = <<<'CYPHER'
            MERGE (u:User {user_id: $userId})
            CYPHER;

        $tx->run($cypher, ['userId' => $user_id]);
    }

}
