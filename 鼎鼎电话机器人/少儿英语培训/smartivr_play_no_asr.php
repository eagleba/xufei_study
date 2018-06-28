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


		if(key_exists("flowdata",$data)){
			$flowdata = $data["flowdata"];
		}

		if($notify=="enter"){



			play_background_asr("您好，这里是电话机器人演示系统，现在开始语音识别测试，请在声音停止后说点什么吧，比如我爱中国，早上好，需要买房吗，等等！");

		}
		else if($notify=="playback_result" || $notify=="start_asr_result"){

			$hangup = $data["hangup"]; //对方是否已经挂断
			if($$hangup){
				noop();
			}
			else{
				playback_noasr("你好，还在吗。");
			}

		}
		else if($notify=="asrprogress_notify"){
				noop();
		}
		else if($notify=="asrmessage_notify"){

			$arr = explode(";",$message);
			$orig="";
			foreach($arr as $val){
				$orig .= substr(strstr($val,"."),1);
			}
		
			if($message == "" || $errorcode !=0){
				noop();
			}
			else {
				playback_noasr(array("刚刚的识别结果是",$orig, "请继续说话测试吧"));
			}


		}
		else {
			noop();
		}
	}
	else {
		play_after_hangup("数据错误");
	}

    


	function noop($usermsg="")
	{
		$result = array("action"=>"noop","flowdata"=>$GLOBALS["flowdata"],"params" =>array("usermsg"=>"$usermsg"));
		echo(json_encode($result));
	}



	function play_background_asr($prompt,$wait=5000,$retry=0,$block_asr = -1,$pause_play_ms=500)
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
				"retry"=>$retry,
				"block_asr" => $block_asr 
			));
	
		echo(json_encode($result));
	}


	
	function playback_noasr($prompt,$wait=5000,$retry=0,$block_asr = -1)
	{
		$result = array(
			"action"=>"playback",
			"flowdata"=>$GLOBALS["flowdata"],
			"params"=>array(
				"prompt"=>$prompt,
				"wait"=>$wait,
				"retry"=>$retry,
				"block_asr" => $block_asr 
			));
		echo(json_encode($result));
	}






?>