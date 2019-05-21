import * as Hammer from 'hammerjs'

var hammer = new Hammer(document.body, {});

hammer.on("press", function (event) {
    console.log(event);
});