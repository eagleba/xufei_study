package com.SmartIvr.demo;
import java.io.BufferedInputStream;
import java.io.BufferedOutputStream;
import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.util.HashMap;
import java.util.Map;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import org.apache.log4j.Logger;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.ResponseBody;
import net.sf.json.JSONObject;



	/**
	 * SmartIvrDemo
	 * @author cxy  
	 * @version 2018-04-15
	**/
public class SmartIvrDemo {
	private static final Logger log = Logger.getLogger(SmartIvrDemo.class);			
	/**
	 * SmartIvrDemo  接收smartIvr接口信息
	 * @author cxy
	 * @version 2018-04-15
	**/
			@ResponseBody
			@RequestMapping("/smatrIvr")
			public Map<String,Object> smatrIvr(HttpServletRequest request,HttpServletResponse response) {
				String json = new String(getReqMsg(request));	   
				log.info(json);                                               
		        JSONObject jsonDate = JSONObject.fromObject(json);             
		        String action = String.valueOf(jsonDate.get("notify"));       
		        String flowdata = String.valueOf(jsonDate.get("flowdata"));    
				Map<String,Object> map = new HashMap<String, Object>();		
				if("enter".equals(action)){                                    
					map = defaultfangyin("您好，欢迎致电顶顶通软件，这里是电话机器人演示系统，请说要进入的测试流程，比如，房产！");
				}
	            else if("asrprogress_notify".equals(action)){
					//进度流程，按需填写
				}
				else if("asrmessage_notify".equals(action)){
					String message = String.PyvalueOf(jsonDate.get("message")); //将识别结果转换为拼音
					map = voiceJudg(flowdata,message);
				}else{
					map = null;
				}
				log.info("map="+map);
				return map;
			}
			
			/**
			 * 根据语音识别到的message选择放音
			 * @param flow
			 * @param message
			 * @return
			 */
			private Map<String, Object> voiceJudg(String flow,String message){
				//动作1
				String action1 ="";
				//动作2 
				String action2 = "您好，欢迎致电顶顶通软件，这里是电话机器人演示系统，请说要进入的测试流程，比如，房产！";
				//动作3
				String action3 = "";
				//动作4
				String action4 = "";
				
				if("流程选择".equals(flow)){
					action1 = "Java控制后台开发请联系 QQ: 243305203";
					action3 = "体验及 定制测试流程请联系 微信: swcxy12315";
					action4 = "拥有关键词智能识别算法,匹配多个关键词，可分配权重，可定制后台配置界面";
				}
				if("测试".equals(flow)){
					action1 = "你正在进行顶顶通机器人测试";
					action3 = "拥有关键词智能识别算法,匹配多个关键词，可分配权重，可定制后台配置界面";
					action4 = "你不了解吗？你现正在进行测试";
				}
				if("介绍".equals(flow)){
					action1 = "这里是顶顶通机器人，正在和你测试";
					action3 = "定制配置管理后台及接口可联系：QQ: 243305203";
					action4 = "机器人可以识别人的语音，和你对话";
				}
				if("再见".equals(flow)){
					return hangUp("谢谢使用，再见");
				}
				
				//action1【权重高】
				if(("联系").equals(message)||("qq").equals(message)||("说").equals(message)){		
					return  fangyin(action1);
				}
				//action2【权重中】
				else if(("微信").equals(message)|| ("了解").equals(message)||("好").equals(message)){
					return fangyin(action3);
				}
				
				//action2【权重低】
				else if(("不懂").equals(message) ||("不知道").equals(message)||("再来").equals(message)){
					return fangyin(action4);
				}
				//不理解重复action2
				return fangyin("action2");
			}
			
			
			/**
			 * 根据流程，组装对应的放音json
			 * @param flow   放音音频
			 * @author cxy
	         * @version 2018-04-15
			 */
			public Map<String,Object> fangyin(String flow){
				Map<String,Object> map = new HashMap<String, Object>();
				if(flow!=null&&"".equals(flow)){
					map.put("action", "playback");
                    //缓存中取出这步的业务名称
					map.put("flowdata","测试流程");					
					Map<String,Object> params = new HashMap<String, Object>();
					params.put("prompt",flow);
					params.put("wait", 4000);
					params.put("retry", 2);
					
					map.put("params", params);
					return map;
				}
				
				return null;
			}
			
			/**
			 * 进入流程
			 * @param flow  欢迎ivr
			 * @author cxy
	         * @version 2018-04-15
			 * @return
			 */
			public Map<String,Object> defaultfangyin(String flow){
				Map<String,Object> map = new HashMap<String, Object>();
				if(flow!=null&&"".equals(flow)){
					Map<String,Object> params = new HashMap<String, Object>();
					Map<String,Object> after_params = new HashMap<String, Object>();
					map.put("action", "start_asr");
					map.put("after_action", "playback");
					map.put("flowdata", flow);
					map.put("after_ignore_error", false);					
					params.put("min_speak_ms", 100);
					params.put("max_speak_ms", 1000);
					params.put("min_pause_ms", 300);
					params.put("max_pause_ms", 600);
					params.put("pause_play_ms", 0);//暂停播放毫秒
					params.put("threshold", 500);//VAD阈值，默认0，建议不要设置，如果一定要设置，建议 2000以下的值。
					params.put("volume", 50);
					params.put("recordpath", "");
					params.put("filter_level", 0);
					map.put("params", params);
					after_params.put("prompt", flow);
					after_params.put("wait", 5000);
					after_params.put("retry", 0);
					map.put("after_params", after_params);
					return map;
				}
				return null;
			}
			
			/**
			 * 放音后挂断
			 * @param voice  结束语
			 * @author cxy
	         * @version 2018-04-15
			 * @return
			 */
			public Map<String,Object> hangUp(String voice){
				Map<String,Object> map = new HashMap<String, Object>();
				map.put("action", "playback");
				map.put("suspend_asr", true);
				map.put("flowdata", "");
				Map<String,Object> params = new HashMap<String, Object>();
				params.put("params", voice);
				map.put("params", params);
				map.put("after_ignore_error", true);
				Map<String,Object> after_params = new HashMap<String, Object>();
				after_params.put("cause", 0);
				after_params.put("usermsg", "");
				map.put("after_params", after_params);
				return map;
			}
//			
			/**
			 * @Description(解析request转为json) @param request
			 * @author cxy
	         * @version 2018-04-19
			 */
			public static String getReqMsg(HttpServletRequest request) {
				InputStream in;
				StringBuffer json = null;
				try {
					in = request.getInputStream();
					json = new StringBuffer();
					byte[] b = new byte[4096];
					for (int n; (n = in.read(b)) != -1;) {
						json.append(new String(b, 0, n));
					}
					String msg = json.toString();
					if (msg.indexOf("text=\"") > -1) {
						msg = msg.substring(0, msg.indexOf("text=\"") + 5) + "\\\""
								+ msg.substring(msg.indexOf("text=\"") + 6, msg.indexOf("\"\"")) + "\\\"\""
								+ msg.substring(msg.indexOf("\"\"") + 2, msg.length());
					}
					return msg;
				} catch (Exception e) {
					json.append("");
				}
				return json.toString();
			}
}
