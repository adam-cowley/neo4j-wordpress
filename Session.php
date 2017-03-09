<?php

namespace Neopress;

use WP_User;

class Session {

    /**
     * Create a Cypher Query for a Category
     *
     * @return void
     */
    public static function log() {
        // Merge Page
        $cypher = 'MERGE (p:Post {ID: {page_id}})';
        $params = ['page_id' => get_the_ID()];

        // Attribute the Pageview to a Session
        if ( $session_id = session_id() ) {
            // Set User's Wordpress ID if logged in
            if ($user_id = get_current_user_id()) {
                $cypher .= ' MERGE (u:User {user_id:{user_id}})';
                $cypher .= ' SET u.id = {id}';

                $params['user_id'] = $user_id;
            }
            else {
                $cypher .= ' MERGE (u:User {id: {id}})';
            }

            // Create Session
            $cypher .= ' MERGE (s:Session {session_id: {session_id}})';

            // Attribute Session to User
            $cypher .= ' MERGE (u)-[:HAS_SESSION]->(s)';

            // Create new Pageview
            $cypher .= ' CREATE (s)-[:HAS_PAGEVIEW]->(v:Pageview {created_at:timestamp()})';

            // Relate Pageview to Page
            $cypher .= ' CREATE (v)-[:VISITED]->(p)';

            $params['id'] = Neopress::user();
            $params['session_id'] = $session_id;
        }

        // Create :NEXT relationship from last pageview
        if (array_key_exists('neopress_last_pageview', $_SESSION)) {
            $cypher .= ' WITH v';
            $cypher .= ' MATCH (last:Pageview) WHERE id(last) = {last_pageview}';
            $cypher .= ' CREATE (last)-[:NEXT]->(v)';

            $params['last_pageview'] = $_SESSION['neopress_last_pageview'];
        }

        // Return Pageview ID
        $cypher .= 'RETURN id(v) as id';

        // Run Query
        $result = Neopress::client()->run($cypher, $params);

        // Store Last Pageview in Session
        $_SESSION['neopress_last_pageview'] = $result->getRecord()->get('id');
    }

}
