<form id="product_input_form" class="form-horizontal">
  <div class="tabbable">
    <ul class="nav nav-pills">
      <li class="active"><a href="#basictab" data-toggle="tab">Basic</a></li>
      <li><a href="#advancedtab" data-toggle="tab">Advanced</a></li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane active" id="basictab"></div>
      <div class="tab-pane" id="advancedtab"></div>
    </div>
  </div>
</form>


<script>
var product_id = "{$id}";
var basepath = "{$this->basePath()}";
{literal}
function paintControl(input, parent) {
  inputid = sanitizeProductInputId(input.id);
  markup = "<div class='control-group";
  if(!input.display) {
    markup += " hide";
  }
  markup += "' id='"+inputid+"'>";
  markup += "  <label class='control-label' for='"+inputid+"'>"+input.display_name+"</label>"
  markup += "  <div class='controls'>";
  if(input.resource_type == 'cloud_product_input') {
    markup += "    <select name='"+input.input_name+"'>";
    markup += renderCloudSelectOptions(input.values, input.default_value);
    markup += "    </select>";
  } else if (input.resource_type == 'instance_type_product_input') {
    markup += "    <select name='"+input.input_name+"'>";
    markup += renderCloudInputDependentSelectOptions(input.values, input.default_value, input.cloud_href);
    markup += "    </select>";
  } else if (input.resource_type == 'datacenter_product_input') {
    markup += "    <select name='"+input.input_name+"' multiple='multiple' data-postas='array'>";
    markup += renderCloudInputDependentSelectOptions(input.values, input.default_value, input.cloud_href);
    markup += "    </select>";
  } else if (input.resource_type == 'select_product_input') {
    markup += "    <select name='"+input.input_name+"'";
    if(input.multiselect) {
      markup += " multiple='multiple'";
    }
    markup +=" data-postas='array'>"
    $(input.options).each(function(k,option) {
      markup += "      <option value='"+option+"'";
      if($.inArray(option, input.default_value) != -1) {
        markup += " selected";
      }
      markup += ">"+option+"</option>"
    });
  } else {
    markup += "    <input type='text' name='"+input.input_name+"' value='"+input.default_value+"' />";
  }
  if(input.description) {
    markup += "<p class='text-info'><img src='"+basepath+"/images/info.png' />"+input.description+"</p>";
  }
  markup += "  </div>"
  markup += "</div>";
  parent.append($(markup));
}

function updateControl(dom, input) {
  var selects = $("select", dom);
  if(selects.length > 0 && (input.resource_type != 'cloud_product_input' & input.resource_type != 'select_product_input')) {
    selects.html(renderCloudInputDependentSelectOptions(input.values, input.default_value, input.cloud_href));
  }
  if(input.display) {
    dom.removeClass('hide');
  }
}

function renderCloudSelectOptions(options, default_value) {
  markup = "";
  $(options).each(function(k,v) {
    markup += "      <option value='"+v['href']+"'";
    if(v.value == default_value) {
      markup += " selected";
    }
    markup += ">"+v['name']+"</option>";
  });
  return markup;
}

function renderCloudInputDependentSelectOptions(options, default_value, cloud_href) {
  markup = "";
  $(options).each(function(k,v) {
    markup += "      <option value='"+v['href']+"'";
    $(default_value).each(function(k, cloud_href_hash) {
      if(cloud_href_hash['cloud_href'] == cloud_href) {
        if(!$.isArray(cloud_href_hash['resource_hrefs'])) {
          cloud_href_hash['resource_hrefs'] = [cloud_href_hash['resource_hrefs']];
        }
        $(cloud_href_hash['resource_hrefs']).each(function(k,this_href) {
          if(v['href'] == this_href) {
            markup += "selected"
          }
        });
      }
    });
    markup += ">"+v['name']+"</option>";
  });
  return markup;
}

function sanitizeProductInputId(id) {
  return id.replace(/[^a-zA-Z0-9_-]/g, '-');
}

function gofetch() {
  data = convertFormToKeyValuePairJson("#product_input_form");
  $.ajax({
    url: '/api/product/'+product_id+'/inputs',
    type: 'POST',
    data: data,
    success: function(data, status, jqXHR) {
      response_input_by_id_hash = {};
      existing_input_by_id_hash = {};
      $(data).each(function(k,v) {
        response_input_by_id_hash[sanitizeProductInputId(v.id)] = v;
      });
      $(".control-group").each(function(k,v) {
        existing_input_by_id_hash[$(v).attr('id')] = $(v);
      });

      // Start with hiding inputs that should not be here with the current
      // config
      if(!isEmpty(existing_input_by_id_hash)) {
        $.each(existing_input_by_id_hash,function(k,v) {
          if(isEmpty(v)) { return; }
          if(!(k in response_input_by_id_hash)) {
            $(v).addClass('hide');
          } else {
            updateControl(v, response_input_by_id_hash[k]);
          }
        });
      }

      if(!isEmpty(response_input_by_id_hash)) {
        $.each(response_input_by_id_hash, function(k,v) {
          if(!(k in existing_input_by_id_hash)) {
            if(v.advanced) {
              paintControl(v, $("#advancedtab"));
            } else {
              paintControl(v, $("#basictab"));
            }
          }
        });
      }
    }
  });
}

$(function() {
  gofetch();

  $("#product_input_form").on("change", "select", function(event) {
    gofetch();
  });
  $("#product_input_form").on("change", "input", function(event) {
    gofetch();
  });
});
{/literal}
</script>