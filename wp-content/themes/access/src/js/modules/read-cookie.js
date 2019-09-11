/**
* Reads a cookie and returns the value
* @param {string} cookieName - Name of the cookie
* @param {string} cookie - Full list of cookies
* @return {string} - Value of cookie; undefined if cookie does not exist
*/
export default function(cookieName, cookie) {
  return (
    RegExp('(?:^|; )' + cookieName + '=([^;]*)').exec(cookie) || []
  ).pop();
}
