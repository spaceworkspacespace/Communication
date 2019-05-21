// import { markdown, parse } from 'markdown'
import { Converter } from 'showdown'

// var Markdown = require("markdown");

let edit = document.createElement("textarea");
let preview = document.createElement("div");
let conver = new Converter();

conver.setFlavor("github");
conver.setOption("tasklists", true);
conver.setOption("table", true);
conver.setOption("strikethrough", true);
conver.setOption("requireSpaceBeforeHeadingText", true);
conver.setOption("simplifiedAutoLink", true);
conver.setOption("parseImgDimensions", true);
conver.setOption("prefixHeaderId", true);

edit.addEventListener("input", function (event) {
    let target: HTMLTextAreaElement = <HTMLTextAreaElement>event.target;
    preview.innerHTML = conver.makeHtml(target.value);
});

document.body.appendChild(edit);
document.body.appendChild(preview);
// document.body.setAttribute("style", "display: flex; flex-direction: row; jsutify-content: ")
