jQuery(document).ready(function ($) {
	$("#relevanssi_related_keywords").change(function (e) {
		var post_id = $("#this_post_id").val()
		var keywords = $("#relevanssi_related_keywords").val()
		var data = {
			action: "relevanssi_related_posts",
			security: relevanssi_metabox_data.metabox_nonce,
			post_id: post_id,
			keywords: keywords,
		}
		jQuery.post(ajaxurl, data, function (response) {
			response = JSON.parse(response)
			$("#related_posts_list").html(response.list)
		})
	})

	$("#relevanssi_related_include_ids").change(function (e) {
		var post_id = $("#this_post_id").val()
		var ids = $("#relevanssi_related_include_ids").val()
		var data = {
			action: "relevanssi_related_posts",
			security: relevanssi_metabox_data.metabox_nonce,
			post_id: post_id,
			ids: ids,
		}
		jQuery.post(ajaxurl, data, function (response) {
			response = JSON.parse(response)
			$("#related_posts_list").html(response.list)
		})
	})

	$("#relevanssi_hidebox").on("click", "button.removepost", function (e) {
		var remove_id = $(this).data("removepost")
		if (remove_id) {
			var post_id = $("#this_post_id").val()
			var data = {
				action: "relevanssi_related_remove",
				security: relevanssi_metabox_data.metabox_nonce,
				post_id: post_id,
				remove_id: remove_id,
			}
			jQuery.post(ajaxurl, data, function (response) {
				response = JSON.parse(response)

				$("#related_posts_list").html(response.related)
				$("#excluded_posts_list").html(response.excluded)
			})
		}
	})

	$("#relevanssi_hidebox").on("click", "button.returnpost", function (e) {
		var return_id = $(this).data("returnpost")
		console.log(return_id)
		if (return_id) {
			var post_id = $("#this_post_id").val()
			var data = {
				action: "relevanssi_related_return",
				security: relevanssi_metabox_data.metabox_nonce,
				post_id: post_id,
				return_id: return_id,
			}
			jQuery.post(ajaxurl, data, function (response) {
				response = JSON.parse(response)
				console.log(response)

				$("#related_posts_list").html(response.related)
				$("#excluded_posts_list").html(response.excluded)
			})
		}
	})
})
