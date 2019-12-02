var _rollbarConfig = {
  accessToken: '{{ ROLLBAR_CLIENT_SIDE_ACCESS_TOKEN }}',
  captureUncaught: true,
  captureUnhandledRejections: true,
  payload: {
    environment: '{{ WP_ENV }}'
  }
};