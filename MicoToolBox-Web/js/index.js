var islogin = false;
var userId = "";
var serviceToken = "";
var cUserId = "";

var HARDWARE = "";
var DEVICEID = "";
var SN = "";

var firmwares = [];
var currFirmware = undefined;
var currChoice = undefined;

var nodeSize = 0;
var currNodeIndex = 0;
var loginbtn = $('#loginBtn');
var refreshqrbtn = $('#refreshqrbtn');
var qrCodeContainer = document.getElementById("qrCodeContainer");
var userIdContainer = document.getElementById("userId");
var passwordContainer = document.getElementById("password");
var userIcon = document.getElementById("usericon");
var pwdIcon = document.getElementById("pwdicon");

$(document).ready(() => {
    getFirmwares();

    $('#pushBtn').click(function(e) {
        if(userId != "" && DEVICEID != "" && SN != "" && currFirmware != undefined && currChoice != undefined) {
            push(currFirmware.name, currFirmware.hardware, currFirmware.type, currFirmware.version, currFirmware.link, currFirmware.hash, currFirmware.extra, currFirmware.batch);
        } else {
            $('#TipsModalLabel').text('推送提示');
            $('#tipsModal .modal-footer .TipsCloseBtn').css({'display': 'inline-block'});
            $('#tipsModal .modal-body').text('至少有一个选项未选择，请完成前面的步骤再推送！');
            $('#tipsModal').modal('show');
        }
    });

    $('#refreshDevBtn').click(function(e) {
        if(!userId || !serviceToken){
            $('#TipsModalLabel').text('提示');
            $('#tipsModal .modal-footer .TipsCloseBtn').css({'display': 'inline-block'});
            $('#tipsModal .modal-body').text('请先登录');
            $('#tipsModal').modal('show');
        } else {
            refreshDevice();
        }
    });

    $('#tipsModal').on('hide.bs.modal', function () {
        $('#tipsModal .modal-footer .TipsCloseBtn').css({'display': 'none'});
        $('#tipsModal .modal-footer .tipsOkBtn').css({'display': 'none'});
        $('#tipsModal .modal-footer .pushNextBtn').css({'display': 'none'});
    });
});

function pwdlogin() {
    userId = $('#userId').val();
    let password = $('#password').val();

    var passwordMd5 = $.md5(password).toUpperCase();

    if(userId == "" || password == "") {
        $('#TipsModalLabel').text('登录提示');
        $('#tipsModal .modal-footer .TipsCloseBtn').css({'display': 'inline-block'});
        $('#tipsModal .modal-body').text('信息不能为空，请检查后重试！');
        $('#tipsModal').modal('show');
        return;
    }

    loginbtn.text('正在登录');
    loginbtn.attr({"disabled":"disabled"});

    post_url = './api/api2.php?action=login&userId=' + userId + '&password=' + passwordMd5;
    $.ajax({
        url: post_url,
        success: function(data) {
            if(isJSON(data) != true){
                $('#TipsModalLabel').text('登录错误 (错误码: formatError)');
                $('#tipsModal .modal-footer .TipsCloseBtn').css({'display': 'inline-block'});
                $('#tipsModal .modal-body').text('登录失败，请核对密码与账号输入是否正确!');
                $('#tipsModal').modal('show');

                loginbtn.text('登录');
                loginbtn.removeAttr("disabled");

                return;
            }

            serviceToken=(JSON.parse(data)).serviceToken;
            cUserId=(JSON.parse(data)).cUserId;
            refreshDevice();
            afterlogin();
        }
    });
}

function isJSON(str) {
    if (typeof str == 'string') {
        try {
            JSON.parse(str);
            return true;
        } catch(e) {
            return false;
        }
    }
}

function getDevices(arr) {
    var deviceTable = $('#deviceTable tbody');
    deviceTable.html('');

    let devices = arr;
    for(let i = 0; i < devices.length; i++) {
        let deviceItem = $(`
            <tr>
                <td>` + devices[i].name + `</td>
                <td>` + devices[i].serialNumber + `</td>
                <td>` + devices[i].hardware + `</td>
                <td>` + devices[i].version + `</td>
                <td>` + devices[i].Status + `</td>
                <td><button hardware="` + devices[i].hardware + `" deviceid="` + devices[i].deviceID + `" sn="` + devices[i].serialNumber + `" class="choiceBtn btn btn-default">选择</button></td>
            </tr>
        `);
        deviceTable.append(deviceItem);
    }

    $('.choiceBtn').click((e) => {
        let choiceBtn = $(e.target);

        if(currChoice != undefined) {
            currChoice.attr('class', 'choiceBtn btn btn-default');
            currChoice.text('选择');
        }
        choiceBtn.attr('class', 'choiceBtn btn btn-primary');
        choiceBtn.text('选中');
        currChoice = choiceBtn;

        deviceChoice(choiceBtn.attr('deviceid'), choiceBtn.attr('sn'), choiceBtn.attr('hardware'));
    });
}

function deviceChoice(deviceId, sn, hardware) {
    DEVICEID = deviceId;
    SN = sn;
    HARDWARE = hardware;
}

function getFirmwares() {
    var firmwareTable = $('#firmwareTable tbody');
    firmwareTable.html('');

    $.ajax({
        url: './data/firmwares.json',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            firmwares = data;
            for(let i = 0; i < firmwares.length; i++) {
                let firmwareItem = $(`
                    <tr>
                        <td>` + firmwares[i].name + `</td>
                        <td>` + firmwares[i].type + `</td>
                        <td>` + firmwares[i].hardware + `</td>
                        <td>` + firmwares[i].version + `</td>
                        <td><button index="` + i + `" class="firmwareBtn btn btn-default">选择</button></td>
                    </tr>
                `);
                firmwareTable.append(firmwareItem);
            }

            var currfirmware = undefined;
            $('.firmwareBtn').click((e) => {
                let firmwareBtn = $(e.target);
                if(currfirmware != undefined) {
                    currfirmware.attr('class', 'firmwareBtn btn btn-default');
                    currfirmware.text('选择');
                }
                firmwareBtn.attr('class', 'firmwareBtn btn btn-primary');
                firmwareBtn.text('选中');
                currfirmware = firmwareBtn;

                firmwareChoice(firmwareBtn.attr('index'));
            });
        }
    });
}

function firmwareChoice(index) {
    currFirmware = firmwares[index];
    let pushLabel = $('.pushLabel');
    if(currFirmware.batch) {
        pushLabel.css({'display': 'inline-block'});
    } else {
        pushLabel.css({'display': 'none'});
    }
}

function getStr(str, start, end) {
    let res = str.match(new RegExp(`${start}(.*?)${end}`))
    return res ? res[1] : null;
}

function push(name, hardware, type, version, link, hash, extra, batch) {
    let pushBtn = $('#pushBtn');
    pushBtn.text('请稍等');
    pushBtn.attr({"disabled":"disabled"});

    if(hardware != HARDWARE) {
        $('#TipsModalLabel').text('推送错误 (错误码: deviceMismatch)');
        $('#tipsModal .modal-footer .TipsCloseBtn').css({'display': 'inline-block'});
        $('#tipsModal .modal-body').text('已选择的固件只能用于设备 ' + hardware + " 而已选择的设备是 " + HARDWARE);
        $('#tipsModal').modal('show');
        pushBtn.text('开始');
        pushBtn.removeAttr("disabled");
        return;
    }

    post_url = './api/api2.php?action=push';

    $.ajax({
        url: post_url,
        type: 'GET',
        data: {
            userId,
            name, 
            hardware,
            type,
            version,
            link,
            hash,
            extra,
            batch,
            deviceId: DEVICEID,
            sn: SN,
            serviceToken: serviceToken,
            cUserId: cUserId
        },
        success: (data) => {
            var stateCode = getStr(data, '{"code":', ',');
            if(stateCode == '0') {
                $('#TipsModalLabel').text('推送提示');
                $('#tipsModal .modal-footer .TipsCloseBtn').css({'display': 'inline-block'});
                $('#tipsModal .modal-body').text('推送成功，请检查是否成功推送！');
                $('#tipsModal').modal('show');
                pushBtn.text('开始');
                pushBtn.removeAttr("disabled");
            } else if(stateCode == '-1') {
                $('#TipsModalLabel').text('推送错误 (错误码: deviceOffline)');
                $('#tipsModal .modal-footer .TipsCloseBtn').css({'display': 'inline-block'});
                $('#tipsModal .modal-body').text('请检查设备是否联网，或者重启设备后重试！');
                $('#tipsModal').modal('show');
                pushBtn.text('开始');
                pushBtn.removeAttr("disabled");
            } else if(stateCode == '2') {
                $('#TipsModalLabel').text('推送错误 (错误码: TimeOut)');
                $('#tipsModal .modal-footer .TipsCloseBtn').css({'display': 'inline-block'});
                $('#tipsModal .modal-body').text('请检查设备是否联网，或者重启设备后重试！');
                $('#tipsModal').modal('show');
                pushBtn.text('开始');
                pushBtn.removeAttr("disabled");
            } else {
                $('#TipsModalLabel').text('推送错误 (错误码: Unkown)');
                $('#tipsModal .modal-footer .TipsCloseBtn').css({'display': 'inline-block'});
                $('#tipsModal .modal-body').text('出现了预料之外的错误，已经输出详细信息在控制台内');
                $('#tipsModal').modal('show');
                pushBtn.text('开始');
                pushBtn.removeAttr("disabled");
            }
        }
    });
}

function qrlogin() {
    fetch('./api/api.php?action=qrlogin')
        .then(response => response.json())
        .then(data => {
            if (data.qrUrl) {
                document.getElementById('qrCodeContainer').innerHTML = `<img src="${data.qrUrl}" alt="QR Code" />`;

                if (data.lpUrl) {
                    fetchLPResponse(data.lpUrl);
                }
            }
        })
        .catch(error => console.error('Error fetching QR and LP:', error));
}

function fetchLPResponse(lpUrl) {
    fetch(`./api/getLPResponse.php?lpUrl=${encodeURIComponent(lpUrl)}`)
    .then(response => response.json())
    .then(data => {
        if (data.nonce) {
            userId=data.userId;
            cUserId=data.cUserId;
            getserviceToken(data.nonce, data.ssecurity, data.locationurl);
        } else {
            console.error('Login Falied');
            if(!islogin)
            {
                qrlogin();
            }
        }
    });
}

function getserviceToken(nonce, ssecurity, location) {
    const formData = new FormData();
    formData.append('action', "serviceToken");
    formData.append('nonce', nonce);
    formData.append('ssecurity', ssecurity);
    formData.append('location', location);

    fetch('./api/api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.text();
        } else {
            console.error('Error fetching servicetoken:', response.status);
            $('#TipsModalLabel').text('登录提示');
            $('#tipsModal .modal-footer .TipsCloseBtn').css({'display': 'inline-block'});
            $('#tipsModal .modal-body').text('api错误 请稍后再试！');
            $('#tipsModal').modal('show');
        }
    })
    .then(serviceToken1 => {
        serviceToken = serviceToken1;
        refreshDevice();
        afterlogin();
    });
}

function refreshDevice(){
    post_url = './api/api.php';
    $.ajax({
        url: post_url,
        method: 'POST',
        data: {
            'action': 'getdevices',
            'serviceToken': serviceToken,
            'userId': userId
        },
        success: function(data) {
            var arr =  JSON.parse(data);
            let state = $('#state');
            if(Array.isArray(arr)){
                getDevices(arr);
                return;
            }
        }
    });
}

function afterlogin(){
    loginbtn.text('已登录');
    refreshqrbtn.text('已登录');
    loginbtn.attr({"disabled":"disabled"});
    refreshqrbtn.attr({"disabled":"disabled"});
    islogin = true;
    qrCodeContainer.style.display = "none";
    userIdContainer.style.display = "none";
    passwordContainer.style.display = "none";
    userIcon.style.display = "none";
    pwdIcon.style.display = "none";
}

qrlogin(); // 初始加载