<?php
/**
 * Rebuild link tracking tables from scratch.  This takes several
 * hours, depending on the database size and server configuration.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 */

use MediaWiki\Maintenance\Maintenance;

// @codeCoverageIgnoreStart
require_once __DIR__ . '/Maintenance.php';
// @codeCoverageIgnoreEnd

/**
 * Maintenance script that rebuilds link tracking tables from scratch.
 *
 * @ingroup Maintenance
 */
class RebuildAll extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Rebuild links, text index and recent changes' );
	}

	/** @inheritDoc */
	public function getDbType() {
		return Maintenance::DB_ADMIN;
	}

	public function execute() {
		// Rebuild the text index
		if ( $this->getReplicaDB()->getType() != 'postgres' ) {
			$this->output( "** Rebuilding fulltext search index (if you abort "
				. "this will break searching; run this script again to fix):\n" );
			$rebuildText = $this->createChild( RebuildTextIndex::class, 'rebuildtextindex.php' );
			$rebuildText->execute();
		}

		// Rebuild RC
		$this->output( "\n\n** Rebuilding recentchanges table:\n" );
		$rebuildRC = $this->createChild( RebuildRecentchanges::class, 'rebuildrecentchanges.php' );
		$rebuildRC->execute();

		// Rebuild link tables
		$this->output( "\n\n** Rebuilding links tables -- this can take a long time. "
			. "It should be safe to abort via ctrl+C if you get bored.\n" );
		$rebuildLinks = $this->createChild( RefreshLinks::class, 'refreshLinks.php' );
		$rebuildLinks->execute();

		$this->output( "Done.\n" );
	}
}

// @codeCoverageIgnoreStart
$maintClass = RebuildAll::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
