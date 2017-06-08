'use strict';

var favicons = require("favicons"),
  source = 'app/images/favicon.png',
  configuration = {
    appName: "DAZSER Payment Portal",
    appDescription: "This application lets customers pay by credit card",
    developerName: "Kyle McNally",
    developerURL: "https://www.dazser.com/",
    background: "#000000",
    path: "dist/favicons/",
    url: "https://pay.dazser.com/",
    display: "standalone",
    orientation: "portrait",
    version: 1.0,
    logging: true,
    online: false,
    html: "favicons.html",
    pipeHTML: false,
    replace: false,
    icons: {
      android: true,              // Create Android homescreen icon. `boolean`
      appleIcon: true,            // Create Apple touch icons. `boolean`
      appleStartup: false,         // Create Apple startup images. `boolean`
      coast: false,                // Create Opera Coast icon. `boolean`
      favicons: true,             // Create regular favicons. `boolean`
      firefox: false,              // Create Firefox OS icons. `boolean`
      twitter: false,              // Create Twitter Summary Card image. `boolean`
      windows: true,              // Create Windows 8 tile icons. `boolean`
      yandex: false                // Create Yandex browser icon. `boolean`
    }
  },
  callback = function(error, response){
    if (error) {
      console.error(error.status);  // HTTP error code (e.g. `200`) or `null`
      console.error(error.name);    // Error name e.g. "API Error"
      console.error(error.message); // Error description e.g. "An unknown error has occurred"
      return;
    }
    //console.log(response.images);   // Array of { name: string, contents: <buffer> }
    //console.log(response.files);    // Array of { name: string, contents: <string> }
    //console.log(response.html);     // Array of strings (html elements)
  };

favicons(source, configuration, callback);
