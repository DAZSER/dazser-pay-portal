'use strict';

//This will setup and bind all input fields for validation purposes

//Email is validated within HTML5
//Invoice number is validated within HTML5
//Amount is validated within HTML5

var cardNumber = document.getElementById('card-number');
var cardNumberIcon = document.getElementById('input-icon-card-number');
cardNumber.addEventListener('blur', function(){
  //validate the cardNumber
  if(!Stripe.card.validateCardNumber(this.value)){
    //INVALID!!
    console.log('Invalid card');
    cardNumber.parentElement.className += ' is-invalid';
    document.getElementById('card-error').textContent = 'Bad Card Number!';
    cardNumberIcon.className = 'input-icon fa fa-lg';
  } else {
    //Card is valid, set the class of the card type
    var cardType = Stripe.card.cardType(this.value);
    switch(cardType){
      case 'Visa':
        cardNumberIcon.className += ' fa-cc-visa';
        break;
      case 'MasterCard':
        cardNumberIcon.className += ' fa-cc-mastercard';
        break;
      case 'American Express':
        cardNumberIcon.className += ' fa-cc-amex';
        break;
      case 'Discover':
        cardNumberIcon.className += ' fa-cc-discover';
        break;
      case 'Diners Club':
        cardNumberIcon.className += ' fa-cc-diners-club';
        break;
      case 'JCB':
        cardNumberIcon.className += ' fa-cc-jcb';
        break;
      case 'Unknown':
      default:
        console.log('Valid, but unknown card');
    }

  }
});

//Code for Expires validate
var expiresMonth = document.getElementById('expires-month');
var expiresYear = document.getElementById('expires-year');
function checkExpiry(month, year){
  if((month === '' && year !== '') || (month !== '' && year === '')){
    console.log('Only one is filled out');
  } else if(month !== '' && year !== ''){
    if(!Stripe.card.validateExpiry(month, year)){
      //Validate failed!
      console.log('Invalid Expiry Date');
      expiresMonth.parentElement.className += ' is-invalid';
      document.getElementById('expires-month-error').textContent = 'Bad Expiration Date!';
    } else {
      console.log('Valid Expiry!');
    }
  } else {
    //can't happen
  }
}
expiresMonth.addEventListener('blur', function(){
  checkExpiry(expiresMonth.value, expiresYear.value);
});
expiresYear.addEventListener('blur', function(){
  checkExpiry(expiresMonth.value, expiresYear.value);
});

//CVC Validate
var cvc = document.getElementById('cvc');
cvc.addEventListener('blur', function(){
  //validate the CVC
  if(!Stripe.card.validateCVC(this.value)){
    //INVALID!
    cvc.parentElement.className += ' is-invalid';
    document.getElementById('cvc-error').textContent = 'Bad CVC Number!';
  }
});
