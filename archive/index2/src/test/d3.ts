import * as D3 from 'd3'
import * as Shape from 'd3-shape'
import * as Scale from 'd3-scale'
import * as Axis from 'd3-axis'
import * as Selection from 'd3-selection'
import * as TimeFormat from 'd3-time-format'
import * as Fetch from 'd3-fetch'
import * as jQuery from 'jquery'
import 'K'
// import * as axios from 'axios'
import Axios from 'axios'
Selection.local().set
Axios.defaults

jQuery("div").eq(1)


Object.defineProperty(window, "d3", {
    enumerable: true,
    configurable: false,
    writable: false,
    value: D3
});
let dataAry = [];
let i = 20;
while (i--) dataAry.push(i);

let formField = ["name", "age", "id"];

Object.defineProperties(window, {
    dataAry: {
        value: dataAry
    },
    formField: {
        value: formField
    }
});

// window.D3 = D3;
// Object.defineProperty(window, "D3", {
//     configurable: false,
//     writable: false,
//     enumerable: true,
//     value: D3
// });

// var data: [number, number][] = [[1, 2], [2, 6], [3, 0]];
//     [10, 1],
//     [11, -10],
//     [12, 0],
//     [13, 111],
//     [14, 2],
// ];
// let line = D3.line();
// i = 10;
// while (i--) {
//     D3.select("body").append("div");
// }



// D3.select("body").append("svg").append("path").attr("d", line(data)).attr("stroke", "blue").attr("fill", "none");

// D3.selectAll("div").append(d=>document.createElement("div"))

// let data = [1, 2, 3, 4, 5, 6];
// let select = D3.selectAll("div").data(data)
// select.append(function (d, i, g) {
//     console.log(this, d, i, g);
//     let p = document.createElement("p");
//     p.innerText = d + "";
//     return p;
// });
// select.exit().append(function (d, i, g) {
//     console.log(this, d, i, g);
//     let p = document.createElement("p");
//     p.innerText = "null";
//     return p;
// });

let tableDate = [
    ["编号", "姓名", "年龄"],
    [1, "h", 108],
    [2, "z", 1108],
    [3, "l", 18]
];
// let table = D3.select("body")
//     .append("table");

// table.append("thead").append("th").selectAll("td").data(tableDate[0]).enter().append("td").text(d => d);
// table.append("tbody").selectAll("tr")
// .data(tableDate.slice(1)).enter().append("tr").selectAll("td").data(d => d).enter().append("td").text(d => d).remove();

// D3.select("th > td").data(tableDate[0]).enter().append(function (d, i, g) {
//     let td = document.createElement("td");
//     td.innerText = d + "";
//     return td;
// }).filter


// let pie = D3.pie();
// pie([1, 2, 3, 4, 5])

// D3.scaleLinear()

// class Arg implements D3.AxisScale<1 | 2 | 3 | 4 | 5>
function a(x: number) {
    return x ** 2;
}
// a.domain = () => [1, 5, 10, 25, 30, 40, 50, 60, 70, 80, 90, 100];
a.domain = () => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
a.range = () => [0, 100];
a.copy = function () { return this; }

const axis = Axis.axisRight(a);
// D3.select("svg").style("margin-top", "55px").style("width", "100vw").style("height", "100vh");
// let b = { domain: () => [1, 2, 3, 4, 5], range: () => [10, 20], copy: function { return this; }}
axis.tickSizeInner(100);
axis(Selection.select("body").append("svg"));

axis.tickValues
Object.defineProperty(window, "axis", {
    value: axis
});

TimeFormat.utcParse
TimeFormat.timeFormatLocale
TimeFormat.timeFormatDefaultLocale

Shape.lineRadial()

Shape.arc();
Axis.axisTop(Scale.scaleLinear().domain([30, 60]));
Selection.select("body").data([]).datum()
Scale.scaleLinear().ticks
Fetch.json

