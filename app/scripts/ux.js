'use strict';

//Update the PAY button with the full amount
function updatePaymentAmount(amount){
  return +amount + 15;
}

//Bind onchange for amount to updatePaymentAmount
var amountInput = document.getElementById('amount');
amountInput.addEventListener('change', function(){
  var newAmount = updatePaymentAmount(amountInput.value);
  console.log(newAmount);
  document.getElementById('span-amount').textContent = newAmount;
  document.getElementById('total-amount').value = newAmount;
});

//Augment the DOM to add remove functions!
Element.prototype.remove = function() {
    this.parentElement.removeChild(this);
}
NodeList.prototype.remove = HTMLCollection.prototype.remove = function() {
    for(var i = this.length - 1; i >= 0; i--) {
        if(this[i] && this[i].parentElement) {
            this[i].parentElement.removeChild(this[i]);
        }
    }
}
