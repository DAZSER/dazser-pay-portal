'use strict';

//https://stripe.com/docs/elements

//Load my Stripe Key
var stripe = Stripe('pk_live_j6A6g7uGYYkLAwXzs1CKA8bH');
//var stripe = Stripe('pk_test_cZSmFhp4VdaBYUOCq0eOsS1r');
var elements = stripe.elements();

var card = elements.create('card');

card.mount('#card-element');

function createToken(){
  stripe.createToken(card).then(function(result){
    if (result.error) {
      //Inform the user of the error
      var errorElement = document.getElementById('error-holder');
      errorElement.textContent = result.error.message;
      errorElement.display = 'block';
      payButtonStateChanger('submittable');
    } else {
      // Attach the token to the form and send it to my server
      stripeResponseCreditHandler(result.token);
    }
  });
}

//This function is the Credit card response handler
//createToken sends the CC info and returns a promise, this function is
//called when the promise is fulfilled
function stripeResponseCreditHandler(token){
  //Add the token, then submit to my server
  //I will process the token with Stripe in order to charge the customer
  var form = document.getElementById('payment-form');
  //Create the element
  var tokenInput = document.createElement('input');
  tokenInput.type = 'hidden';
  tokenInput.name = 'stripeToken';
  tokenInput.value = JSON.stringify(token);
  //And append it to the form!
  form.appendChild(tokenInput);

  //Add the action for the form
  form.action = 'process.php';
  form.method = 'POST';

  //Finally, submit the form!
  form.submit();
}

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

//Create the single use token by sending the CC information to Stripe
//This will return a token that I can store on my DB server.
//I can then use that token to charge the customer
payButton.addEventListener('click', (event)=>{
  event.preventDefault();
  //Change the state of the pay button
  payButtonStateChanger('submitting');
  createToken();
});
