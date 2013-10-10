var DLN = window.DLN || {};
DLN.Behaviors = {
};
DLN.LoadBehavior = function(context){
  if(context === undefined){
    context = $(document);
}
context.find("*[data-behavior]").each(function(){
  var that = $(this);
  var behaviors = that.attr('data-behavior');
  $.each(behaviors.split(" "), function(index,behaviorName){
    try {
      var BehaviorClass = DLN.Behaviors[behaviorName];
      var initializedBehavior = new BehaviorClass(that);
    }
    catch(e){
      // No Operation
    }
  });
 });
};
DLN.onReady = function(){
  DLN.LoadBehavior();
};
$(document).ready(function(){
  DLN.onReady();
});
