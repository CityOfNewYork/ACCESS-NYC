module.exports = function log() {
	var method = 'log';

	if (arguments[0] in window.console) {
		method = Array.prototype.shift.apply(arguments);
	}

	log.history = log.history || [];
	log.history.push(arguments);

	if (window.console && this.debug) {
		window.console[method].apply(window.console, arguments);
	}
};
