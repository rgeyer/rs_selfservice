function timeout_func()
{
  var percent = $("#progressbar").progressbar('value');
  percent = (percent + 10) % 100;
  $("#progressbar").progressbar('value', percent);

  if ($("#progress-dialog").dialog('isOpen')) {
    setTimeout(timeout_func,1000);
  }
}

function open_message_dialog(height, width, title, content) {
  $('#message-dialog').html(content);
  $('#message-dialog').dialog({
    title: title,
    height: height,
    width: width
  });
  $('#message-dialog').dialog('open');
}

function response_messages_to_content(messages) {
  content = "";
  $(messages).each(function(i, message) {
    content += "<p>"+message+"</p>";
  });
  return content;
}