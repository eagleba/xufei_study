<?php

	$data=file_get_contents("php://input"); 
	$data = json_decode($data, TRUE); 
	
	if(key_exists("notify",$data) &&
		key_exists("calleeid",$data) &&
		key_exists("callerid",$data) &&
		key_exists("callid",$data) ) {

		$notify = $data["notify"];
		$calleeid = $data["calleeid"];
		$callerid = $data["callerid"];
		$callid = $data["callid"];
		$errorcode = 0;
		if(key_exists("errorcode",$data)){
			$errorcode = $data["errorcode"];
		}

		$message = "";
		if(key_exists("message",$data)){
			$message = $data["message"];
		}

		$flowdata = array();
		if(key_exists("flowdata",$data)){
			$flowdata = $data["flowdata"];
		}

		if($notify=="enter"){

			$flowdata["step"]="询问是否需要买房";
			$flowdata["break"]=false;
			$flowdata["retry"]=0;
			$flowdata["retrytext"]="";
			$flowdata["keyword"]["yes"]=array("有","好","要","可以");
			$flowdata["keyword"]["no"]=array("不","没");
			$flowdata["keyword"]["query"]=array("哪里","什么","你好");


			$pause_play_ms = 0; //是否设置自动打断 0，关闭自动打断，其他值（建议 300-1000，或者关闭），检测多少毫秒的声音就打断。

			$flowdata["retrytext"]="喂你好，我是售楼部的，请问你最近有打算买房吗";
			play_background_asr($pause_play_ms,"欢迎进入房产话术测试流程,现在开始测试，先生你好，我是售楼部的，请问你最近有打算买房吗");

		}
		else if($notify=="playback_result"){
		
			if($flowdata["retry"]  > 1){
				play_after_hangup("打扰了，再见");
			}

			else {

				$flowdata["retry"] = $flowdata["retry"] +1;

				playback($flowdata["retrytext"]);

			}


		}
		else if($notify=="asrprogress_notify"){


			if($message == "" || $errorcode != 0){
				 console_playback("resume");
			}
			else if($flowdata["break"]){
				noop();
			}
			else{
				
				$hit = false;
				
				foreach ($flowdata["keyword"] as $sub) {
					foreach ($sub as $value) {
						if(strstr($message,$value)){
							$hit = true;
							break;
						}
					}
				}

				if($hit){
					$flowdata["break"]=true;
					console_playback("pause");
				}
				else{
					console_playback("resume");
				}
			}


		}
		else if($notify=="asrmessage_notify"){

			$playstate = $data["playstate"]; //当前是否在放音
		
			if($message == "" || $errorcode !=0){
				console_playback("resume");
			}
			else{

				$keyword = find_keyword($message);

				if($flowdata["step"]=="询问是否需要买房"){

					if($keyword == "no"){
						$flowdata["step"]="否决_挽留";

						$flowdata["retrytext"]="喂你好，需要来看一下吗";
						playback("我们最近有一个学区房准备开盘，位置非常好，开盘有优惠活动，你都不考虑一下吗");
					}
					else if($keyword == "yes"){
						play_after_hangup("好的，我等下把我的微信号通过短信发给你，你加一下我的微信号，我通过微信发送优惠信息给你，谢谢，祝你生活愉快");
					}
					else if(strstr($message,"哪里") || strstr($message,"什么")){

						$flowdata["retrytext"]="喂你好，请问你最近有打算买房";

						playback("我们是售楼部的，请问你最近有打算买房吗");
					}
					else{ 
	
						if($playstate){
							console_playback("resume");
						}
						else{
							$flowdata["step"]="未知_挽留";
							playback("我们楼盘最近准备开盘，位置非常好，开盘有优惠活动，你需要了解一下吗");
						}
						

					}

				}
				else if($flowdata["step"]=="否决_挽留" || $flowdata["step"]=="未知_挽留"){

					if($keyword == "no"){
						play_after_hangup("好的，打扰你的，再见");
					}
					else if($keyword == "yes"){
						play_after_hangup("好的，我等下把我的微信号通过短信发给你，你加一下我的微信号，我通过微信发送优惠信息给你，谢谢，祝你生活愉快");
					}
					else if($keyword == "query"){
						play_after_hangup("请自己加入各种关键词处理，演示结束再见");
					}
					else{
						if($playstate){
							console_playback("resume");
						}
						else{
							play_after_hangup("不好意思，现在信号不好，我以后在联系你");
						}
					}

				}
			}



		}
		else if($notify=="leave"){
			noop();
		}
		else{
			play_after_hangup("未知通知类型");
		}
	}
	else {
		play_after_hangup("数据错误");
	}

    
	function find_keyword($message)
	{
		foreach ($GLOBALS["flowdata"]["keyword"]["query"] as $value) {
			if(strstr($message,$value)){
				return "query";
			}
		}

		foreach ($GLOBALS["flowdata"]["keyword"]["no"] as $value) {
			if(strstr($message,$value)){
				return "no";
			}
		}


		foreach ($GLOBALS["flowdata"]["keyword"]["yes"] as $value) {
			if(strstr($message,$value)){
				return "yes";
			}
		}


		return "";
	}


	



	function noop($usermsg="")
	{
		$result = array("action"=>"noop","flowdata"=>$GLOBALS["flowdata"],"params" =>array("usermsg"=>"$usermsg"));
		echo(json_encode($result));
	}

   


	function play_background_asr($pause_play_ms,$prompt,$wait=5000,$retry=0)
	{
		$result = array(
			"action"=>"start_asr",
			"flowdata"=>$GLOBALS["flowdata"],
			"params"=>array(
				"min_speak_ms"=>100,
				"max_speak_ms"=>10000,
				"min_pause_ms"=>500,
				"max_pause_ms"=>800,
				"pause_play_ms"=>$pause_play_ms,
				"threshold"=>0,
				"recordpath"=>"",
				"volume"=>50,
				"filter_level"=>0,
			    "asr_configure_filename"=>""

			),
			"after_action"=>"playback",
			"after_ignore_error"=>false,  
			"after_params" =>array(
				"prompt"=>$prompt,
				"wait"=>$wait,
				"retry"=>$retry
			));
	
		echo(json_encode($result));
	}

	
	function playback($prompt,$wait=5000,$retry=0)
	{
		$result = array(
			"action"=>"playback",
			"flowdata"=>$GLOBALS["flowdata"],
			"params"=>array(
				"prompt"=>$prompt,
				"wait"=>$wait,
				"retry"=>$retry
		));
		echo(json_encode($result));
	}


	function play_after_hangup($prompt,$usermsg="",$cause= 0)
	{
		$result = array(
			"action"=>"playback",
			"suspend_asr"=>true,
			"flowdata"=>$GLOBALS["flowdata"],
			"params"=>array("prompt"=>$prompt),
			"after_action"=>"hangup",
			"after_ignore_error"=>true,  
			"after_params" =>array("cause"=>$cause,"usermsg"=>"$usermsg")
		);
		echo(json_encode($result));
	}

	//pause resume stop
	function console_playback($cmd)
	{
		$result = array(
			"action"=>"console_playback",
			"flowdata"=>$GLOBALS["flowdata"],
			"params"=>array(
			"command"=>"$cmd"
		));
		echo(json_encode($result));
	}






?>