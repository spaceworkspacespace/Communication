
import { App as Desktop } from './pages/Desktop'
import { App as Mobile } from './pages/Mobile'
import Axios from 'axios';

class App {
    public static runClass: "desktop" | "mobile";
    public run() {
        if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
            // $(window.document.head).append(`<link rel="stylesheet" href="/static/libs/layim-v3.8.0/css/layui.mobile.css">`);
            $(window.document.head).append(`<link rel="stylesheet" href="http://144.34.168.228/layim-v3.8.0/css/layui.mobile.css">`);
            new Mobile().run();
            App.runClass = "mobile";
        } else {
            // $(window.document.head).append(`<link rel="stylesheet" href="/static/libs/layim-v3.8.0/css/layui.css">`);
            $(window.document.head).append(`<link rel="stylesheet" href="http://144.34.168.228/layim-v3.8.0/css/layui.css">`);
            new Desktop().run();
            App.runClass = "desktop";
        }
    }
}

export {
    App
}