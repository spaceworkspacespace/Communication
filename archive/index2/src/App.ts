
import { App as Desktop } from './pages/Desktop'
import { App as Mobile } from './pages/Mobile'

const LIB_SERVER = "https://im.5dx.ink/static/libs"

class App {
    public static runClass: "desktop" | "mobile";
    public run() {
        if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
            // $(window.document.head).append(`<link rel="stylesheet" href="/static/libs/layim-v3.8.0/css/layui.mobile.css">`);
            $(window.document.head).append(`<link rel="stylesheet" href="${LIB_SERVER}/layim-v3.8.0/css/layui.mobile.css">`);
            new Mobile().run();
            App.runClass = "mobile";
        } else {
            // $(window.document.head).append(`<link rel="stylesheet" href="/static/libs/layim-v3.8.0/css/layui.css">`);
            $(window.document.head).append(`<link rel="stylesheet" href="${LIB_SERVER}/layim-v3.8.0/css/layui.css">`);
            new Desktop().run();
            App.runClass = "desktop";
        }
    }
}

export {
    App
}