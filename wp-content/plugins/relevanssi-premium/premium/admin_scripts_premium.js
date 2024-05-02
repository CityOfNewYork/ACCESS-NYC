jQuery(document).ready(function ($) {
	// Show and hide the "Show Relevanssi for admins" setting on the Overview tab depending
	// on whether the "Hide Relevanssi" setting is enabled.
	var show_post_controls = $("#show_post_controls")
	$("#relevanssi_hide_post_controls").on("change", function () {
		show_post_controls.toggleClass("screen-reader-text")
	})

	// Find out the latest row ID in the redirect table.
	var last_row_id = $(".redirect_table_row:last").attr("id")
	var redirect_row_index = 0
	if (last_row_id) {
		// There's a last row, we're on the Redirects tab.
		last_row_id = last_row_id.split("_")
		if (last_row_id.length > 1) {
			redirect_row_index = last_row_id[1]
		}
	}

	// Adds a new row to the redirect table on the Redirects tab.
	$("#add_redirect").on("click", function (e) {
		redirect_row_index++
		$(".redirect_table_row:last")
			.clone(true)
			.attr("id", "row_" + redirect_row_index)
			.insertAfter(".redirect_table_row:last")

		var query = $("#row_" + redirect_row_index + " input:first")
		query.val("")
		query.attr("name", "query_" + redirect_row_index)
		query.attr("id", "query_" + redirect_row_index)

		var partial = $("#row_" + redirect_row_index + " input:checkbox")
		partial.prop("checked", false)
		partial.attr("name", "partial_" + redirect_row_index)
		partial.attr("id", "partial_" + redirect_row_index)

		var url = $("#row_" + redirect_row_index + " input:eq(2)")
		url.val("")
		url.attr("id", "url_" + redirect_row_index)
		url.attr("name", "url_" + redirect_row_index)

		var hits = $("#row_" + redirect_row_index + " input:last")
		hits.val("")
		hits.attr("name", "hits_" + redirect_row_index)
		hits.attr("id", "hits_" + redirect_row_index)

		var hitsNumber = $("#row_" + redirect_row_index + " span:last")
		hitsNumber.html("0")
	})

	// Related posts tab: if "Matching post types" is checked, disable and uncheck other options.
	$("input.matching").on("click", function (e) {
		if ($(this).is(":checked")) {
			$("input.nonmatching").prop("checked", false)
			$("input.nonmatching").attr("disabled", true)
		} else {
			$("input.nonmatching").attr("disabled", false)
		}
	})

	// Related posts tab: Display default thumbnail option.
	$("#relevanssi_related_thumbnails").on("click", function (e) {
		$("#defaultthumbnail").toggleClass("screen-reader-text", !this.checked)
	})

	// Redirects tab redirect table row removal.
	$(".remove").on("click", function (e) {
		e.preventDefault()
		if ($("#redirect_table >tbody >tr").length > 1) {
			// If there is more than one row in the table, remove the last row.
			$(this).closest("tr").remove()
		} else {
			// Only one row left, don't remove it (because adding rows is based on cloning).
			// Instead empty out the values.
			$(".redirect_table_row:last input:text").val("")
			$(".redirect_table_row:last input:checkbox").prop("checked", false)
		}
	})

	// Related posts tab: Toggle settings for main switch.
	$("#relevanssi_related_enabled").on("click", function () {
		$("#tr_relevanssi_related_append input").attr("disabled", !this.checked)
		$("#tr_relevanssi_related_keyword input").attr("disabled", !this.checked)
		$("#relevanssi_related_number").attr("disabled", !this.checked)
		$("#relevanssi_related_months").attr("disabled", !this.checked)
		$("#tr_relevanssi_related_post_types input").attr("disabled", !this.checked)
		$("#relevanssi_related_nothing").attr("disabled", !this.checked)
		$("#relevanssi_related_notenough").attr("disabled", !this.checked)
		$("#relevanssi_related_titles").attr("disabled", !this.checked)
		$("#relevanssi_related_thumbnails").attr("disabled", !this.checked)
		$("#relevanssi_related_excerpts").attr("disabled", !this.checked)
		$("#relevanssi_related_cache_for_admins").attr("disabled", !this.checked)
		$("#relevanssi_flush_related_cache").attr("disabled", !this.checked)
	})

	// Redirects tab redirect table row cloning.
	$("a.copy").on("click", function (e) {
		e.preventDefault()
		redirect_row_index++
		$(this)
			.closest("tr")
			.clone(true)
			.attr("id", "row_" + redirect_row_index)
			.insertAfter(".redirect_table_row:last")

		var query = $("#row_" + redirect_row_index + " input:first")
		query.attr("name", "query_" + redirect_row_index)
		query.attr("id", "query_" + redirect_row_index)

		var partial = $("#row_" + redirect_row_index + " input:checkbox")
		partial.attr("name", "partial_" + redirect_row_index)
		partial.attr("id", "partial_" + redirect_row_index)

		var url = $("#row_" + redirect_row_index + " input:eq(2)")
		url.attr("name", "url_" + redirect_row_index)
		url.attr("name", "url_" + redirect_row_index)

		var hits = $("#row_" + redirect_row_index + " input:last")
		hits.val("")
		hits.attr("name", "hits_" + redirect_row_index)
		hits.attr("id", "hits_" + redirect_row_index)

		var hitsNumber = $("#row_" + redirect_row_index + " span:last")
		hitsNumber.html("0")
	})

	$("#attachments_tab :input").on("change", function (e) {
		$("#index").attr("disabled", "disabled")
		var relevanssi_note = $("#relevanssi-note")
		relevanssi_note.show()
		relevanssi_note.html(
			'<p class="description important">' + relevanssi.options_changed + "</p>"
		)
	})

	$("#build_index").on("click", function () {
		$("#relevanssi-progress").show()
		$("#results").show()
		$("#relevanssi-timer").show()
		$("#relevanssi-indexing-instructions").show()
		$("#stateoftheindex").html(relevanssi.reload_state)
		$("#indexing_button_instructions").hide()
		var results = document.getElementById("results")
		results.value = ""

		var data = {
			action: "relevanssi_truncate_index",
			security: nonce.indexing_nonce,
		}

		intervalID = window.setInterval(relevanssiUpdateClock, 1000)

		console.log("Truncating index.")
		results.value += relevanssi.truncating_index + " "
		jQuery.post(ajaxurl, data, function (response) {
			truncate_response = JSON.parse(response)
			console.log("Truncate index: " + truncate_response)
			if (truncate_response == true) {
				results.value += relevanssi.done + "\n"
			}
			var data = {
				action: "relevanssi_index_post_type_archives",
				security: nonce.post_type_archive_indexing_nonce,
			}
			console.log("Indexing post type archives.")
			results.value += "Indexing post type archives... "
			jQuery.post(ajaxurl, data, function (response) {
				console.log("Done")
				response = JSON.parse(response)
				results.value += response.feedback
				var data = {
					action: "relevanssi_count_users",
				}
				console.log("Counting users.")
				results.value += relevanssi.counting_users + " "
				jQuery.post(ajaxurl, data, function (response) {
					count_response = JSON.parse(response)
					console.log("Counted " + count_response + " users.")
					if (count_response < 0) {
						results.value += relevanssi.user_disabled + "\n"
					} else {
						results.value +=
							count_response + " " + relevanssi.users_found + "\n"
					}

					var user_total = count_response

					var data = {
						action: "relevanssi_count_taxonomies",
					}
					console.log("Counting taxonomies.")
					results.value += relevanssi.counting_terms + " "
					jQuery.post(ajaxurl, data, function (response) {
						count_response = JSON.parse(response)
						console.log("Counted " + count_response + " taxonomy terms.")
						if (count_response < 0) {
							results.value += relevanssi.taxonomy_disabled + "\n"
						} else {
							results.value +=
								count_response + " " + relevanssi.terms_found + "\n"
						}

						var taxonomy_total = count_response

						var data = {
							action: "relevanssi_count_posts",
						}
						console.log("Counting posts.")
						results.value += relevanssi.counting_posts + " "
						jQuery.post(ajaxurl, data, function (response) {
							count_response = JSON.parse(response)
							console.log("Counted " + count_response + " posts.")
							var post_total = parseInt(count_response)
							results.value +=
								count_response + " " + relevanssi.posts_found + "\n"

							var data = {
								action: "relevanssi_list_taxonomies",
							}
							console.log("Listing taxonomies.")
							jQuery.post(ajaxurl, data, function (response) {
								taxonomies_response = JSON.parse(response)
								console.log("Listing taxonomies: " + taxonomies_response)
								console.log("Starting indexing.")
								console.log("User total " + user_total)
								if (user_total > 0) {
									console.log("Indexing users.")
									var args = {
										total: user_total,
										completed: 0,
										total_seconds: 0,
										post_total: post_total,
										limit: 10,
										taxonomies: taxonomies_response,
										taxonomies_total: taxonomy_total,
									}
									process_user_step(args)
								} else if (taxonomy_total > 0) {
									console.log("Indexing taxonomies.")
									results.value += relevanssi.indexing_taxonomies + " "
									results.value += taxonomies_response + "\n"
									var args = {
										taxonomies: taxonomies_response,
										completed: 0,
										total: taxonomy_total,
										total_seconds: 0,
										post_total: post_total,
										current_taxonomy: "",
										offset: 0,
										limit: 20,
									}
									process_taxonomy_step(args)
								} else {
									console.log("Just indexing.")
									var args = {
										completed: 0,
										total: post_total,
										offset: 0,
										total_seconds: 0,
										limit: relevanssi_params.indexing_limit,
										adjust: relevanssi_params.indexing_adjust,
										extend: false,
										security: nonce.indexing_nonce,
									}
									process_indexing_step(args)
								}
							})
						})
					})
				})
			})
		})
	})
})

function process_user_step(args) {
	var completed = args.completed
	var total = args.total
	var total_seconds = args.total_seconds

	console.log(completed + " / " + total)
	var t0 = performance.now()

	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: {
			action: "relevanssi_index_users",
			limit: args.limit,
			offset: args.offset,
			completed: completed,
			total: total,
			security: nonce.user_indexing_nonce,
		},
		dataType: "json",
		success: function (response) {
			console.log(response)
			if (response.completed == "done") {
				var t1 = performance.now()
				var time_seconds = (t1 - t0) / 1000
				time_seconds = Math.round(time_seconds * 100) / 100
				total_seconds += time_seconds

				var results_textarea = document.getElementById("results")
				results_textarea.value += response.feedback
				results_textarea.scrollTop = results_textarea.scrollHeight
				var percentage_rounded = Math.round(response.percentage)

				jQuery(".rpi-progress div").animate(
					{
						width: percentage_rounded + "%",
					},
					50,
					function () {
						// Animation complete.
					}
				)

				console.log("Done indexing users.")

				if (args.taxonomies_total > 0) {
					var new_args = {
						completed: 0,
						total: args.taxonomies_total,
						taxonomies: args.taxonomies,
						current_taxonomy: "",
						post_total: args.post_total,
						offset: 0,
						total_seconds: total_seconds,
						limit: 20,
						extend: false,
					}
					process_taxonomy_step(new_args)
				} else {
					var new_args = {
						security: nonce.indexing_nonce,
						completed: 0,
						total: args.post_total,
						offset: 0,
						total_seconds: 0,
						limit: relevanssi_params.indexing_limit,
						adjust: relevanssi_params.indexing_adjust,
						extend: false,
					}
					process_indexing_step(new_args)
				}
			} else {
				var t1 = performance.now()
				var time_seconds = (t1 - t0) / 1000
				time_seconds = Math.round(time_seconds * 100) / 100
				total_seconds += time_seconds

				var estimated_time = rlv_format_approximate_time(
					Math.round(
						(total_seconds / response.percentage) * 100 - total_seconds
					)
				)

				document.getElementById("relevanssi_estimated").innerHTML =
					estimated_time

				if (time_seconds < 2) {
					args.limit = args.limit * 2
					// current limit can be indexed in less than two seconds; double the limit
				} else if (time_seconds < 5) {
					args.limit += 5
					// current limit can be indexed in less than five seconds; up the limit
				} else if (time_seconds > 20) {
					args.limit = Math.round(args.limit / 2)
					if (args.limit < 1) args.limit = 1
					// current limit takes more than twenty seconds; halve the limit
				} else if (time_seconds > 10) {
					args.limit -= 5
					if (args.limit < 1) args.limit = 1
					// current limit takes more than ten seconds; reduce the limit
				}

				var results_textarea = document.getElementById("results")
				results_textarea.value += response.feedback
				results_textarea.scrollTop = results_textarea.scrollHeight
				var percentage_rounded = Math.round(response.percentage)

				jQuery(".rpi-progress div").animate(
					{
						width: percentage_rounded + "%",
					},
					50,
					function () {
						// Animation complete.
					}
				)
				console.log("Next step.")
				var new_args = {
					completed: parseInt(response.completed),
					total: args.total,
					total_seconds: total_seconds,
					offset: response.offset,
					limit: args.limit,
					post_total: args.post_total,
					taxonomies: args.taxonomies,
					taxonomies_total: args.taxonomies_total,
				}
				process_user_step(new_args)
			}
		},
	})
}

function process_taxonomy_step(args) {
	var completed = args.completed
	var total = args.total
	var total_seconds = args.total_seconds

	console.log(completed + " / " + total)
	var t0 = performance.now()

	if (args.current_taxonomy == "") {
		taxonomy = args.taxonomies.shift()
		args.offset = 0
		args.limit = 20
	} else {
		taxonomy = args.current_taxonomy
	}

	if (taxonomy != undefined) {
		var results_textarea = document.getElementById("results")
		results_textarea.value += "Indexing " + "'" + taxonomy + "': "
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {
				action: "relevanssi_index_taxonomies",
				completed: completed,
				total: total,
				taxonomy: taxonomy,
				offset: args.offset,
				limit: args.limit,
				security: nonce.taxonomy_indexing_nonce,
			},
			dataType: "json",
			success: function (response) {
				console.log(response)
				if (response.completed == "done") {
					var t1 = performance.now()
					var time_seconds = (t1 - t0) / 1000
					time_seconds = Math.round(time_seconds * 100) / 100
					total_seconds += time_seconds

					var results_textarea = document.getElementById("results")
					results_textarea.value += response.feedback

					console.log("Done indexing taxonomies.")

					var new_args = {
						completed: 0,
						total: args.post_total,
						offset: 0,
						total_seconds: 0,
						limit: relevanssi_params.indexing_limit,
						adjust: relevanssi_params.indexing_adjust,
						extend: false,
						security: nonce.indexing_nonce,
					}
					process_indexing_step(new_args)
				} else {
					var t1 = performance.now()
					var time_seconds = (t1 - t0) / 1000
					time_seconds = Math.round(time_seconds * 100) / 100
					total_seconds += time_seconds

					var estimated_time = rlv_format_approximate_time(
						Math.round(
							(total_seconds / response.percentage) * 100 - total_seconds
						)
					)

					document.getElementById("relevanssi_estimated").innerHTML =
						estimated_time

					if (time_seconds < 2) {
						args.limit = args.limit * 2
						// current limit can be indexed in less than two seconds; double the limit
					} else if (time_seconds < 5) {
						args.limit += 5
						// current limit can be indexed in less than five seconds; up the limit
					} else if (time_seconds > 20) {
						args.limit = Math.round(args.limit / 2)
						if (args.limit < 1) args.limit = 1
						// current limit takes more than twenty seconds; halve the limit
					} else if (time_seconds > 10) {
						args.limit -= 5
						if (args.limit < 1) args.limit = 1
						// current limit takes more than ten seconds; reduce the limit
					}

					var results_textarea = document.getElementById("results")
					results_textarea.value += response.feedback
					results_textarea.scrollTop = results_textarea.scrollHeight
					var percentage_rounded = Math.round(response.percentage)

					jQuery(".rpi-progress div").animate(
						{
							width: percentage_rounded + "%",
						},
						50,
						function () {
							// Animation complete.
						}
					)
					console.log("Next step.")
					if (response.new_taxonomy) taxonomy = ""
					var new_args = {
						taxonomies: args.taxonomies,
						completed: parseInt(response.completed),
						total: args.total,
						total_seconds: total_seconds,
						post_total: args.post_total,
						current_taxonomy: taxonomy,
						offset: response.offset,
						limit: args.limit,
					}
					process_taxonomy_step(new_args)
				}
			},
		})
	} else {
		var new_args = {
			completed: 0,
			total: args.post_total,
			offset: 0,
			total_seconds: 0,
			limit: relevanssi_params.indexing_limit,
			adjust: relevanssi_params.indexing_adjust,
			extend: false,
			security: nonce.indexing_nonce,
		}
		process_indexing_step(new_args)
	}
}
