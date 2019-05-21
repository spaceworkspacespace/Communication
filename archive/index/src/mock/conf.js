
import { LINKS as L } from '@/conf/link'
import _ from 'lodash'

const PREFIX = "http://192.168.0.87:8080";
// const PREFIX = "";

const LINKS = mapValuesDeep(L, s => PREFIX + s);

function mapValuesDeep(obj, iterate) {
    function fun(obj) {
        if (typeof obj === "object") {
            return mapValuesDeep(obj, iterate);
        }
        return iterate(obj);
    }
    return _.mapValues(obj, fun);
}

export {
    LINKS
}