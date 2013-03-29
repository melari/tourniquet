/**
 * Makes a GET AJAX call to the give url. Will pass the result to the given callback
 * on completion of the call. An array of params can be supplied which will be added
 * as a query string.
**/
function remote_call(url, callback, params)
{
  url = __APP_NAMESPACE + url;
  if (params != null)
    url += generate_query_string(params);

  var request = new XMLHttpRequest();
  request.open("GET", url, true);
  request.onreadystatechange = function()
  {
    if (request.readyState == 4 && request.status == 200)
    {
      callback(request.responseText);
    }
  }
  request.send();
}

function generate_query_string(params)
{
  var result = "?";
  for (var key in params)
  {
    if (params.hasOwnProperty(key))
    {
      result += encodeURIComponent(key) + "=" + encodeURIComponent(params[key]) + "&";
    }
  }
  return result.slice(0, -1);
}

var debounce_timers = {};
function debounce(id, callback)
{
  if (debounce_timers[id] != null)
    clearTimeout(debounce_timers[id])
  debounce_timers[id] = setTimeout(callback, 500);
}
