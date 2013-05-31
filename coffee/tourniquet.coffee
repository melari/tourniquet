String.prototype.startsWith = (str) ->
  @lastIndexOf(str, 0) == 0


@remote_call = (url, params, callback) ->
  url += generate_query_string(params)

  request = new XMLHttpRequest
  request.open "GET", url, true
  request.onreadystatechange = ->
    if request.readyState == 4 && request.status == 200
      callback(request.responseText)
  request.send()

@remote_post_call = (url, params, callback) ->
  request = new XMLHttpRequest
  request.open "POST", url, true
  request.setRequestHeader "Content-type", "application/x-www-form-urlencoded"
  request.onreadystatechange = ->
    if request.readyState == 4 && request.status == 200
      callback(request.responseText)
  request.send(@generate_query_string(params, true))

@generate_query_string = (params, exclude_question) ->
  result = "?" unless exclude_question
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

@on_ready = (func) ->
  $(document).ready(->
    func.call(window)
  )
