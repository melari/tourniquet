# Makes a GET AJAX call to the give url. Will pass the result to the given callback
# on completion of the call. An array of params can be supplied which will be added
# as a query string.
@remote_call = (url, callback, params) ->
  url = @url_for(url)
  if params?
    url += generate_query_string(params)

  request = new XMLHttpRequest
  request.open "GET", url, true
  request.onreadystatechange = ->
    if request.readyState == 4 && request.status == 200
      callback(request.responseText)

  request.send()


@generate_query_string = (params) ->
  result = "?"
  for key, value of params
    if params.hasOwnProperty key
      result += "#{encodeURIComponent(key)}=#{encodeURIComponent(value)}&"
  result.slice(0, -1)


@debounce_timers = {}
@debounce = (id, callback) ->
  if debounce_timers[id]?
    clearTimeout(debounce_timers[id])
  debounce_timers[id] = setTimeout(callback, 500)

@url_for = (url) ->
  __APP_NAMESPACE + url

@id = (id) ->
  $("##{id}")

@value_of = (eid) ->
  id(eid).val()

@set_html = (eid, value) ->
  id(eid).html(value)

@redirect = (url, params) ->
  url = __APP_NAMESPACE + url
  if params?
    url += generate_query_string(params)

  window.location = url
