import * as Tween from '@tweenjs/tween.js';
import { Parabola } from './parabola'



let convas = document.createElement("canvas");
convas.height = 1920;
convas.width = 1080;
// convas.setAttribute("style", "width: 100vw; height: 100vh;");
convas.innerHTML = "您的浏览器不支持此功能.";
document.body.appendChild(convas);
let ctx = convas.getContext("2d");

// convas.addEventListener("mouseover", () => {
// });
// convas.addEventListener("mouseout", () => {

// });
ctx.fillStyle = "blue";
ctx.strokeStyle = "green";

ctx.beginPath();
ctx.arc(50, 50, 15, 0, 2 * Math.PI);
ctx.stroke();

let parabola = new Parabola({ v0: 20, g: 3, r: 45/360*2*Math.PI });
let timer: number = null;
let count = 0;
let zero = { x: 100, y: 100 };

ctx.moveTo(zero.x, zero.y);
timer = window.setInterval(() => {
    if (++count > 100) {
        clearInterval(timer);
        return;
    }
    let coords = parabola.obtainCoords(count);
    coords.x += zero.x;
    coords.y = -coords.y;
    coords.y += zero.y;
    console.log("t: " + count, "coords: ", coords);
    // ctx.lineTo(-coords.y/10, coords.x);
    ctx.lineTo(coords.x, coords.y);
    ctx.stroke();
}, 100);

