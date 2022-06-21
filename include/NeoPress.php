<?php

namespace Neopress;

use Laudis\Neo4j\Basic\Session;
use function array_key_exists;
use function get_current_user_id;
use function get_the_ID;
use function is_single;
use function session_id;

class NeoPress {
	private Session $session;
	private string $userId;

	public function __construct( Session $session, string $userId ) {
		$this->session = $session;
		$this->userId  = $userId;
	}

	/**
	 * Register Shutdown Hook
	 */
	public function shutdown(): void {
		if ( is_single() ) {
			$this->log();
		}
	}

	/**
	 * Create a Cypher Query for a Category
	 */
	private function log(): void {
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

			$params['id']        = $this->userId;
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
		$result = $this->session->run( $cypher, $params );

		// Store Last Pageview in Session
		$_SESSION['neopress_last_pageview'] = $result->getAsMap( 0 )->get( 'id' );
	}
}