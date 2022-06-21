<?php

namespace Neopress;

class Session {

	/**
	 * Create a Cypher Query for a Category
	 */
	public static function log(): void {
		// Merge Page
		$cypher = 'MERGE (p:Post {ID: $pageId})';
		$params = [ 'pageId' => get_the_ID() ];

		// Attribute the Pageview to a Session
		if ( $session_id = session_id() ) {
			// Set User's WordPress ID if logged in
			if ( $user_id = get_current_user_id() ) {
				$cypher .= ' MERGE (u:User {user_id:$userId})';
				$cypher .= ' SET u.id = $id';

				$params['userId'] = $user_id;
			} else {
				$cypher .= ' MERGE (u:User {id: $id})';
			}

			// Create Session
			$cypher .= ' MERGE (s:Session {session_id: $sessionId})';

			// Attribute Session to User
			$cypher .= ' MERGE (u)-[:HAS_SESSION]->(s)';

			// Create new Pageview
			$cypher .= ' CREATE (s)-[:HAS_PAGEVIEW]->(v:Pageview {created_at:timestamp()})';

			// Relate Pageview to Page
			$cypher .= ' CREATE (v)-[:VISITED]->(p)';

			$params['id']        = NeoPress::getUser();
			$params['sessionId'] = $session_id;
		}

		// Create :NEXT relationship from last pageview
		if ( array_key_exists( 'neopress_last_pageview', $_SESSION ) ) {
			$cypher .= ' WITH v';
			$cypher .= ' MATCH (last:Pageview) WHERE id(last) = $lastPageview';
			$cypher .= ' CREATE (last)-[:NEXT]->(v)';

			$params['lastPageview'] = $_SESSION['neopress_last_pageview'];
		}

		// Return Pageview ID
		$cypher .= 'RETURN id(v) as id';

		// Run Query
		$result = NeoPress::client()->run( $cypher, $params );

		// Store Last Pageview in Session
		$_SESSION['neopress_last_pageview'] = $result->getAsMap( 0 )->get( 'id' );
	}

}
