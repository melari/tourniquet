String.prototype.startsWith = (str) ->
  @lastIndexOf(str, 0) == 0


@remote_call = (url, params, callback, error_callback) ->
  url += generate_query_string(params)

  request = new XMLHttpRequest
  request.open "GET", url, true
  request.onreadystatechange = ->
    if request.readyState == 4
      if request.status == 200
        callback(request.responseText)
      else
        error_callback(request.status, request.responseText)
  request.send()

@remote_post_call = (url, params, callback, error_callback) ->
  request = new XMLHttpRequest
  request.open "POST", url, true
  request.setRequestHeader "Content-type", "application/x-www-form-urlencoded"
  request.onreadystatechange = ->
    if request.readyState == 4
      if request.status == 200
        callback(request.responseText)
      else
        error_callback(request.status, request.responseText)
  request.send(@generate_query_string(params, true))

@remote_file_upload = (url, file, params, callback, progress_handler, error_callback) ->
  url += generate_query_string(params)

  request = new XMLHttpRequest
  request.upload.addEventListener('progress', progress_handler)
  request.open "POST", url, true
  request.onreadystatechange = ->
    if request.readyState == 4
      if request.status == 200
        callback(request.responseText)
      else
        error_callback(request.status, request.responseText)
  form_data = new FormData()
  form_data.append("file", file)
  request.send(form_data)

@generate_query_string = (params, exclude_question) ->
  result = ""
  result = "?" unless exclude_question
  for key, value of params
    if params.hasOwnProperty key
      result += "#{encodeURIComponent(key)}=#{encodeURIComponent(value)}&"
  result.slice(0, -1)


@debounce_timers = {}
@debounce = (id, callback, length = 500) ->
  if debounce_timers[id]?
    clearTimeout(debounce_timers[id])
  debounce_timers[id] = setTimeout(callback, length)

@url_for = (url) ->
  return url if url.startsWith("http")
  __APP_NAMESPACE + url

@select = (param) ->
  $(param)

@id = (id) ->
  @select("##{id}")

@value_of = (eid) ->
  e = id(eid)
  if e.attr("type") == "checkbox"
    return if e.is(":checked") then 1 else 0
  e.val()

@set_value = (eid, value) ->
  e = id(eid)
  if e.attr("type") == "checkbox"
    e.prop('checked', value)
  else
    e.val(value)

@set_html = (eid, value) ->
  id(eid).html(value)

@append_html = (eid, value) ->
  id(eid).append(value)

@show = (element_id) ->
  @id(element_id).css("display", "block")

@hide = (element_id) ->
  @id(element_id).css("display", "none")

@toggle = (element_id) ->
  if @id(element_id).css("display") != "none"
    @hide(element_id)
  else
    @show(element_id)

@remove = (element_id) ->
  @id(element_id).remove()

@redirect = (url, params) ->
  if params?
    url += generate_query_string(params)

  window.location = url

@remove_newlines = (string) ->
  string.replace(/(\r\n|\n|\r)/gm,"") if string.replace

@post_redirect = (url, params) ->
  form = document.createElement("form")
  form.setAttribute("method", "post")
  form.setAttribute("action", url)
  for key, value of params
    if params.hasOwnProperty key
      hidden_field = document.createElement("input")
      hidden_field.setAttribute("type", "hidden")
      hidden_field.setAttribute("name", @remove_newlines(key))
      hidden_field.setAttribute("value", @remove_newlines(value))
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
