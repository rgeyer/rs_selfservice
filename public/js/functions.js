function timeout_func()
{
  var percent = $("#progressbar").progressbar('value');
  percent = (percent + 10) % 100;
  $("#progressbar").progressbar('value', percent);

  if ($("#progress-dialog").dialog('isOpen')) {
    setTimeout(timeout_func,1000);
  }
}

function open_message_dialog(title, content, action_btn_props) {
  var dialog = $('#message-dialog');
  console.log(content);
  var footer = $('.modal-footer', dialog);
  footer.children().remove();
  var modalbody = $('.modal-body', dialog);
  console.log(modalbody);
  modalbody.html(content);
  $('.modal-header > h3', dialog).text(title);
  $('#message-dialog').modal('show');
  footer.append('<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>');
  if(action_btn_props) {
    var actionbtn_markup = '<a class="btn btn-primary ajaxaction">';
    actionbtn_markup += action_btn_props['value'];
    actionbtn_markup += '</a>';
    var actionbtn_dom = $(actionbtn_markup);
    $.each(action_btn_props, function(k,v) {
      actionbtn_dom.attr(k,v);
    });
    footer.append(actionbtn_dom);
  }
}

function response_messages_to_content(messages) {
  content = "";
  $(messages).each(function(i, message) {
    content += "<p>"+message+"</p>";
  });
  return content;
}

function convertFormToKeyValuePairJson(selector) {
  serializedForm = $(selector).serializeArray();
  data = {};
  $.each(serializedForm, function(i, field) {
    if(data[field.name]) {
      if($.isArray(data[field.name])) {
        data[field.name] = data[field.name].concat(field.value);
      } else {
        oldval = data[field.name];
        data[field.name] = [oldval].concat(field.value);
      }
    } else {
      multiple = $("[name='"+field.name+"']", $(selector)).attr('data-postas');
      if(multiple) {
        data[field.name] = [field.value];
      } else {
        data[field.name] = field.value;
      }
    }
  });
  return data;
}

function isEmpty(obj) {
  for(var key in obj) {
    if(obj.hasOwnProperty(key)) {
      return false;
    }
  }
  return true;
}