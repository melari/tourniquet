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
  result = ""
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
  return url if url.startsWith("http")
  __APP_NAMESPACE + url

@id = (id) ->
  $("##{id}")

@value_of = (eid) ->
  e = id(eid)
  if e.attr("type") == "checkbox"
    return if e.is(":checked") then 1 else 0
  e.val()

@set_html = (eid, value) ->
  id(eid).html(value)

@append_html = (eid, value) ->
  id(eid).html(id(eid).html() + value)

@redirect = (url, params) ->
  if params?
    url += generate_query_string(params)

  window.location = url

@post_redirect = (url, params) ->
  form = document.createElement("form")
  form.setAttribute("method", "post")
  form.setAttribute("action", url)
  for key, value of params
    if params.hasOwnProperty key
      hidden_field = document.createElement("input")
      hidden_field.setAttribute("type", "hidden")
      hidden_field.setAttribute("name", key)
      hidden_field.setAttribute("value", value)
      form.appendChild(hidden_field)
  document.body.appendChild(form)
  form.submit()

@on_ready = (func) ->
  $(document).ready(->
    func.call(window)
  )

@javascript_form = (lambda) ->
  lambda()
  false
