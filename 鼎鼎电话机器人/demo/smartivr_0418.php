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
			$flowdata["flowtype"]="default";
			play_background_asr("您好，这里是电话机器人演示系统，现在开始语音识别测试，你可以说通过说关键词  关闭打断 打开打断 唱歌 等 进入不同的测试流程");
		}
		else if($notify=="playback_result" || $notify=="wait_result"){
		
			$asrstate = $data["asrstate"]; //当前用户是否在说话
			$hangup = $data["hangup"]; //对方是否已经挂断

			if($asrstate || $hangup){
				noop();
			}
			else if($flowdata["flowtype"]=="default"){
			
				if(array_key_exists("history_asr_message",$flowdata)  && $flowdata["history_asr_message"]){
					$message =  $flowdata["history_asr_message"];
					unset( $flowdata["history_asr_message"]);
					playback(array("刚刚放音时候识别到的结果是",$message,"请继续说话测试吧"),5000, 0);
				}
				else if(array_key_exists("wait",$flowdata)  && $flowdata["wait"]){
					$wait = $flowdata["wait"];
					unset($flowdata["wait"]);
					wait($wait);
				}
				else {
					playback("请说话测试吧");
				}

			}
			else 
			{
				playback("系统错误，未知流程");
			}
		}
		else if($notify=="asrprogress_notify"){

			
			if( array_key_exists("nobreak",$flowdata)  && $flowdata["nobreak"]){
				console_playback("resume");
			}
			else{
				
				if($message=="" || $errorcode !=0){
					noop();
				}
				else{
					console_playback("pause");
				}
			}

		}
		else if($notify=="asrmessage_notify"){

			$arr = explode(";",$message);
			$orig="";
			foreach($arr as $val){
				$orig .= substr(strstr($val,"."),1);
			}

			$playstate = $data["playstate"]; //当前是否在放音




			if($playstate && array_key_exists("nobreak",$flowdata)  && $flowdata["nobreak"]  ){


				if(array_key_exists("history_asr_message",$flowdata)){
					$flowdata["history_asr_message"] .= $orig;
				}
				else {
					$flowdata["history_asr_message"] = $orig;
				}

				console_playback("resume");
			}
			else {

				if($orig == "" || $errorcode !=0){
					console_playback("resume");
				}
				else if(strstr($orig,"唱歌") || strstr($orig,"音乐")){
					if($flowdata["flowtype"]=="default"){
						playback(array("现在给你播放一首歌曲","music.wav"));
					}

				}
				else if(strstr($orig,"转接") || strstr($orig,"转人工")){
					bridge("1002","","user/1002","正在转接中，请等待","wating.wav");
				}
				else if(strstr($orig,"关闭打断") || strstr($orig,"不要打断") || strstr($orig,"停止打断")){
					
					$flowdata["flowtype"]="resetasr";
					$flowdata["nobreak"]=true;
					restartasr(0);
				}
				else if(strstr($orig,"开始打断") || strstr($orig,"启动打断") || strstr($orig,"打开打断")){
					
					$flowdata["flowtype"]="resetasr";
					$flowdata["nobreak"]=false;
					restartasr(500);
				}
				else if(strstr($orig,"关闭自动打断")){
					
					playback("这个声音不能自动打断，你试试吧，说快点，不要听，关键词打断还是有效的哦，试试能不能自动打断我，一定要多试试哦",5000, 0, -1);

				}
				else {

					$allmessage = "";

					if(array_key_exists("history_asr_message",$flowdata)  && $flowdata["history_asr_message"]){
						$allmessage =  $flowdata["history_asr_message"];
						unset( $flowdata["history_asr_message"]);
					}

					$allmessage .= $orig;

					playback(array("刚刚的识别结果是",$allmessage, "请继续说话测试吧"),5000, 0);
				}

			}

		}
		else if($notify=="leave"){
			noop();
		}
		else if($notify=="start_asr_result"){
			
			if($flowdata["flowtype"]=="resetasr"){

				$flowdata["flowtype"]="default";

				if($flowdata["nobreak"]==true){
					playback("打断功能已经关闭，请说话测试吧");
				}
				else{
					playback("打断功能已经打开，请说话测试吧");
				}
			}


		}
		else if($notify=="bridge_result"){
			hangup();
		}
		else if($notify=="stop_result"){
			noop();
		}
		else if($notify=="transfer_result"){
			noop();
		}
		else{
			play_after_hangup("未知通知类型");
		}
	}
	else {
		play_after_hangup("数据错误");
	}

    






	
	function hangup($usermsg="",$cause= 0)
	{
		$result = array("action"=>"hangup","flowdata"=>$GLOBALS["flowdata"],"params" =>array("cause"=>$cause,"usermsg"=>"$usermsg"));
		echo(json_encode($result));
	}



	function noop($usermsg="")
	{
		$result = array("action"=>"noop","flowdata"=>$GLOBALS["flowdata"],"params" =>array("usermsg"=>"$usermsg"));
		echo(json_encode($result));
	}

  





	function transfer($destnumber,$dialplan="XML",$context="default")
	{
		$result = array("action"=>"stop_asr","flowdata"=>$GLOBALS["flowdata"],
			"after_action"=>"transfer",
			"after_ignore_error"=>false,  	
		"after_params"=>array("destnumber"=>"$destnumber","dialplan"=>"$dialplan","context"=>"$context"));
		echo(json_encode($result));
	}



    function bridge($number,$callerid="",$gateway="",$prompt="",$background="")
	{

		$result = array("action"=>"stop_asr","flowdata"=>$GLOBALS["flowdata"],
			"after_action"=>"bridge",
			"after_ignore_error"=>false,  	
		"after_params"=>array("number"=>"$number","callerid"=>"$callerid","gateway"=>"$gateway","prompt"=>"$prompt","background"=>"$background"));

		echo(json_encode($result));
	}


	function deflect($number)
	{
		$result = array("action"=>"deflect","flowdata"=>$GLOBALS["flowdata"],"params"=>array("number"=>"$number"));
		echo(json_encode($result));
	}

	function getdtmf($prompt,$max=128)
	{
		$result = array("action"=>"getdtmf","flowdata"=>$GLOBALS["flowdata"],"params"=>array("prompt"=>"$prompt","invalid_prompt"=>"按键无效","min"=>0,"max"=>$max,"tries"=>1,"timeout"=>5000,"digit_timeout"=>3000,"terminators"=>"#"));
		echo(json_encode($result));
	}

	function wait($timeout)
	{
		$result = array("action"=>"wait","flowdata"=>$GLOBALS["flowdata"],"params"=>array("timeout"=>$timeout));
		echo(json_encode($result));
	}


	function restartasr($pause_play_ms=500)
	{
		$result = array(
			"after_action"=>"start_asr",
			"flowdata"=>$GLOBALS["flowdata"],
			"after_params"=>array(
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
			"action"=>"stop_asr",
			"after_ignore_error"=>false);
	
		echo(json_encode($result));

	}

	//pause_play_ms 监测到用户说话持续多久，就自动停止机器人放音。 0关闭这个功能
	function play_background_asr($prompt,$wait=5000,$retry=0,$pause_play_ms=500)
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



	function play_after_background_asr($prompt,$wait=5000,$retry=0,$pause_play_ms=500)
	{
		$result = array(
			"after_action"=>"start_asr",
			"flowdata"=>$GLOBALS["flowdata"],
			"after_params"=>array(
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
			"action"=>"playback",
			"after_ignore_error"=>false,  
			"params" =>array(
				"prompt"=>$prompt,
				"wait"=>$wait,
				"retry"=>$retry
			));
	
		echo(json_encode($result));
	}





	//allow_interrupt 放音时是否允许自动打断（start_asr先设置pause_play_ms，开启自动打断） -1：不允许， 0：允许，其他：放音多少毫秒后允许自动打断。 
	function playback($prompt,$wait = 5000, $retry = 0 , $allow_interrupt = 0)
	{
		
		if( array_key_exists("nobreak",$GLOBALS["flowdata"]) && $GLOBALS["flowdata"]["nobreak"]){
			$GLOBALS["flowdata"]["wait"]=$wait;
			$wait = 0;
		}

		$result = array(
			"action"=>"playback",
			"flowdata"=>$GLOBALS["flowdata"],
			"params"=>array(
				"prompt"=>$prompt,
				"wait"=>$wait,
				"retry"=>$retry,
				"allow_interrupt" => $allow_interrupt
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