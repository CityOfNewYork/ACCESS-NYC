module.exports = function (app) {
	app.models = {base: require('./models/base.js')};
	app.collections = {base: require('./collections/base.js')};
	app.views = {base: require('./views/base.js')};
};
