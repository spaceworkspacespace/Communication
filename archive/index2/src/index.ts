
import 'babel-polyfill'
import axios from 'axios'
import layer from 'layer'
// import './mock/index'
// import { App as Desktop } from './pages/Desktop'
// import { App as Mobile } from './pages/Mobile'
// import * as $ from 'jquery'
import { App } from './App';

axios.defaults.baseURL = "https://im.5dx.ink"
// axios.defaults.baseURL = "http://192.168.0.218:1212"
// axios.defaults.baseURL = "http://127.0.0.1:9510"
// axios.defaults.baseURL = "http://chat.pybycl.com:1235";
// axios.defaults.baseURL = "http://192.168.0.80:1235";
axios.defaults.withCredentials = true;
axios.defaults.params = {
    "_ajax": true,
    // "_origin": "http://192.168.0.87:9520",
};

layer.config({
    zIndex: 21000000
});


function main() {
    new App().run();
}

main();
// import { GatewayImpl } from './gateway'

// Object.defineProperty(window, "Gateway", {
//     enumerable: true,
//     writable: false,
//     value: GatewayImpl,
//     configurable: false,
// });
// import { AESUtils } from './crypto'
// Object.defineProperty(window, "AESUtils", {
//     enumerable: true,
//     writable: false,
//     value: AESUtils,
//     configurable: false,
// });
