<?php

	$data=file_get_contents("php://input"); 
	$data = json_decode($data, TRUE); 
	
	$TM_START_MSG = "您好。这里是ABC英语。新学期开始了，我们为小朋友准备了免费的外教英语试听课，想邀请小朋友参加。请问您近期是否想要帮孩子了解英语培训课程呢？";
	$TM_END_MSG = "好的。感谢您对我工作的支持，希望ABC英语对您的孩子的学习有帮助，祝您生活愉快！再见！"
	
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
			$flowdata["keyword"]["yes"]=array("有","好","要","可以","行","没问题");
			$flowdata["keyword"]["no"]=array("不","没");
			$flowdata["keyword"]["not_sure"]=array("还行","再看"，"再说");
			$flowdata["keyword"]["query"]=array("哪里","什么","你好");


			$pause_play_ms = 200; //是否设置自动打断 0，关闭自动打断，其他值（建议 300-1000，或者关闭），检测多少毫秒的声音就打断。

			$flowdata["retrytext"]="您好。这里是ABC英语。新学期开始了，我们为小朋友准备了免费的外教英语试听课，想邀请小朋友参加。请问您近期是否想要帮孩子了解英语培训课程呢？";
			play_background_asr($pause_play_ms,"您好。这里是ABC英语。新学期开始了，我们为小朋友准备了免费的外教英语试听课，想邀请小朋友参加。请问您近期是否想要帮孩子了解英语培训课程呢？");

		}
		else if($notify=="playback_result"){
		
			if($flowdata["retry"]  > 1){
				play_after_hangup("好的。感谢您对我工作的支持，希望ABC英语对您的孩子的学习有帮助，再见！");
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
						$flowdata["retrytext"]="好的，我理解。小朋友是还没有开始打算学英语呢？还是已经在其他地方学习了？";
						playback("好的，我理解。小朋友是还没有开始打算学英语呢？还是已经在其他地方学习了？");
					}
					else if($keyword == "yes"){
						$flowdata["step"]="TMA";
						$flowdata["retrytext"]="好的,我们是ABC英语，在江南大道附近，我们引进美国幼儿园和小学教材，由资深外教老师任教、让孩子在歌曲、游戏、故事、动画片等活动中轻松学英文。我们的外教英语体验活动通常在周日上午或周三晚上，也可以为您预约其他时间。有英语基础的小朋友，还可以旁听我们周日上午的正式课程，您看孩子哪个时间会比较合适呢？";
						play_back("好的,我们是ABC英语，在江南大道附近，我们引进美国幼儿园和小学教材，由资深外教老师任教、让孩子在歌曲、游戏、故事、动画片等活动中轻松学英文。我们的外教英语体验活动通常在周日上午或周三晚上，也可以为您预约其他时间。有英语基础的小朋友，还可以旁听我们周日上午的正式课程，您看孩子哪个时间会比较合适呢？");
					}
					else if($keyword == "not_sure"){
						$flowdata["step"]="TMC";
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
				else if($flowdata["step"]=="TMB"){
					if（strstr($message,"没打算"){
						$flowdata["step"]="TMC";
						$flowdata["retrytext"]="现在英语作为第二语言，孩子在以后的学习中是必然会运用到的，孩子的英语启蒙越早越好呢。我们ABC英语引进美国幼儿园和小学教材，由资深外教老师任教、让孩子在歌曲、游戏、故事、动画片等活动中轻松学英文，很受小朋友的欢迎，您不妨带孩子来免费体验一下。我们还定期举行公益的英语角活动，即使暂时不打算报课，也可以长期参与我们的英语角活动与更多的家长和小朋友交流，培养英语兴趣。您看是否要帮孩子做一个了解呢？";
						playback("现在英语作为第二语言，孩子在以后的学习中是必然会运用到的，孩子的英语启蒙越早越好呢。我们ABC英语引进美国幼儿园和小学教材，由资深外教老师任教、让孩子在歌曲、游戏、故事、动画片等活动中轻松学英文，很受小朋友的欢迎，您不妨带孩子来免费体验一下。我们还定期举行公益的英语角活动，即使暂时不打算报课，也可以长期参与我们的英语角活动与更多的家长和小朋友交流，培养英语兴趣。您看是否要帮孩子做一个了解呢");
					}
					else if（strstr($message,"在学了") || $keyword == "no"）{
						$flowdata["step"]="TM_END";
						$flowdata["retrytext"]="现在英语作为第二语言，孩子在以后的学习中是必然会运用到的，孩子的英语启蒙越早越好呢。我们ABC英语引进美国幼儿园和小学教材，由资深外教老师任教、让孩子在歌曲、游戏、故事、动画片等活动中轻松学英文，很受小朋友的欢迎，您不妨带孩子来免费体验一下。我们还定期举行公益的英语角活动，即使暂时不打算报课，也可以长期参与我们的英语角活动与更多的家长和小朋友交流，培养英语兴趣。您看是否要帮孩子做一个了解呢？";
						playback("现在英语作为第二语言，孩子在以后的学习中是必然会运用到的，孩子的英语启蒙越早越好呢。我们ABC英语引进美国幼儿园和小学教材，由资深外教老师任教、让孩子在歌曲、游戏、故事、动画片等活动中轻松学英文，很受小朋友的欢迎，您不妨带孩子来免费体验一下。我们还定期举行公益的英语角活动，即使暂时不打算报课，也可以长期参与我们的英语角活动与更多的家长和小朋友交流，培养英语兴趣。您看是否要帮孩子做一个了解呢");
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