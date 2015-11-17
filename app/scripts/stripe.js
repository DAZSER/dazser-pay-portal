'use strict';

//https://stripe.com/docs/tutorials/forms

//Set my publishable key
Stripe.setPublishableKey('pk_test_cZSmFhp4VdaBYUOCq0eOsS1r');

var errorSpan = document.getElementById('error');
errorSpan.textContent = 'response.error.message';
errorSpan.display = 'block';

//This function is the Credit card response handler
//.createToken sends the form, along with a promise, the function is
//called when the promise is fulfilled
function stripeResponseCreditHandler(status, response){
  var form = document.getElementById('payment-form');

  if(response.error) {
    // Show the errors to the user
    document.getElementById('error').textContent = 'response.error.message';
  }
}

//Create the single use token by sending the CC information to Stripe
//This will return a token that I can store on my DB server.
//I can then use that token to charge the customer
var payButton = document.getElementById('pay-button');
payButton.addEventListener('click', function(){
  console.log('Pay button clicked');

  //Disable the button after click
  payButton.disabled = true;

  //Get the value of the Radio button
  var form = document.getElementById('payment-form');
  var radios = form.elements['payment-type'];
  if(radios.value === 'credit'){
    //Run Stripe code for Credit Cards
    Stripe.card.createToken(form, stripeResponseCreditHandler);
  } else if (radios.value === 'ach') {
    //Run Stripe code for ACH
  } else {
    //Invalid Radio value
  }

  //Disable normal form flow
  return false;
});
