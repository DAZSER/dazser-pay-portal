'use strict';

//https://stripe.com/docs/tutorials/forms

//Grab a handler for the pay button globally
var payButton = document.getElementById('pay-button');

//This function is the Credit card response handler
//.createToken sends the form, along with a promise, the function is
//called when the promise is fulfilled
function stripeResponseCreditHandler(status, response){
  var form = document.getElementById('payment-form');

  if(response.error) {
    // Show the errors to the user
    console.log(response);
    var errorSpan = document.getElementById('error');
    errorSpan.textContent = response.error.message;
    errorSpan.display = 'block';
    payButton.disabled = false;
  } else {
    //NO error!!! Add the token, then submit to my server
    //I will process the token with Stripe in order to charge the customer
    console.log(response);
    //Create the element
    var token = document.createElement('input');
    token.type = 'hidden';
    token.name = 'stripeToken';
    token.value = response.id;
    //And append it to the form!
    form.appendChild(token);
    form.submit();
  }
}

//Create the single use token by sending the CC information to Stripe
//This will return a token that I can store on my DB server.
//I can then use that token to charge the customer
payButton.addEventListener('click', function(){
  console.log('Pay button clicked');

  //Disable the button after click
  payButton.disabled = true;

  //Get the value of the Radio button
  var form = document.getElementById('payment-form');
  console.log(form);
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
