console.log('\'Allo \'Allo!');

function updatePaymentType(paymentType){
    //This function takes a payment type and unhides the correct div
    if(paymentType === 'credit'){
      console.log('Credit');
      document.getElementById('credit-card').style.display = 'block';
      document.getElementById('ach').style.display = 'none';
    } else if(paymentType === 'ach'){
      console.log('ACH');
      document.getElementById('credit-card').style.display = 'none';
      document.getElementById('ach').style.display = 'block';
    } else {
      console.log('Bad Payment type');
      document.getElementById('credit-card').style.display = 'none';
      document.getElementById('ach').style.display = 'none';
    }
}

//Run it on document load
//Find the value of Payment type radio
var formElements = document.getElementById('payment-form').elements;
var radios = formElements['payment-type'];

updatePaymentType(radios.value);

//Then bind it to the radio buttons
for(var i = 0; i < radios.length; i++){
  radios[i].onclick = function(){
    updatePaymentType(this.value);
  }
}
