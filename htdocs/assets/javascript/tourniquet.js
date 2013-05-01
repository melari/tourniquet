// Generated by CoffeeScript 1.6.2
(function() {
  this.remote_call = function(url, params, callback) {
    var request;

    url += generate_query_string(params);
    request = new XMLHttpRequest;
    request.open("GET", url, true);
    request.onreadystatechange = function() {
      if (request.readyState === 4 && request.status === 200) {
        return callback(request.responseText);
      }
    };
    return request.send();
  };

  this.remote_post_call = function(url, params, callback) {
    var request;

    request = new XMLHttpRequest;
    request.open("POST", url, true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.onreadystatechange = function() {
      if (request.readyState === 4 && request.status === 200) {
        return callback(request.responseText);
      }
    };
    return request.send(this.generate_query_string(params, true));
  };

  this.generate_query_string = function(params, exclude_question) {
    var key, result, value;

    if (!exclude_question) {
      result = "?";
    }
    for (key in params) {
      value = params[key];
      if (params.hasOwnProperty(key)) {
        result += "" + (encodeURIComponent(key)) + "=" + (encodeURIComponent(value)) + "&";
      }
    }
    return result.slice(0, -1);
  };

  this.debounce_timers = {};

  this.debounce = function(id, callback) {
    if (debounce_timers[id] != null) {
      clearTimeout(debounce_timers[id]);
    }
    return debounce_timers[id] = setTimeout(callback, 500);
  };

  this.url_for = function(url) {
    return __APP_NAMESPACE + url;
  };

  this.id = function(id) {
    return $("#" + id);
  };

  this.value_of = function(eid) {
    return id(eid).val();
  };

  this.set_html = function(eid, value) {
    return id(eid).html(value);
  };

  this.redirect = function(url, params) {
    url = __APP_NAMESPACE + url;
    if (params != null) {
      url += generate_query_string(params);
    }
    return window.location = url;
  };

}).call(this);
