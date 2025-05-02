/* globals ajaxurl, wpml_groups_to_scan, wpml_active_plugins_themes, wpml_mo_scan_ui_files */

var WPML_ST = WPML_ST || {};

jQuery(function($) {

	'use strict';

	WPML_ST.ScanningSection = function(scanningSection) {
		$('#wpml-mo-scan-localization-page').css('display', 'none');
		this.init();
		this.attachEvents();
	};

	WPML_ST.ScanningSection.prototype = {
		setFilters: function(filters) {
			this.filters = filters;
		},

		reinit: function() {
			this.init();
			this.attachEvents();
		},

		init: function() {
			this.rescanMoSection = jQuery('#wpml-st-changed-mo-files-form');
			this.rescanMoSectionSelectAllButton = jQuery('#select-all-changed-mo-checkboxes-to-rescan');

			this.scanningSection = jQuery('#wpml-st-localization-section');
			this.scanningTable = this.scanningSection.find('#wpml-st-localization-table');
			this.scanningButton = $('#wpml_theme_plugin_localization_scan');

			this.otherContentHeader = jQuery('#wpml-other-content-header');
			this.otherContent = jQuery('#wpml-other-content');

			this.markAllItemsDetectedForRescanMo();
			this.initRescanMoSection();
		},

		attachEvents: function() {
			var self = this;

			this.scanningTable.on('change', 'input:checkbox', {instance: this}, function(event) {
				self.setIsScanButtonDisabled();
			});

			this.rescanMoSectionSelectAllButton.on('click', function() {
				self.filters.setAllFilterForInactiveFilters();
				var itemsToRescanMo = self.getAllItems({
					onlyActive: true,
					detectedForRescanMo: true,
				});

				itemsToRescanMo.find("input[type=checkbox]").prop('checked', true);
				itemsToRescanMo.each(function() {
					jQuery(this).closest('.item').attr('data-disablephpscan', '1');
				});
				self.setIsScanButtonDisabled();
			});
		},

		detachEvents: function() {
			this.scanningTable.off('change', 'input:checkbox');
			this.rescanMoSectionSelectAllButton.off('click');
		},

		initRescanMoSection: function() {
			this.markAllItemsDetectedForRescanMo();
			this.hideMoRescanFormIfNoActiveItemsForRescan();
		},

		markAllItemsDetectedForRescanMo: function() {
			var items = this.getAllItems();
			var rescanItems = this.getDetectedItemsForRescanMo();
			items = items.filter(function() {
				return rescanItems.indexOf($(this).closest('.item').attr('data-componentidformoscan')) !== -1;
			});
			items.attr('data-rescanmo', '1');
		},

		getDetectedItemsForRescanMo: function() {
			if(typeof wpml_mo_scan_ui_files === 'undefined') {
				return [];
			}

			var filesToScan = wpml_mo_scan_ui_files.files_to_scan;
			var pluginNamesForRescan = (filesToScan && filesToScan.plugins) ? filesToScan.plugins : [];
			var themeNamesForRescan = (filesToScan && filesToScan.themes) ? filesToScan.themes : [];
			var otherNamesForRescan = (filesToScan && filesToScan.other) ? filesToScan.other : [];
			var items = [];

			return items.concat(pluginNamesForRescan).concat(themeNamesForRescan).concat(otherNamesForRescan); 
		},

		hideMoRescanFormIfNoActiveItemsForRescan: function() {
			var detectedItems = this.getDetectedItemsForRescanMo();
			var activeItemsForMoRescan = this.getAllItems({ onlyActive: true }).filter(function() {
				return detectedItems.indexOf($(this).attr('data-componentidformoscan')) !== -1;
			});
			if(activeItemsForMoRescan.length > 0) {
				return;
			}

			this.rescanMoSection.css('display', 'none');
		},

		getAllItems: function(selectors) {
			selectors = selectors || {};
			var items = this.scanningTable.find('[data-itemcategory="theme"],[data-itemcategory="plugin"],[data-itemcategory="other"]');

			if(selectors.onlyActive) {
				items = items.filter(function() {
					return parseInt($(this).closest('.item').attr('data-isactive'), 10) === 1;
				});
			}

			if(selectors.detectedForRescanMo) {
				items = items.filter(function() {
					return parseInt($(this).closest('.item').attr('data-rescanmo'), 10) === 1;
				});
			}

			return items;
		},

		setIsScanButtonDisabled: function() {
			var checked = $(this.scanningTable).find( '.item input:checkbox:checked' );
			var disableScanButton = ! Boolean( checked.length );
			this.scanningButton.prop('disabled', disableScanButton );
		},

		getTableSectionByType: function(sectionType) {
			if(sectionType === 'plugin') {
				return this.scanningTable.find('#wpml-plugin-content');
			}
			if(sectionType === 'theme') {
				return this.scanningTable.find('#wpml-theme-content');
			}
			if(sectionType === 'other') {
				return this.scanningTable.find('#wpml-other-content');
			}
		},

		getNonces: function() {
			var source = this.scanningTable.find('thead');
			return {
				'folderAction': source.attr('data-scan_folder-action'),
				'folderNonce': source.attr('data-scan_folder-nonce'),
				'filesAction': source.attr('data-scan_files-action'),
				'filesNonce': source.attr('data-scan_files-nonce'),
				'updateHashAction': source.attr('data-update_hash-action'),
				'updateHashNonce': source.attr('data-update_hash-nonce'),
				'updateStatsEndpoint': source.attr('data-update_stats-endpoint'),
				'updateStatsNonce': source.attr('data-update_stats-nonce'),
			};
		},

		getItemsToRescanMo: function() {
			var filter = function() {
				return $(this).closest('.item').attr('data-rescanmo') === '1' && $(this).find("input[type=checkbox]").prop('checked');
			};
			var makeMap = function(array) {
				return function() {
					array.push($(this).attr('data-componentidformoscan'));
				};
			};

			var themes = [];
			var plugins = [];
			var other = [];

			this.scanningTable.find('[data-itemcategory="theme"]').filter(filter).each(makeMap(themes));
			this.scanningTable.find('[data-itemcategory="plugin"]').filter(filter).each(makeMap(plugins));
			this.scanningTable.find('[data-itemcategory="other"]').filter(filter).each(makeMap(other));

			return {
				'themes': themes,
				'plugins': plugins,
				'other': other,
			};
		},

		hasItemsToRescanMo: function() {
			var items = this.getItemsToRescanMo();
			return (
				items.themes.length > 0 ||
				items.plugins.length > 0 ||
				items.other.length > 0
			);
		},

		getItemsCountToRescanMo: function() {
			var items = this.getItemsToRescanMo();
			return ( items.themes.length + items.plugins.length + items.other.length );
		},
	};

	WPML_ST.ThemePluginFilter = function(scanningSection){
		this.scanningSection = scanningSection;
		this.openedDomainCmpLabels = [];
		this.selectedFilterStatusByCategory = {};

		this.init();
		this.attachEvents();
	};

	WPML_ST.ThemePluginFilter.prototype = {
		getScanningTable: function() {
			return this.scanningSection.scanningTable;
		},

		reinit: function() {
			this.init();
			this.attachEvents();
			this.restoreSelectedFilters();
			this.restoreOpenedDomainTabs();
		},

		init: function() {
			var self = this;

			this.allTableItemsCheckboxSelector = 'thead .table-header .checkbox-column :checkbox';
			this.allCategoryItemsCheckboxSelector = 'tbody .category-header .checkbox-column :checkbox';
			this.checkboxSelector = '.item input:checkbox';
			this.filterSelector = '.state-selector li';
			this.toggleSubitemsListSelector = '.toggle-list-items a';

			self.syncTableHeaderCheckbox();
			$(this.getScanningTable()).find(this.allCategoryItemsCheckboxSelector).each(function() {
				self.syncCategoryHeaderCheckbox( $(this) );
			});
		},

		attachEvents: function() {
			var self = this;

			$(this.getScanningTable()).on('click', this.allTableItemsCheckboxSelector, function(event) {
				self.toggleCheckboxesWithCategoryCheckboxes( self.getScanningTable().find("tbody"), $(this) );
			});

			$(this.getScanningTable()).on('click', this.allCategoryItemsCheckboxSelector, function(event) {
				self.toggleCategoryCheckboxes( $(this) );
				self.syncTableHeaderCheckbox();
			});

			$(this.getScanningTable()).on('click', this.checkboxSelector, {instance: this}, function(event) {
				self.syncCategoryHeaderCheckbox( $(this) );
				self.syncTableHeaderCheckbox();
				self.enablePhpScan($(this));
			});

			$(this.getScanningTable()).on('click', this.filterSelector, {instance: this}, function(event) {
				event.preventDefault();
				var sectionType = $(this).closest('tr').attr('data-togglecategory');
				event.data.instance.toggleItems(self.scanningSection.getTableSectionByType(sectionType), $(this));
			});

			$(this.getScanningTable()).on('click', this.toggleSubitemsListSelector, function(event) {
				event.preventDefault();
				var isOpened = $(this).closest('.item').hasClass('show-domains-list');

				if(isOpened) {
					$(this).closest('.item').removeClass('show-domains-list');
					$(this).closest('tr').find('[data-domainListItem]').css('display', 'none');
				} else {
					$(this).closest('.item').addClass('show-domains-list');
					$(this).closest('tr').find('[data-domainListItem]').css('display', 'block');
				}

				$(this).text((isOpened) ? $(this).attr('data-showtext') : $(this).attr('data-hidetext'));
				$(this).closest('.toggle-list-items').toggleClass('opened');

				self.adjustDomainCellsHeight($(this));
			});
		},

		adjustDomainCellsHeight: function(trigger) {
			var root = trigger.closest('tr');
			var domainLabelCell = root.find('.domain-label-cell ul li');
			var domainInfoCells = [];
			root.find('.domain-info-cell ul').each(function() {
				domainInfoCells.push($(this).find('li'));
			});

			domainLabelCell.each(function(index) {
				if(index === 0) {
					return;
				}

				for(var i = 0; i < domainInfoCells.length; i++) {
					var height = jQuery(domainLabelCell[index]).outerHeight();
					jQuery(domainInfoCells[i][index]).outerHeight(height);
				}
			});
		},

		detachEvents: function() {
			var self = this;

			$(this.getScanningTable()).off('click', this.allTableItemsCheckboxSelector);
			$(this.getScanningTable()).off('click', this.allCategoryItemsCheckboxSelector);
			$(this.getScanningTable()).off('click', this.checkboxSelector);
			$(this.getScanningTable()).off('click', this.filterSelector);
			$(this.getScanningTable()).off('click', this.toggleSubitemsListSelector);
		},

		setAllFilterForInactiveFilters: function() {
			var filters = this.getScanningTable().find(".state-selector");
			var selectedItems = filters.find(".active");
			selectedItems.each(function() {
				if($(this).attr('data-status') !== 'inactive') {
					return;
				}
				$(this).closest('.state-selector').find('[data-status="all"]').trigger('click');
			});
		},

		toggleItems: function(scanningTable, triggerElement) {
			var status = triggerElement.data('status') ? triggerElement.data('status') : '';
			var statusesBox = triggerElement.closest('.state-selector');

			statusesBox.find('li').removeClass('active');
			triggerElement.addClass('active');

			if ('active' === status) {
				scanningTable.find('tr.item').hide();
				scanningTable.find('.active').show();
			} else if ('inactive' === status) {
				scanningTable.find('tr.item').show();
				scanningTable.find('.active').hide();
			} else if ('all' === status) {
				scanningTable.find('tr.item').show();
			}
		},

		enablePhpScan: function(items) {
			items.each(function() {
				jQuery(this).closest('.item').removeAttr('data-disablePhpScan');
			});
		},

		toggleCheckboxesWithCategoryCheckboxes: function(el, trigger) {
			var checkboxes = el.find('input:checkbox');
			checkboxes.prop('checked', trigger.prop('checked'));
			this.enablePhpScan(checkboxes);
		},

		toggleCategoryCheckboxes: function(trigger) {
			var type = trigger.closest('.category-header').attr('data-togglecategory');
			var categoryItems = this.getScanningTable().find('[data-itemcategory="' + type + '"]');
			categoryItems.find('input:checkbox').prop('checked', trigger.prop('checked'));
			this.enablePhpScan(categoryItems);
		},

		syncTableHeaderCheckbox: function() {
			var areAllChecked = true;
			$(this.getScanningTable()).find(this.allCategoryItemsCheckboxSelector).each(function() {
				if(!$(this).prop('checked')) {
					areAllChecked = false;
				}
			});
			$(this.getScanningTable()).find(this.allTableItemsCheckboxSelector).prop('checked', areAllChecked);
		},

		syncCategoryHeaderCheckbox: function(trigger) {
			var type = trigger.closest('.item').attr('data-itemcategory');
			var categoryItems = this.getScanningTable().find('[data-itemcategory="' + type + '"]');
			var areAllChecked = true;
			categoryItems.find('.checkbox-column :checkbox').each(function() {
				if(!$(this).prop('checked')) {
					areAllChecked = false;
				}
			});
			var categoryHeader = $(this.getScanningTable()).find('tbody .category-header').filter(function() {
				return $(this).attr('data-togglecategory') === type;
			});
			categoryHeader.find('.checkbox-column :checkbox').prop('checked', areAllChecked);
		},

		saveSelectedFilters: function() {
			var self = this;
			this.getScanningTable().find('[data-togglecategory]').each(function() {
				self.selectedFilterStatusByCategory[$(this).attr('data-togglecategory')] = $(this).find('.active').attr('data-status');
			});
		},

		saveOpenedDomainTabs: function() {
			var self = this;
			this.getScanningTable().find('.toggle-list-items').each(function() {
				if(!$(this).hasClass('opened')) {
					return;
				}

				self.openedDomainCmpLabels.push($(this).closest('tr').find('[data-component-name]').attr('data-component-name'));
			});
		},

		restoreSelectedFilters: function() {
			var self = this;
			this.getScanningTable().find('[data-togglecategory]').each(function() {
				 $(this).find('[data-status="' + self.selectedFilterStatusByCategory[$(this).attr('data-togglecategory')] + '"]').trigger('click');
			});
			this.selectedFilterStatusByCategory = {};
		},

		restoreOpenedDomainTabs: function() {
			var self = this;
			for(var i = 0; i < self.openedDomainCmpLabels.length; i++) {
				var domain = self.openedDomainCmpLabels[i];
				this.getScanningTable().find('[data-component-name="' + domain + '"]').closest('tr').find('.toggle-list-items a').trigger('click');
			}
			this.openedDomainCmpLabels = [];
		},
	};

	WPML_ST.ScanningCounter = function() {
		this.scannedDirsCount = 0;
		this.dirsToScanCount = 0;
		this.filesChunkToScanCount = 0;
		this.scannedFilesChunkCount = 0;
		this.totalStrings = 0;
		this.scannedFiles = [];
		this.stats = {};
		this.remainingToScanMoFilesCount = 0;
		this.scannedMoFilesCount = 0;
	};

	WPML_ST.ScanningCounter.prototype = {
		reset: function() {
			this.scannedDirsCount = 0;
			this.dirsToScanCount = 0;
			this.filesChunkToScanCount = 0;
			this.scannedFilesChunkCount = 0;
			this.totalStrings = 0;
			this.scannedFiles = [];
			this.stats = {};
			this.remainingToScanMoFilesCount = 0;
			this.scannedMoFilesCount = 0;
		}
	};

	WPML_ST.StringsScanning = function(scanningSection, counter) {
		this.numberOfFilesPerChunk = 50;
		this.triggerElement = {};
		this.elements = {};
		this.scheduledFileChunks = [];
		this.scanSuccessfulMessage = '';
		this.filesProcessedMessage = '';
		this.spinner = '.wpml-scanning-progress .spinner';
		this.progressMsg = '.wpml-scanning-progress-msg';
		this.statsMoSection = '.wpml-scanning-mo-results';
		this.statsSection = '.wpml-scanning-results';
		this.scanningProgressDialog = '.wpml-scanning-progress';
		this.dialogCloseSelector = '.ui-dialog-titlebar-close';
		this.scanningSection = scanningSection;
		this.counter = counter;

		jQuery('<div>', {
			class: 'wpml-scanning-mo-results',
		}).insertBefore(this.statsSection);

		this.init();
		this.attachEvents();
	};

	WPML_ST.StringsScanning.prototype = {
		init: function() {
			$( this.progressMsg ).hide();
		},

		attachEvents: function() {
			$(this.scanningSection.scanningButton).on( 'click', {instance: this}, function(event) {
				var instance = event.data.instance;
				event.preventDefault();
				instance.triggerElement = $( this );
				instance.scanAllSections();
			});
		},

		detachEvents: function() {
			$(this.scanningSection.scanningButton).off('click');
		},

		setFilters: function(filters) {
			this.filters = filters;
		},

		scanAllSections: function() {
			var self = this;
			this.triggerElement.prop('disabled', true);

			$( this.spinner ).addClass('is-active');
			$( this.progressMsg ).show();
			$( this.statsSection ).empty();
			$( this.scanningProgressDialog ).dialog({
				width: 750,
				maxHeight: 600,
				modal: true,
				open: function() {
					var dialog = $(self.scanningProgressDialog).closest('.ui-dialog');
					dialog.find('.spinner').css('display', 'none');
					dialog.addClass('wpml-st-string-scanning-modal-form');
					dialog.find('.wpml-scanning-progress').removeClass('wpml-scanning-progress-show-results');
					dialog.find(".ui-dialog-titlebar").css('display', 'none');
					// We need to set title as block element to put inside block element with icon on new line, so should replace span with div.
					var titleEl = dialog.find('.ui-dialog-title')[0];
					titleEl.outerHTML = titleEl.outerHTML.replace(/<span/g, '<div').replace(/<\/span/g, '</div');
					$(self.dialogCloseSelector).hide();

					if(dialog.find('.loader').length === 0) {
						dialog.prepend(jQuery('<div class="loader-wrap"><div class="loader"></div></div>'));
					}

					var fixDots = function() {
						var msg = dialog.find('.wpml-scanning-progress-msg');
						var text = msg.text();
						if(text.length < 2 ) {
							return;
						}
						var lastChar = text.charAt(text.length - 1);
						var beforeLastChar = text.charAt(text.length - 2);
						if(lastChar === '.' && beforeLastChar !== '.') {
							msg.text(text.replace(new RegExp('.$'), '...'));
						}
					};
					fixDots();
				},
				close: function() {
					jQuery(self.statsMoSection).html('');
					self.counter.reset();
				},
				closeOnEscape: false,
			});

			var themeSection = this.scanningSection.getTableSectionByType('theme');
			var pluginSection = this.scanningSection.getTableSectionByType('plugin');
			var otherSection = this.scanningSection.getTableSectionByType('other');

			var themesToScan = this.getItemsToScan(themeSection);
			var pluginsToScan = this.getItemsToScan(pluginSection);
			var otherToScan = this.getItemsToScan(otherSection);

			this.counter.dirsToScanCount += themesToScan.length;
			this.counter.dirsToScanCount += pluginsToScan.length;

			this.scan('theme', themesToScan);
			this.scan('plugin', pluginsToScan);

			// 2 cases to handle here:
			// 1) Other section is used only for MO/JSON files scan with translations, it has nothing to scan for initial strings.
			//    So, we should start from MO scanning for 'other' scan without any themes and plugins.
			// 2) Select all button to rescan updated 'MO' files with translations was clicked and no items were selected
			//    manually. In that case we want to launch MO files scan from here.
			if(themesToScan.length === 0 && pluginsToScan.length === 0) {
				this.maybeRescanMo();
			}
		},

		getItemsToScan: function(section) {
			var checkboxSel = 'input:checkbox:checked[data-component-name]';
			return section.find(checkboxSel).closest('[data-themepluginitem]').filter(function() {
				var maybeDisablePhpScanAttr = $(this).closest('.item').attr('data-disablePhpScan');
				return $(this).css('display') !== 'none' && maybeDisablePhpScanAttr !== '1';
			}).find(checkboxSel);
		},

		scan: function(sectionType, items) {
			if(items.length === 0) {
				return;
			}

			var type = sectionType;

			items.toArray().forEach(function(element) {
				var ajaxScanDirFiles = {};

				if ($('input[name="use_theme_plugin_domain"]').prop('checked')) {
					ajaxScanDirFiles.auto_text_domain = 1;
				}

				if ( -1 !== $( element ).data( 'component-name' ).search( 'mu-::-' ) ) {
					type = 'mu-plugin';
				}

				ajaxScanDirFiles[ type ] = $(element).val();
				ajaxScanDirFiles.action = this.scanningSection.getNonces()['folderAction'];
				ajaxScanDirFiles.nonce = this.scanningSection.getNonces()['folderNonce'];

				this.scanDirAjax( sectionType, $(element).val(), ajaxScanDirFiles );
			}, this);
		},

		scanDirAjax: function( sectionType, elementValue, ajaxScanDirFiles ) {
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				context: {
					sectionType: sectionType,
					elementValue: elementValue,
					instance: this,
				},
				data: ajaxScanDirFiles,
				success: this.onScanDirSuccess
			});
		},

		onScanDirSuccess: function( dirFilesResult ) {
			this.instance.processScanDirSuccess.call(this.instance, this.sectionType, this.elementValue, dirFilesResult);
		},

		processScanDirSuccess: function( sectionType, elementValue, dirFilesResult ) {
			var ajaxFilesChunk = {},
				dirFiles = dirFilesResult.data.files,
				filesChunks = [],
				index = 0;

			ajaxFilesChunk.action = this.scanningSection.getNonces()['filesAction'];
			ajaxFilesChunk.nonce = this.scanningSection.getNonces()['filesNonce'];
			ajaxFilesChunk[sectionType] = elementValue;

			while ( 0 < dirFiles.length ) {
				filesChunks.push( dirFiles.splice(0, this.numberOfFilesPerChunk) );
			}

			this.counter.filesChunkToScanCount = this.counter.filesChunkToScanCount + filesChunks.length;
			this.counter.scannedDirsCount++;

			this.scheduledFileChunks.push({
				ajaxFilesChunk: ajaxFilesChunk,
				filesChunks: filesChunks,
			});

			// This check will ensure that we will start scanning only after all Ajax requests to get files to scan have completed.
			if(this.counter.scannedDirsCount < this.counter.dirsToScanCount) {
				return;
			}

			if(this.counter.scannedFilesChunkCount === this.counter.filesChunkToScanCount) {
				$(this.statsSection).html( dirFilesResult.data.no_files_message );
				$(this.statsSection).show();
				this.maybeRescanMo();
				return;
			}

			this.scheduledFileChunks.forEach(function(scheduledFileChunk) {
				if(scheduledFileChunk.filesChunks.length == 0) {
					return;
				}

				this.scanFilesAjax(scheduledFileChunk.ajaxFilesChunk, scheduledFileChunk.filesChunks, index);
			}, this);
		},

		scanFilesAjax: function(ajax_files_chunk, files_chunks, index) {
			var self = this;

			if ( index === files_chunks.length - 1 ) {
				ajax_files_chunk.scan_mo_files = 1;
			}

			ajax_files_chunk.files = files_chunks[index];

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: ajax_files_chunk,
				context: this,
				success: $.proxy(this.scanFilesSuccess, this),
				error: function() {
					var origChunkFiles = files_chunks[index].slice();
					var smallerFilesChunks = [];

					while ( 0 < origChunkFiles.length ) {
						smallerFilesChunks.push( origChunkFiles.splice(0, 10) );
					}

					self.rescanFilesAjax(ajax_files_chunk, smallerFilesChunks, 0, files_chunks, index);
				},
			}).done(function() {
				this.scanFilesAjaxDone(files_chunks, ajax_files_chunk, index);
			});
		},

		rescanFilesAjax: function(ajax_files_chunk, files_chunks, index, orig_files_chunks, orig_index) {
			var self = this;
			ajax_files_chunk.files = files_chunks[index];

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: ajax_files_chunk,
				context: this,
				success: function(result) {
					var areAllSubchunksRescanned = false;
					if (index === files_chunks.length - 1) {
						areAllSubchunksRescanned = true;
					}

					self.scanFilesSuccess(result, areAllSubchunksRescanned);

					if (index === files_chunks.length - 1) {
						this.scanFilesAjaxDone(orig_files_chunks, ajax_files_chunk, orig_index);
					}
				},
			}).done(function() {
				this.rescanFilesAjaxDone(files_chunks, ajax_files_chunk, index, orig_files_chunks, orig_index);
			});
		},

		rescanFilesAjaxDone: function(files_chunks, ajax_files_chunk, index, orig_files_chunks, orig_index) {
			if (index < files_chunks.length - 1) {
				this.rescanFilesAjax(ajax_files_chunk, files_chunks, index + 1, orig_files_chunks, orig_index);
			}
		},

		scanFilesAjaxDone: function(files_chunks, ajax_files_chunk, index) {
			if (index < files_chunks.length - 1) {
				this.scanFilesAjax(ajax_files_chunk, files_chunks, index + 1);
			}
		},

		scanFilesSuccess: function(result, updateChunksCount) {
			var scanned_files_obj = result.data;
			var updateChunksCount = updateChunksCount || false;

			for(var domain in scanned_files_obj.stats) {
				if(typeof this.counter.stats[domain] !== 'undefined') {
					this.counter.stats[domain].count += parseInt( scanned_files_obj.stats[domain].count, 10 );
				}
				else {
					this.counter.stats[domain] = scanned_files_obj.stats[domain];
					this.counter.stats[domain].count = parseInt( this.counter.stats[domain].count, 10 );
				}
			}

			this.counter.scannedFiles = this.counter.scannedFiles.concat(scanned_files_obj.files_processed);
			this.counter.totalStrings = this.counter.totalStrings + scanned_files_obj.strings_found;
			this.scanSuccessfulMessage = scanned_files_obj.scan_successful_message;
			this.filesProcessedMessage = scanned_files_obj.files_processed_message;
			if(updateChunksCount) {
				this.counter.scannedFilesChunkCount++;
			}

			$(this.statsSection).html(this.renderStringsCounter());
			$(this.statsSection).show();

			if ( this.counter.scannedFilesChunkCount === this.counter.filesChunkToScanCount ) {
				this.scheduledFileChunks = [];
				this.updateStatsAjax();
			}
		},

		updateStatsAjax: function() {
			var self = this;

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					'action': 'wpml_action',
					'endpoint': this.scanningSection.getNonces()['updateStatsEndpoint'],
					'nonce': this.scanningSection.getNonces()['updateStatsNonce'],
					'data': JSON.stringify({
						'stats': this.counter.stats,
					}),
				},
				success: function() {
					var origScannedFiles = self.counter.scannedFiles.slice();
					var scannedFilesChunks = [];

					while ( 0 < origScannedFiles.length ) {
						scannedFilesChunks.push( origScannedFiles.splice(0, 500) );
					}

					self.updateHashAjax(scannedFilesChunks, 0);
				},
			});
		},

		updateHashAjax: function(scannedFilesChunks, index) {
			var self = this;
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					'action': this.scanningSection.getNonces()['updateHashAction'],
					'nonce': this.scanningSection.getNonces()['updateHashNonce'],
					'files': scannedFilesChunks[index],
				},
				success: function() {
					if(index === scannedFilesChunks.length - 1) {
						self.maybeRescanMo();
						return;
					}

					self.updateHashAjax(scannedFilesChunks, index + 1);
				},
			});
		},

		maybeRescanMo: function() {
			var self = this;
			if(!this.scanningSection.hasItemsToRescanMo()) {
				this.refreshScreen();
				return;
			}

			this.execRescanMo();
		},

		execRescanMo: function() {
			var self = this;
			$.ajax({
				type: 'POST',
				url: icl_vars.restUrl + '/wpml/st/v1/import_mo_strings',
				data: JSON.stringify(this.scanningSection.getItemsToRescanMo()),
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json',
					'X-WP-Nonce': icl_vars.restNonce,
				},
				success: function(data) {
					var total = parseInt(data.total, 10);
					var remaining = parseInt(data.remaining, 10);

					// If MO processing has stuck because of corrupted data in icl_strings table
					// we should avoid infinite loop here. We will show here error message to the user in future version (wpmldev-2537).
					if(remaining > 0 && self.counter.remainingToScanMoFilesCount === remaining) {
						self.onRescanMoComplete(data);
						return;
					}

					// Initialize only on first mo files batch scan success.
					if(self.counter.scannedMoFilesCount === 0 && total > 0) {
						self.counter.scannedMoFilesCount = total;
					}

					// Backend has a limit how many strings it can process per request, need continue to scan in such case.
					if(remaining > 0) {
						self.counter.remainingToScanMoFilesCount = remaining;
						self.execRescanMo();
						return;
					}

					self.onRescanMoComplete(data);
				}
			});
		},

		onRescanMoComplete: function(data) {
			jQuery(this.statsMoSection).html(this.renderMoFilesCount(data.scan_message, this.counter.scannedMoFilesCount) + '<br/><br/>');
			this.refreshScreen();
		},

		refreshScreen: function() {
			var self = this;
			self.filters.saveSelectedFilters();
			self.filters.saveOpenedDomainTabs();
			$.get(ajaxurl + '?action=load_localization_type_ui_html', function(data) {
				self.filters.detachEvents();
				self.scanningSection.detachEvents();
				self.detachEvents();
				$('#wpml-st-localization').replaceWith(data);
				self.scanningSection.reinit();
				self.attachEvents();
				self.filters.reinit();
				self.afterScreenReload();
			});
		},

		afterScreenReload: function() {
			var dialog = $(this.scanningProgressDialog).closest('.ui-dialog');
			dialog.find(".loader-wrap").remove();
			dialog.find(".ui-dialog-titlebar").css('display', 'block');
			dialog.find('.wpml-scanning-progress').addClass('wpml-scanning-progress-show-results');
			$(this.statsSection).html(this.renderScanningResults());
			$(this.statsSection).show();
			this.restoreUI();
		},

		renderScanningResults: function() {
			var text = '';

			var okMsg = this.scanSuccessfulMessage;
			var okMsgParts = okMsg.split(':');
			if(okMsgParts.length > 1) {
				okMsgParts[0] = '<b>' + okMsgParts[0] + '</b>';
				okMsg = okMsgParts.join(':');
			}

			text = text + okMsg.replace('%s', this.counter.totalStrings) + '<br />';

			if ( this.filesProcessedMessage ) {
				text = text + this.filesProcessedMessage + '<br /><br />';

				if ( this.counter.scannedFiles ) {
					text = text + '<div class="ui-dialog-scrollable-content">';
					$.each(this.counter.scannedFiles, function (index, element) {
						text = text + '<div class="result-row">' + element + '</div>';
					});
					text = text + '</div>';
				}
			}

			return text;
		},

		renderMoFilesCount: function(msg, count) {
			var msgParts = msg.split('%s');
			var prefix = msgParts[0];
			var postfix = msgParts[1];
			var postfixParts = postfix.split('.');
			var postfixStart = postfixParts[0];
			var postfixEnd = postfixParts[1];
			postfixParts.shift();
			postfixParts.shift();
			msg = prefix + '<b>%s' + postfixStart + '</b>.' + postfixEnd + '.' + postfixParts.join('.');

			return 'WPML' + msg.replace('%s', count).split('WPML')[1];
		},

		renderStringsCounter: function() {
			var text = 'WPML' + this.scanSuccessfulMessage.replace('%s', this.counter.totalStrings).split('WPML')[1];
			var parts = text.split('.');
			var html = '';
			for(var i = 0; i < parts.length; i++) {
				if(i === parts.length - 1) {
					html += parts[i];
				} else {
					html += parts[i] + '.<br/>';
				}
			}
			return html;
		},

		restoreUI: function() {
			$(this.spinner).removeClass('is-active');
			this.triggerElement.prop('disabled', false);
			$( this.progressMsg ).hide();
			this.allowClosingDialog();

			this.counter.reset();
			this.recenterModalDialogInViewport();
		},

		recenterModalDialogInViewport: function() {
			var bodyHeight = jQuery('#wpbody-content').outerHeight();
			var dialogHeight = jQuery('.ui-dialog').outerHeight();
			var windowHeight = jQuery(window).height();
			var dialogScrollTopOffset = window.pageYOffset || document.documentElement.scrollTop;
			var dialogViewportTopOffset = (windowHeight / 2) - (dialogHeight / 2);
			jQuery('.ui-dialog').css('top', (dialogScrollTopOffset + dialogViewportTopOffset) + 'px');
		},

		allowClosingDialog: function() {
			$(this.dialogCloseSelector).show();
			$(this.scanningProgressDialog).dialog('option', 'closeOnEscape', true);
			$(this.scanningProgressDialog).closest('.ui-dialog').focus();
		}
	};

	WPML_ST.AutoScan = function( groups ) {
		this.groups = groups;
		this.scanButton = $('#wpml_theme_plugin_localization_scan');
	};

	WPML_ST.AutoScan.prototype = {
		init: function() {
			if ( this.shouldRunAutoScan() ) {
				for (var group in this.groups) {
					if (this.groups.hasOwnProperty(group)) {
						this.selectItems( this.groups[group] );
						this.scan( this.scanButton );
					}
				}
			}
		},

		selectItems: function( group ) {
			group.forEach(function(item){
				var input = $( 'input[value="' + item + '"]' );
				if (!input.prop('checked')) {
					input.trigger('click');
				}
			});
		},

        scan: function () {
            this.scanButton.click();
        },

        shouldRunAutoScan: function () {
            return '' !== this.groups[0] && (-1 !== location.href.search('action=scan_from_notice') || -1 !== location.href.search('action=scan_active_items'))
        }
    };

    $(function () {
    	var scanningSection = new WPML_ST.ScanningSection();
        var auto_scan_type = wpml_active_plugins_themes;
        var counter = new WPML_ST.ScanningCounter();

        if (-1 !== location.href.search('action=scan_from_notice')) {
            auto_scan_type = wpml_groups_to_scan;
        }

		var stringsScanning = new WPML_ST.StringsScanning(scanningSection, counter);
		var themePluginFilter = new WPML_ST.ThemePluginFilter(scanningSection);
		scanningSection.setFilters(themePluginFilter);
		stringsScanning.setFilters(themePluginFilter);

		var autoScan = new WPML_ST.AutoScan( auto_scan_type );
		autoScan.init();
	});
});
