#!/usr/bin/env python
# -*- coding:utf-8 -*-
"""
python 2.7 + flask
"""

from flask import Flask, abort, request, jsonify

APP = Flask(__name__)

CUSTOMS = {}

def play_backgroud_asr(flowdata, prompt, wait=5000, retry=0):
    """Play backgroud ASR"""
    result = {
        "action": "start_asr",
        "flowdata": flowdata,
        "params": {
            "min_speak_ms": 100,
            "max_speak_ms": 10000,
            "min_pause_ms": 300,
            "max_pause_ms": 600,
            "pause_play_ms": 0,
            "threshold": 0,
            "recordpath": "",
            "volume": 50,
            "filter_level": 0
        },
        "after_action": "playback",
        "after_ignore_error": False,
        "after_params": {
            "prompt": prompt,
            "wait": wait,
            "retry": retry
        }
    }
    return jsonify(result)

def playback(flowdata, prompt, wait=5000, retry=0):
    """playback"""
    result = {
        "action": "playback",
        "flowdata": flowdata,
        "params": {
            "prompt": prompt,
            "wait": wait,
            "retry": retry
        }
    }
    return jsonify(result)

def play_after_hangup(flowdata, prompt, usermsg="", cause=0):
    """play and hangup"""
    result = {
        "action": "playback",
        "suspend_asr": True,
        "flowdata": flowdata,
        "params": {
            "prompt": prompt

        },
		"after_action": "hangup",
         "after_ignore_error": True,
          "after_params": {
                "cause": cause,
                "usermsg": usermsg,
            }
    }
    return jsonify(result)

def console_playback(flowdata, command):
    """console playback action"""
    result = {
        "action": "console_playback",
        "flowdata": flowdata,
        "params": {
            "command": command,
        }
    }
    return jsonify(result)

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

def get_custom(req):
    """Get custom """
    callid = req.get("callid", u"").encode('utf-8')
    calleeid = req.get("calleeid", "").encode('utf-8')
    callerid = req.get("callerid", "").encode('utf-8')
    key = '%s,%s,%s' % (callid, callerid, calleeid)
    custom = CUSTOMS.get(key, None)
    if not custom:
        flowdata = req.get("flowdata", "")
        if not flowdata:
            flowdata = ""
        custom = {
            "key": key,
            "flowdata": flowdata.encode('utf-8')
        }
        CUSTOMS[key] = custom
    return custom

def process_enter(req):
    """Process enter notify"""
    custom = get_custom(req)
    custom['flowdata'] = "流程选择"
    return play_backgroud_asr(
        custom['flowdata'],
        "您好，欢迎致电顶顶通软件，这里是电话机器人演示系统，请说要进入的测试流程，比如，房产推销,语音识别测试！")

def process_playback_result(req):
    """Process playback result"""
    custom = get_custom(req)
    flowdata = req.get('flowdata', u"").encode('utf-8')
    if not flowdata:
        return abort(400)
    if flowdata == "流程选择":
        custom['flowdata'] = "提示流程选择"
        return playback(
            custom['flowdata'],
            "请问你要进入哪个测试流程，比如，房产推销，语音识别测试", 3000, 2)
    elif flowdata == "提示流程选择":
        return play_after_hangup(flowdata, "谢谢使用，再见")
    return playback(flowdata, "你好，还在吗。", 5000)

def process_asrprogress_notify(req):
    """Process asrprogress notify"""
    flowdata = req.get("flowdata", u"")
    if not flowdata:
        return abort(400)
    message = req.get('message', None)
    errorcode = req.get('errorcode', 0)
    if not message or errorcode != 0:
        return noop(flowdata)
    return console_playback(flowdata, "pause")

def process_choice(custom, message):
    """Process choice"""
    if message.find("房产") != -1:
        custom['flowdata'] = "房产_询问_1"
        return playback(
            custom['flowdata'],
            "欢迎进入房产话术测试流程,现在开始测试，先生你好，我是售楼部的，请问你最近有打算买房吗")
    elif message.find("语音识别"):
        custom['flowdata'] = "语音识别"
        return playback(
            custom['flowdata'],
            "欢迎进入房产话术测试流程,现在开始测试，先生你好，我是售楼部的，请问你最近有打算买房吗")
    custom['flowdata'] = "提示流程选择"
    return playback(
        custom['flowdata'],
        "刚刚没听清，请问你要进入哪个测试流程，比如，房产推销，语音识别测试",
        3000, 2)

def process_house_query(custom, message):
    """process house query"""
    if message.find("不") != -1 or message.find("没") != -1:
        custom['flowdata'] = "房产_挽留_1"
        return playback(
            custom['flowdata'],
            "我们最近有一个学区房准备开盘，位置非常好，开盘有优惠活动，你都不考虑一下吗"
        )
    elif message.find("有") != -1 or message.find("要") != -1:
        return play_after_hangup(
            custom['flowdata'],
            "好的，我等下把我的微信号通过短信发给你，你加一下我的微信号，我通过微信发送优惠信息给你，谢谢，祝你生活愉快"
        )
    custom['flowdata'] = "房产_挽留_1"
    return playback(
        custom['flowdata'],
        "我们楼盘最近准备开盘，位置非常好，开盘有优惠活动，你需要了解一下吗"
    )

def process_house_retrieve(custom, message):
    """Process house retrieve"""
    if message.find("不") != -1 or message.find("没") != -1:
        return play_after_hangup(
            custom['flowdata'],
            "好的，打扰你的，再见")
    elif message.find("好"):
        return play_after_hangup(
            custom['flowdata'],
            "好的，我等下把我的微信号通过短信发给你，你加一下我的微信号，我通过微信发送优惠信息给你，谢谢，祝你生活愉快")
    return playback(custom['flowdata'], "不好意思，刚刚没听清，你需要来看看吗")

def process_asrmessage_notify(req):
    """process asrmessage notify"""
    custom = get_custom(req)
    flowdata = req.get("flowdata", u"").encode('utf-8')
    message = req.get("message", u"").encode('utf-8')
    errorcode = req.get("errorcode", 0)
    if not message or errorcode != 0:
        return console_playback(flowdata, "resume")
    if flowdata == '流程选择' or flowdata == "提示流程选择":
        return process_choice(custom, message)
    elif flowdata == "房产_询问_1":
        return process_house_query(custom, message)
    elif flowdata == "房产_挽留_1":
        return process_house_retrieve(custom, message)
    elif flowdata == "语音识别":
        fields = message.split(';')
        fields = [field.split('.')[-1] for field in fields]
        msg = ''.join(fields)
        return playback(flowdata, '刚刚的识别结果是%s，请继续说话测试吧！' % msg)
    return jsonify({})

def process_leave(req):
    """Process leave notify"""
    custom = get_custom(req)
    del CUSTOMS[custom["key"]]
    return noop(custom['flowdata'], "")

def process_unknown_notify(req):
    """Process unkonw notify"""
    custom = get_custom(req)
    return play_after_hangup(custom['flowdata'], "数据错误")

@APP.route("/smartivr", methods=['POST'])
def smartivr():
    """SmartIVR RESTful interface"""
    req = request.get_json()
    if not req or 'notify' not in req:
        abort(400)
    print "Request: %s" % request.data
    notify = req.get('notify')
    if notify == "enter":
        resp = process_enter(req)
    elif notify == "playback_result":
        resp = process_playback_result(req)
    elif notify == "asrprogress_notify":
        resp = process_asrprogress_notify(req)
    elif notify == "asrmessage_notify":
        resp = process_asrmessage_notify(req)
    elif notify == "leave":
        resp = process_leave(req)
    else:
        resp =  process_unknown_notify(req)
    print "Response: %s" % resp.data
    return resp

def main():
    """ SmartIVR web server main program"""
    host = "0.0.0.0"
    port = 9999
    APP.run(host=host, port=port, debug=True)

if __name__ == "__main__":
    main()
