'use strict';

//https://stripe.com/docs/tutorials/forms

//Grab a handler for the pay button globally
var payButton = document.getElementById('pay-button');

//This function will switch the submit button between submittable and submitting
function payButtonStateChanger(state){
  if(state === 'submittable'){
    //reset paybutton to defaults
    payButton.disabled = false;

    document.getElementById('spinner').remove();

    var amountSpan = document.createElement('span');
    amountSpan.id = 'span-amount';

    payButton.textContent = 'Pay $';
    payButton.appendChild(amountSpan);

  } else if (state === 'submitting'){
    //change to disabled and spinner
    payButton.disabled = true;

    var spinner = document.createElement('i');
    spinner.className = 'fa fa-spinner fa-spin fa-lg';
    spinner.id = 'spinner';

    payButton.textContent = '';
    payButton.appendChild(spinner);
  }
}

//This function is the Credit card response handler
//.createToken sends the form, along with a promise, the function is
//called when the promise is fulfilled
function stripeResponseCreditHandler(status, response){
  var form = document.getElementById('payment-form');

  if(response.error) {
    // Show the errors to the user
    console.log(response);
    var errorElement = document.getElementById('error-holder');
    errorElement.textContent = response.error.message;
    errorElement.display = 'block';
    payButtonStateChanger('submittable');
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
    //Also attach the entire response object!
    var stripeResponse = document.createElement('input');
    stripeResponse.type = 'hidden';
    stripeResponse.name = 'stripeResponse';
    stripeResponse.value = JSON.stringify(response);
    form.appendChild(stripeResponse);

    //Add the action for the form
    form.action = 'https://pay.dazser.com/process.php';
    form.method = 'POST';

    //Finally, submit the form!
    console.log('Submit!');
    form.submit();
  }
}

//Create the single use token by sending the CC information to Stripe
//This will return a token that I can store on my DB server.
//I can then use that token to charge the customer
payButton.addEventListener('click', function(event){
  console.log('Cancel form submit quickly');
  event.preventDefault();
});
payButton.addEventListener('click', function(){
  console.log('Pay button clicked');

  //Change the state of the pay button
  payButtonStateChanger('submitting');

  //Get the value of the Radio button
  var form = document.getElementById('payment-form');
  //var radios = form.elements['payment-type'];
  //if(radios.value === 'credit'){
    //Run Stripe code for Credit Cards
    Stripe.card.createToken(form, stripeResponseCreditHandler);
  //} else if (radios.value === 'ach') {
    //Run Stripe code for ACH
  //} else {
    //Invalid Radio value
  //}
});
