const { execSync } = require('child_process');
const webpackConfig = require('../../webpack.config');
const domain = "wpml";

const files = Object.keys( webpackConfig.entry );

files.forEach((file) => {
  const fileName = file + '.js'
  const output = 'languages/pot/wpml-wpml-' + fileName.replace(".js", ".pot");
  const command = `wp i18n make-pot ./public/js ${output} --include="${fileName}" --no-location --domain="${domain}" --skip-php`;
  try {
    console.log(`Running command: ${command}`);
    execSync(command, { stdio: 'inherit' });
  } catch (error) {
    console.error(`Error executing command for ${fileName}:`, error);
  }
});
