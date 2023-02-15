<?php

namespace WPML\TM\ATE\Log;

use WPML\Collect\Support\Collection;

class View {

	/** @var Collection $logs */
	private $logs;

	public function __construct( Collection $logs ) {
		$this->logs = $logs;
	}

	public function renderSupportSection() {
		?>
		<div class="wrap">
			<h2 id="ate-log">
				<?php esc_html_e( 'Advanced Translation Editor', 'wpml-translation-management' ); ?>
			</h2>
			<p>
				<a href="<?php echo admin_url( 'admin.php?page=' . Hooks::SUBMENU_HANDLE ); ?>">
					<?php echo sprintf( esc_html__( 'Error Logs (%d)', 'wpml-translation-management' ), $this->logs->count() ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public function renderPage() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Advanced Translation Editor Error Logs', 'wpml-translation-management' ); ?></h1>
			<br>
			<table class="wp-list-table widefat fixed striped posts">
				<thead><?php $this->renderTableHeader(); ?></thead>

				<tbody id="the-list">
				<?php
				if ( $this->logs->isEmpty() ) {
					$this->renderEmptyTable();
				} else {
					$this->logs->each( [ $this, 'renderTableRow' ] );
				}
				?>
				</tbody>
				<tfoot><?php $this->renderTableHeader(); ?></tfoot>
			</table>
		</div>
		<?php
	}

	private function renderTableHeader() {
		?>
		<tr>
			<th class="date">
				<span><?php esc_html_e( 'Date', 'wpml-translation-management' ); ?></span>
			</th>
			<th class="event">
				<span><?php esc_html_e( 'Event', 'wpml-translation-management' ); ?></span>
			</th>
			<th class="description">
				<span><?php esc_html_e( 'Description', 'wpml-translation-management' ); ?></span>
			</th>
			<th class="wpml-job-id">
				<span><?php esc_html_e( 'WPML Job ID', 'wpml-translation-management' ); ?></span>
			</th>
			<th class="ate-job-id">
				<span><?php esc_html_e( 'ATE Job ID', 'wpml-translation-management' ); ?></span>
			</th>
			<th class="extra-data">
				<span><?php esc_html_e( 'Extra data', 'wpml-translation-management' ); ?></span>
			</th>
		</tr>
		<?php
	}

	public function renderTableRow( Entry $entry ) {
		?>
		<tr>
			<td class="date">
				<?php echo esc_html( $entry->getFormattedDate() ); ?>
			</td>
            <td class="event">
				<?php echo esc_html( EventsTypes::getLabel( $entry->eventType ) ); ?>
            </td>
			<td class="description">
				<?php echo esc_html( $entry->description ); ?>
			</td>
			<td class="wpml-job-id">
				<?php echo esc_html( $entry->wpmlJobId ); ?>
			</td>
			<td class="ate-job-id">
				<?php echo esc_html( $entry->ateJobId ); ?>
			</td>
			<td class="extra-data">
				<?php echo esc_html( $entry->getExtraDataToString() ); ?>
			</td>
		</tr>
		<?php
	}

	private function renderEmptyTable() {
		?>
		<tr>
			<td colspan="6" class="title column-title has-row-actions column-primary">
				<?php esc_html_e( 'No entries', 'wpml-translation-management' ); ?>
			</td>
		</tr>
		<?php
	}
}
