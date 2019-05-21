

(async function () {
    let m: MediaDevices = navigator.mediaDevices;

    let stream: MediaStream = await m.getUserMedia({ audio: true, video: true });
    let peerConn: RTCPeerConnection = new RTCPeerConnection();
    let offer: RTCSessionDescriptionInit = await peerConn.createOffer();
    peerConn.setLocalDescription(offer);
})();