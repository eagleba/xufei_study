#!/usr/bin/env python
# -*- coding:utf-8 -*-
"""
python 2.7 + flask
"""

from time import ctime
import json
import os
import socket
import fsr
from flask import Flask, abort, request, jsonify

APP = Flask(__name__)

CUSTOMS = {}


#文字转语音并播放
def play_backgroud_asr(flowdata):
    result = {
        "action": "start_asr",
        "flowdata": flowdata,
        "params": {
            "min_speak_ms": 100, #说话时间小于这个值，会被认为是无效声音。
            "max_speak_ms": 20000, #说话时间超过这个值，就停止录音，直接提交ASR服务器识别。
            "min_pause_ms": 300, #默认值用户停顿时间超过这个值，会提交到ASR识别。
            "max_pause_ms": 600, #用户停顿时间超过这个值，认为一句话说完。
            "pause_play_ms": 0, #0表示禁用自动暂停，用户一直说话不停顿的时候可以使用
            "threshold": 0, #VAD阈值，默认0，建议不要设置，如果一定要设置，建议 2000以下的值。
            "recordpath": "", #录音文件路径
            "volume": 50, #0-100，0不使用音量标准化，其他值 音量把录音音量调整到这个值后，再提交ASR识别。
            "filter_level": 0.3 #防止干扰等级。0-1.0之间，建议 0.3。
        }
    }
    print("\n当前请求参数：")
    print(result)
    return jsonify(result)


#放音时停止识别
def playback_noasr(flowdata, prompt):
    """playback"""
    result = {
        "action": "playback",
        "flowdata": flowdata,
        "suspend_asr": True,
        "params": {
            "prompt": prompt,
        }
    }
    print("\n当前请求参数：")
    print(result)
    return jsonify(result)


#启动ASR
def wait(flowdata, wait=5000):
    """playback"""
    result = {
        "action": "wait",
        "flowdata": flowdata,
        "params": {
            "timeout": wait
        }
    }
    print("\n当前请求参数：")
    print(result)
    return jsonify(result)


# 不执行任何操作
def noop(flowdata, usermsg=""):
    """noop action"""
    result = {
        "action": "noop",
        "flowdata": flowdata,
        "params": {
            "usermsg": usermsg,
        }
    }
    return jsonify(result)


#放音并挂断
def play_after_hangup(prompt):
    """play and hangup"""
    result = {
        "action": "playback",
        "suspend_asr": True,
        "flowdata": "",
        "params": {"prompt": prompt},
        "after_action": "hangup",
        "after_ignore_error": True,
        "after_params": {"cause": 0,"usermsg": ""}
    }
    print("\n当前请求参数：")
    print(result)
    return jsonify(result)


def data_cleaning(text):
    text_split = text.split(";")[:-1]
    text_new = "".join(cell[2:] for cell in text_split)
    return text_new


@APP.route("/smartivr", methods=['POST'])
def smartivr():
    # 获取机器人返回的数据
    req = request.get_json()
    print("\n")
    print(ctime(), "本轮调用结果:")
    print(req)
    # 获取机器人状态
    notify = req.get('notify')

    # 启动状态机
    m = fsr.external_run()

    # 开启ASR
    if notify == 'enter':
        resp = play_backgroud_asr("start_asr")

    # 开始播放录音
    elif notify == "start_asr_result":
        input_ = {'query': '', 'state': 'initial_state'}
        output = m.response(input_)
        resp = playback_noasr(str(output), output['response'])

    # 未识别结束
    elif notify == 'asrprogress_notify':
        resp = noop(req.get("flowdata"))

    elif notify == "playback_result":
        resp = wait(req.get("flowdata"))

    # 识别结束
    elif notify == "asrmessage_notify":
        query_origin = req.get('message')
        query = data_cleaning(query_origin) if query_origin else query_origin
        print("\n")
        print(ctime())
        print("\n当前识别结果为：{}".format(query))
        oldstate = eval(req["flowdata"])['state']
        input_ = {'query': query, 'state': oldstate}
        output = m.response(input_)
        nowstate = output.get('state')
        if nowstate not in ['finish_success_state', 'finish_artificial_state', 'finish_fail_state']:
            resp = playback_noasr(str(output), output['response'])
        else:
            resp = play_after_hangup(output['response'])

    # 挂断状态
    elif notify == 'leave':
        resp = noop(req.get("flowdata"))

    # 用户不说话时
    elif notify == 'wait_result':
        flow_data = eval(req['flowdata'])
        if flow_data['label'] != "stop_wait":
            flow_data['label'] = "stop_wait"
            resp = playback_noasr(str(flow_data), flow_data['response'])
        else:
            resp = play_after_hangup("谢谢，再见")

    else:
        resp = noop(req.get("flowdata"))

    return resp


def main():
    hostname = socket.gethostname()
    host = socket.gethostbyname(hostname)
    port = 9999
    APP.run(host=host, port=port, debug=True)


if __name__ == "__main__":
    main()
