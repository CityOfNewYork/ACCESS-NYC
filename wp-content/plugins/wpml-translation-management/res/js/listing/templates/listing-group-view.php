<script type="text/html" id="table-listing-group">
	<tr class="tj-groups-heading groups-heading js-tj-groups-heading">
		<th colspan="7">
			<div class="listing-heading-inner-wrap">
				<div class="group-name">
					<h4><?php esc_html_e( 'Translation Batch sent on ', 'wpml-translation-management' ) ?><%= TJ.last_update %></h4>
					<%= TJ.batch_name ? '<p><?php esc_html_e( 'Batch Name: ', 'wpml-translation-management' ) ?> <strong>' + TJ.batch_name + '</strong></p>' : '' %>
				</div>
			</div>
			<div class="buttons">
				<div href="#" class="button-secondary group-action group-expand"><span
						class="dashicons dashicons-plus"></span>&nbsp;<?php esc_html_e( 'Expand', 'wpml-translation-management' ); ?></div>
				<div href="#" class="button-secondary group-action group-collapse"><span
						class="dashicons dashicons-minus"></span>&nbsp;<?php esc_html_e( 'Collapse', 'wpml-translation-management' ); ?>
				</div>
			</div>
			<div class="listing-heading-summary">
				<ul>
					<li class="js-group-info">
						<span id="group-displayed-jobs" class="value"><%=TJ.how_many%></span>
						<span id="group-out-of-text" style="display:<%=TJ.show_out_of%>;"><?php esc_html_e( 'out of',
																										 'wpml-translation-management' ); ?></span>
						<span id="group-all-jobs" class="value" style="display:<%=TJ.show_out_of%>;"><%=TJ.how_many_overall%></span> <?php echo __( 'Jobs',
																																					'wpml-translation-management' ); ?>
						<br>

						<div>
							<a href="#" id="group-previous-jobs"
							   style="display:<%=TJ.show_previous%>;"><?php esc_html_e( '&laquo; continue from the previous page',
																					 'wpml-translation-management' ); ?><br></a>
						</div>
						<div>
							<a href="#" id="group-remaining-jobs"
							   style="display:<%=TJ.show_remaining%>;"> <?php esc_html_e( 'continue on the next page &raquo;',
																					   'wpml-translation-management' ); ?></a>
						</div>
					</li>
					<%
					if(TJ.statuses) {
					%>
					<li>
						<ul class="group-list group-statuses js-group-statuses">
							<%
							_.each(TJ.statuses, function (count, status) {
							var percentage = count / TJ.how_many_overall * 100;
							%>
							<li>
								<span class="value"><%= percentage.toFixed(2) + '% ' %></span><%= status %>
							</li>
							<% }); %>
						</ul>
					</li>
					<%
					} %>
					<%
					if(TJ.languages) {
					%>
					<li>
						<ul class="group-list group-languages">

							<%
							_.each(TJ.languages, function (language_items, language) {
							%>
							<li>
								<span class="value"><%= language_items.length %></span> <%- language %>
							</li>
							<%
							});
							%>
						</ul>
					</li>
					<%
					} %>
				</ul>
			</div>
		</th>
	</tr>
</script>

<script type="text/html" id="batch-name-link-template">
	<a target="_blank" href="<%=TJ.url%>"><%=TJ.name%></a>
</script>
