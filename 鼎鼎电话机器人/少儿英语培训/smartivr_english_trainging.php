<?php

	$data=file_get_contents("php://input"); 
	$data = json_decode($data, TRUE); 
	
	$TM_START_MSG = "TM_START,您好。这里是ABC英语。新学期开始了，我们为小朋友准备了免费的外教英语试听课，想邀请小朋友参加。请问您近期是否想要帮孩子了解英语培训课程呢？";
	$TM_END_MSG = "TM_END,好的。感谢您对我工作的支持，希望ABC英语对您的孩子的学习有帮助，祝您生活愉快！再见！";
	$TMA_MSG = "TMA,好的，我是ABC英语，在江南大道附近，我们引进美国幼儿园和小学教材，由资深外教老师任教、让孩子在歌曲、游戏、故事、动画片等活动中轻松学英文。我们的外教英语体验活动通常在周日上午或周三晚上，也可以为您预约其他时间。有英语基础的小朋友，还可以旁听我们周日上午的正式课程，您看孩子哪个时间会比较合适呢？";
    $TMB_MSG = "TMB,好的，我理解。小朋友是还没有开始打算学英语呢？还是已经在其他地方学习了？";
    $TMC_MSG = "TMC,现在英语作为第二语言，孩子在以后的学习中是必然会运用到的，孩子的英语启蒙越早越好呢。我们ABC英语引进美国幼儿园和小学教材，由资深外教老师任教、让孩子在歌曲、游戏、故事、动画片等活动中轻松学英文，很受小朋友的欢迎，您不妨带孩子来免费体验一下。我们还定期举行公益的英语角活动，即使暂时不打算报课，也可以长期参与我们的英语角活动与更多的家长和小朋友交流，培养英语兴趣。您看是否要帮孩子做一个了解呢？";
    $TMD_MSG = "TMD,明白了，我们会尽量根据孩子的特点准备活动内容。稍后我们的老师会通知您活动时间和地址，请您留意一下。好吗？";
    $TME_MSG = "TME,好的,下次有试听课时我再联系您,您看行吗";
    $TM_UNKNOWN_MSG = "TM_UNKONWN,好的。感谢您对我工作的支持，希望ABC英语对您的孩子的学习有帮助，祝您生活愉快！再见！";
    
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

			$flowdata["step"]="TM_START";
			$flowdata["break"]=false;
			$flowdata["retry"]=0;
			$flowdata["retrytext"]="";
			$flowdata["keyword"]["yes"]=array("有","好","要","可以","行","没问题","合适");
			$flowdata["keyword"]["no"]=array("不","没");
            $flowdata["keyword"]["not_sure"]=array("还行","再看","再说","没想好","没打算");
			$flowdata["keyword"]["query"]=array("哪里","什么","你好");
            $flowdata["keyword"]["notime"]=array("没时间","没空","有安排","有课");

			$pause_play_ms = 200; //是否设置自动打断 0，关闭自动打断，其他值（建议 300-1000，或者关闭），检测多少毫秒的声音就打断。

			$flowdata["retrytext"]= $TM_START_MSG;
			play_background_asr($pause_play_ms,$TM_START_MSG);

		}
		else if($notify=="playback_result"){
		
			if($flowdata["retry"]  > 1){
				play_after_hangup($TM_END_MSG);
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

				if($flowdata["step"]=="TM_START"){
					if($keyword == "no"){
						$flowdata["step"]="TMB";
						$flowdata["retrytext"]= $TMB_MSG;
						playback($TMB_MSG);
					}
					else if($keyword == "yes"){
						$flowdata["step"]="TMA";
						$flowdata["retrytext"]= $TMA_MSG;
						playback($TMA_MSG);
                    }
					else if($keyword == "not_sure"){
						$flowdata["step"]="TMC";
						$flowdata["retrytext"]= $TMC_MSG;
						playback($TMC_MSG);
					}
					else{ 	
						if($playstate){
							console_playback("resume");
						}
						else{
							$flowdata["step"]="TMC";
                            $flowdata["retrytext"]= $TMC_MSG;
	                        playback($TMC_MSG);							
						}	
					}
				}
                else if($flowdata["step"]=="TMA"){
					if($keyword == "yes"){
						$flowdata["step"]="TMD";
						$flowdata["retrytext"]= $TMD_MSG;
						playback($TMD_MSG);
					}
					else if($keyword == "notime"){
						$flowdata["step"]="TME";
						$flowdata["retrytext"]= $TME_MSG;
						playback($TME_MSG);
                    }
					else if($keyword == "not_sure"){
						$flowdata["step"]="TMD";
						$flowdata["retrytext"]= $TMD_MSG;
						playback($TMD_MSG);
					}
					else{ 	
						if($playstate){
							console_playback("resume");
						}
						else{
							$flowdata["step"]="TME";
                            $flowdata["retrytext"]= $TME_MSG;
	                        playback($TME_MSG);							
						}	
					}
				}
                else if($flowdata["step"]=="TMB"){
					if($keyword == "yes" || $keyword == "not_sure" || $keyword == "notime"){
						$flowdata["step"]="TMC";
						$flowdata["retrytext"]= $TMC_MSG;
						playback($TMC_MSG);
					}
					else if($keyword == "notime"){
						$flowdata["step"]="TME";
						$flowdata["retrytext"]= $TME_MSG;
						playback($TME_MSG);
                    }
					else if($keyword == "no" || strstr($message,"在学了")){
						$flowdata["step"]="TM_END";
						play_after_hangup($TM_END_MSG);
					}
					else{ 	
						if($playstate){
							console_playback("resume");
						}
						else{
							$flowdata["step"]="TMB";
                            $flowdata["retrytext"]= $TMB_MSG;
	                        playback($TME_MSG);							
						}	
					}
				}
                 else if($flowdata["step"]=="TMC"){
					if($keyword == "yes"){
						$flowdata["step"]="TMA";
						$flowdata["retrytext"]= $TMA_MSG;
						playback($TMA_MSG);
					}
					else if($keyword == "no" || $keyword == "not_sure" || $keyword == "notime"){
						$flowdata["step"]="TM_END";
						play_after_hangup($TM_END_MSG);
					}
					else{ 	
						if($playstate){
							console_playback("resume");
						}
						else{
							$flowdata["step"]="TMC";
                            $flowdata["retrytext"]= $TMC_MSG;
	                        playback($TMC_MSG);							
						}	
					}
				}
                else if($flowdata["step"]=="TMD"){
					if($keyword == "yes"){
						$flowdata["step"]="TM_END";
						play_after_hangup($TM_END_MSG);
					}
					else if($keyword == "no" || $keyword == "not_sure" || $keyword == "notime"){
						$flowdata["step"]="TM_END";
						play_after_hangup($TM_END_MSG);
					}
					else{ 	
						if($playstate){
							console_playback("resume");
						}
						else{
							$flowdata["step"]="TM_END";
                            playback($TM_END_MSG);							
						}	
					}
				}
                else if($flowdata["step"]=="TME"){
					if($keyword == "yes"){
						$flowdata["step"]="TM_END";
						play_after_hangup($TM_END_MSG);
					}
					else if($keyword == "no" || $keyword == "not_sure" || $keyword == "notime"){
						$flowdata["step"]="TM_END";
						play_after_hangup($TM_END_MSG);
					}
					else{ 	
						if($playstate){
							console_playback("resume");
						}
						else{
							$flowdata["step"]="TM_END";
                            play_after_hangup($TM_END_MSG);							
						}	
					}
				}                
                
            }
        }
		else if($notify=="leave"){
			noop();
		}
		else{
			play_after_hangup($TM_UNKNOWN_MSG);
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
        
        foreach ($GLOBALS["flowdata"]["keyword"]["not_sure"] as $value) {
			if(strstr($message,$value)){
				return "not_sure";
			}
		}
        
        foreach ($GLOBALS["flowdata"]["keyword"]["notime"] as $value) {
			if(strstr($message,$value)){
				return "notime";
			}
		}

		return "no_keyword";
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