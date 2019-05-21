<style>
#x-webvideocall,
.x-webvideocall-video {
    height: auto;
    width: 100%;
    position: relative;
}
</style>

<template>
    <div id="x-webvideocall">
        <video class="x-webvideocall-video" autoplay="true"
            ref="videom">您的浏览器不支持视屏通话.</video>
        <video class="x-webvideocall-video" autoplay="true"
            ref="videoy">您的浏览器不支持视屏通话.</video>
    </div>
</template>
<script>
import layer from 'layer'

const NOT_SUPPORT = "";
const INIT_FAILURE = "功能初始化失败, 请稍后(或切换浏览器)重试~";

export default {
    data: function () {
        return {
            init: null,
            peerConnM: null,
            peerConnY: null,
            localStream: null,
            peerConnM: null,
            peerConnY: null,
        };
    },

    mounted: async function () {
        let stream = await navigator.mediaDevices
            .getUserMedia({ audio: true, video: true, facingMode: "user" });
        this.$refs.videom.srcObject = this.localStream = stream;

        let peerConnM = this.peerConnM = new RTCPeerConnection();
        peerConnM.addStream(stream);
        peerConnM.onicecandidate = e => {
            if (event.candidate) {
                this.peerConnY.addIceCandidate(event.candidate);
            }
        }

        let peerConnY = this.peerConnY = new RTCPeerConnection();
        peerConnY.onaddstream = event => {
            this.$refs.videoy.srcObject = event.stream;
        };
        peerConnY.onicecandidate = event => {
            if (event.candidate) {
                this.peerConnM.addIceCandidate(event.candidate);
            }
        }
        this.connect();
    },

    methods: {
        connect: async function () {
            let offer = await this.peerConnM.createOffer();
            await this.peerConnY.setRemoteDescription(offer);
            await this.peerConnM.setLocalDescription(offer);
            let answer = await this.peerConnY.createAnswer();
            await this.peerConnY.setLocalDescription(answer);
            await this.peerConnM.setRemoteDescription(answer);

        }
    }

}
</script>

