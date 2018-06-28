using System;
using System.Collections;
using System.Configuration;
using System.Data;
using System.Linq;
using System.Web;
using System.Web.Security;
using System.Web.UI;
using System.Web.UI.HtmlControls;
using System.Web.UI.WebControls;
using System.Web.UI.WebControls.WebParts;
using System.Xml.Linq;
using Newtonsoft.Json;
using Tools;
using System.IO;

namespace Web
{
    public partial class smartivr : System.Web.UI.Page
    {
        private string notify = string.Empty;
        private string calleeid = string.Empty;
        private string callerid = string.Empty;
        private string callid = string.Empty;
        private string flowdata = string.Empty;
        JSONHelper json = new JSONHelper();

        protected void Page_Load(object sender, EventArgs e)
        {
            if (!IsPostBack)
            {
                try
                {
                    string data = string.Empty;
                    string responseJson = string.Empty;
                    Smart smart = new Smart();
                    Stream stream = Request.InputStream;

                    if (stream.Length != 0)
                    {
                        StreamReader streamReader = new StreamReader(stream);
                        data = streamReader.ReadToEnd();
                        smart = JsonToObject<Smart>(data);
                        responseJson = json.ToJson(smart);
                        PublicClass.SetLogs(null, "responseJson：" + responseJson);//提交日志;
                    }

                    switch (smart.Notify)
                    {
                        case "enter":
                            DataTable dt = Help.getDataTable("select top 1* from Question where r_items_id=1 order by r_order");
                            if (dt != null && dt.Rows.Count > 0)
                            {
                                flowdata = dt.Rows[0]["r_id"].ToString();
                                asr(dt.Rows[0]["r_name"].ToString());
                            }
                            break;
                        case "asr_result":
                            if (smart.Errorcode == 0)
                            {
                                PublicClass.SetLogs(null, "Errorcode：" + smart.Message.Replace("。", "") + ":" + "select top 1 * from QuestionOption where r_Question_id=" + smart.Flowdata + " and r_OptionName = '" + smart.Message.Replace("。", "") + "'");//提交日志;
                                DataTable dttt = Help.getDataTable("select * from QuestionOption where r_Question_id=" + smart.Flowdata + " and r_OptionName = '" + smart.Message.Replace("。", "").Replace("？", "") + "'");
                                if (dttt != null && dttt.Rows.Count > 0)
                                {
                                    PublicClass.SetLogs(null, "dttt：" + smart.Message.Replace("。", ""));//提交日志;
                                    DataTable dtt = Help.getDataTable("select * from Question where r_items_id=1 and r_order=" + dttt.Rows[0]["r_NextQuestion"].ToString());
                                    if (dtt != null && dtt.Rows.Count > 0)
                                    {
                                        PublicClass.SetLogs(null, "dtt：" + dttt.Rows[0]["r_NextQuestion"].ToString());//提交日志;
                                        flowdata = dtt.Rows[0]["r_id"].ToString();
                                        asr(dtt.Rows[0]["r_name"].ToString());
                                    }
                                }
                                else
                                {
                                    PublicClass.SetLogs(null, "Errorcode：" + smart.Message.Replace("。", "") + ":" + "select * from QuestionOption where r_Question_id=" + smart.Flowdata + " and r_OptionName like '%" + smart.Message.Replace("。", "").Replace("？", "") + "%'");//提交日志;
                                    dttt = Help.getDataTable("select * from QuestionOption where r_Question_id=" + smart.Flowdata + " and r_OptionName like '%" + smart.Message.Replace("。", "").Replace("？", "") + "%'");
                                    if (dttt != null && dttt.Rows.Count > 0)
                                    {
                                        PublicClass.SetLogs(null, "dttt：" + smart.Message.Replace("。", ""));//提交日志;
                                        DataTable dtt = Help.getDataTable("select * from Question where r_items_id=1 and r_order=" + dttt.Rows[0]["r_NextQuestion"].ToString());
                                        if (dtt != null && dtt.Rows.Count > 0)
                                        {
                                            PublicClass.SetLogs(null, "dtt：" + dttt.Rows[0]["r_NextQuestion"].ToString());//提交日志;
                                            flowdata = dtt.Rows[0]["r_id"].ToString();
                                            asr(dtt.Rows[0]["r_name"].ToString());
                                        }
                                    }
                                    else
                                    {
                                        DataTable dtt = Help.getDataTable("select * from Question where r_items_id=1 and r_name='' and r_end='否'");
                                        if (dtt != null && dtt.Rows.Count > 0)
                                        {
                                            PublicClass.SetLogs(null, "dtt：" + dttt.Rows[0]["r_NextQuestion"].ToString());//提交日志;
                                            flowdata = dtt.Rows[0]["r_id"].ToString();
                                            asr(dtt.Rows[0]["r_name"].ToString());
                                        }
                                        else
                                        {
                                            dtt = Help.getDataTable("select * from Question where r_items_id=1 and  r_end='是'");
                                            if (dtt != null && dtt.Rows.Count > 0)
                                            {
                                                PublicClass.SetLogs(null, "dtt：" + dttt.Rows[0]["r_NextQuestion"].ToString());//提交日志;
                                                flowdata = dtt.Rows[0]["r_id"].ToString();
                                                asr(dtt.Rows[0]["r_name"].ToString());
                                            }
                                        }
                                    }
                                }
                                if (smart.Message == "")
                                {
                                    asr("daikuan5.41.wav");
                                }
                            }
                            else if (smart.Errorcode == -1)
                            {
                                //asr("daikuan5.41.wav");
                            }
                            else if (smart.Errorcode == -2)
                            {
                                play_after_hangup("创建文件失败请联系管理员！", "", 0);
                            }
                            else
                            {
                                play_after_hangup("未知错误！", "", 0);
                            }
                            break;
                        case "getdtmf_result":
                            string dtmf = smart.Flowdata.Substring(7);
                            smart.Flowdata = "getdtmf" + smart.Message;
                            playback("你输入的按键是 " + smart.Message + "，上次输入的按键是 " + dtmf);
                            break;
                        case "playback_result":
                            if (smart.Flowdata.IndexOf("getdtmf")>0)
                            {
                                getdtmf("请继续输入按键", 1);
                            }
                            //else if (smart.Flowdata == "是的")
                            //{
                            //    smart.Flowdata = "";
                            //    asr("是的已经讲过了，继续说命令吧！");
                            //}
                            else if (smart.Flowdata == "vad")
                            {
                                vad("请说话吧，后面是放音打断测试，语音活动检测(Voice Activity Detection,VAD)又称语音端点检测,语音边界检测。目的是从声音信号流里识别和消除长时间的静音期，以达到在不降低业务质量的情况下节省话路资源的作用，它是IP电话应用的重要组成部分。静音抑制可以节省宝贵的带宽资源，可以有利于减少用户感觉到的端到端的时延。！");
                            }
                            else
                            {
                                //asr("请继续说命令吧！");
                            }
                            break;
                        case "bridge_result":
                            if (smart.Errorcode == 0)
                            {
                                asr("呼叫分机成功，请继续测试");
                            }
                            else
                            {
                                asr("呼叫分机失败，错误代码 "+smart.Errorcode+" 请继续测试");
                            }
                            break;
                        case "leave":
                            noop("");
                            break;
                        default:
                            play_after_hangup("未知通知类型","",0);
                            break;
                    }

                    //DataTable dt_params = new DataTable();
                    //dt_params.Columns.Add("prompt", typeof(string));
                    //dt_params.Columns.Add("max_waiting_ms", typeof(int));
                    //dt_params.Columns.Add("retry", typeof(int));
                    //dt_params.Columns.Add("mode", typeof(int));
                    //DataRow dr_params = dt_params.NewRow();
                    //dr_params["prompt"] = "\u6b22\u8fce\u81f4\u7535\u9f0e\u9f0e\u8f6f\u4ef6\uff0c\u8bf7\u8bf4\u627e\u8c01";
                    //dr_params["max_waiting_ms"] = "5000";
                    //dr_params["retry"] = "3";
                    //dr_params["mode"] = "0";
                    //dt_params.Rows.Add(dr_params);

                    //DataTable dt = new DataTable();
                    //dt.Columns.Add("action", typeof(string));
                    //dt.Columns.Add("flowdata", typeof(string));
                    //dt.Columns.Add("params", typeof(object));
                    //DataRow dr = dt.NewRow();
                    //dr["action"] = "asr";
                    //dr["flowdata"] = "";
                    //dr["params"] = json.ToJson(dt_params);
                    //dt.Rows.Add(dr);

                    //Response.Write(json.ToJson(dt));
                    //PublicClass.SetLogs(null, "smartivr首页加载：" + data + ":" + ":" + smart.Calleeid + ":" + json.ToJson(dt));//提交日志;
                    //switch (smart.Notify)
                    //{
                    //    case "enter":
                    //        break;
                    //}
                }
                catch (Exception ex)
                {
                    play_after_hangup("数据错误："+ex.Message,"",0);
                    PublicClass.SetLogs(null, "smartivr首页加载错误：" + ex.Message);//提交日志;
                }
            }
        }

        /// <summary>
        /// JSON转换为对象
        /// </summary>
        /// <typeparam name="T"></typeparam>
        /// <param name="jsonString"></param>
        /// <returns></returns>
        public static T JsonToObject<T>(string jsonString)
        {
            T res = JsonConvert.DeserializeObject<T>(jsonString);
            return res;
        }

        public string hangup(string usermsg, int cause)
        {
            DataTable dt_params = new DataTable();
            dt_params.Columns.Add("cause", typeof(int));
            dt_params.Columns.Add("usermsg", typeof(string));
            DataRow dr_params = dt_params.NewRow();
            dr_params["cause"] = cause;
            dr_params["usermsg"] = usermsg;
            dt_params.Rows.Add(dr_params);

            DataTable dt = new DataTable();
            dt.Columns.Add("action", typeof(string));
            dt.Columns.Add("flowdata", typeof(string));
            dt.Columns.Add("params", typeof(object));
            DataRow dr = dt.NewRow();
            dr["action"] = "hangup";
            dr["flowdata"] = flowdata;
            dr["params"] = json.ToJson(dt_params);
            dt.Rows.Add(dr);

            string str = json.ToJson(dt);
            Response.Write(str);
            PublicClass.SetLogs(null, "hangup：" + str);//提交日志;
            return str;
        }

        public string play_after_hangup(string prompt, string usermsg, int cause)
        {
            DataTable dt_params = new DataTable();
            dt_params.Columns.Add("prompt", typeof(string));
            DataRow dr_params = dt_params.NewRow();
            dr_params["prompt"] = prompt;
            dt_params.Rows.Add(dr_params);

            DataTable dt_after_params = new DataTable();
            dt_after_params.Columns.Add("cause", typeof(int));
            dt_after_params.Columns.Add("usermsg", typeof(string));
            DataRow dr_after_params = dt_after_params.NewRow();
            dr_after_params["cause"] = cause;
            dr_after_params["usermsg"] = usermsg;
            dt_after_params.Rows.Add(dr_after_params);

            DataTable dt = new DataTable();
            dt.Columns.Add("action", typeof(string));
            dt.Columns.Add("flowdata", typeof(string));
            dt.Columns.Add("params", typeof(object));
            dt.Columns.Add("after_action", typeof(string));
            dt.Columns.Add("after_params", typeof(object));
            DataRow dr = dt.NewRow();
            dr["action"] = "playback";
            dr["flowdata"] = flowdata;
            dr["params"] = json.ToJson(dt_params);
            dr["after_action"] = "hangup";
            dr["after_params"] = json.ToJson(dt_after_params);
            dt.Rows.Add(dr);

            string str = json.ToJson(dt);
            Response.Write(str);
            PublicClass.SetLogs(null, "play_after_hangup：" + str);//提交日志;
            return str;
        }

        public string noop(string usermsg)
        {
            DataTable dt_params = new DataTable();
            dt_params.Columns.Add("usermsg", typeof(string));
            DataRow dr_params = dt_params.NewRow();
            dr_params["usermsg"] = usermsg;
            dt_params.Rows.Add(dr_params);

            DataTable dt = new DataTable();
            dt.Columns.Add("action", typeof(string));
            dt.Columns.Add("flowdata", typeof(string));
            dt.Columns.Add("params", typeof(object));
            DataRow dr = dt.NewRow();
            dr["action"] = "noop";
            dr["flowdata"] = flowdata;
            dr["params"] = json.ToJson(dt_params);
            dt.Rows.Add(dr);

            string str = json.ToJson(dt);
            Response.Write(str);
            PublicClass.SetLogs(null, "noop：" + str);//提交日志;
            return str;
        }

        public string playback(string prompt)
        {
            DataTable dt_params = new DataTable();
            dt_params.Columns.Add("prompt", typeof(string));
            DataRow dr_params = dt_params.NewRow();
            dr_params["prompt"] = prompt;
            dt_params.Rows.Add(dr_params);

            DataTable dt = new DataTable();
            dt.Columns.Add("action", typeof(string));
            dt.Columns.Add("flowdata", typeof(string));
            dt.Columns.Add("params", typeof(object));
            DataRow dr = dt.NewRow();
            dr["action"] = "playback";
            dr["flowdata"] = flowdata;
            dr["params"] = json.ToJson(dt_params);
            dt.Rows.Add(dr);

            string str = json.ToJson(dt);
            Response.Write(str);
            PublicClass.SetLogs(null, "playback：" + str);//提交日志;
            return str;
        }

        public string asr(string prompt)
        {
            DataTable dt_params = new DataTable();
            dt_params.Columns.Add("prompt", typeof(string));
            dt_params.Columns.Add("max_waiting_ms", typeof(int));
            dt_params.Columns.Add("retry", typeof(int));
            dt_params.Columns.Add("mode", typeof(int));
            dt_params.Columns.Add("disable_asr", typeof(object));
            DataRow dr_params = dt_params.NewRow();
            dr_params["prompt"] = prompt;
            dr_params["max_waiting_ms"] = "5000";
            dr_params["retry"] = "0";
            dr_params["mode"] = "0";
            dr_params["disable_asr"] = "false";
            dt_params.Rows.Add(dr_params);

            DataTable dt = new DataTable();
            dt.Columns.Add("action", typeof(string));
            dt.Columns.Add("flowdata", typeof(string));
            dt.Columns.Add("params", typeof(object));
            DataRow dr = dt.NewRow();
            dr["action"] = "asr";
            dr["flowdata"] = flowdata;
            dr["params"] = json.ToJson(dt_params);
            dt.Rows.Add(dr);

            string str = json.ToJson(dt);
            Response.Write(str);
            PublicClass.SetLogs(null, "asr：" + str);//提交日志;
            return str;
        }

        public string vad(string prompt)
        {
            DataTable dt_params = new DataTable();
            dt_params.Columns.Add("prompt", typeof(string));
            dt_params.Columns.Add("max_waiting_ms", typeof(int));
            dt_params.Columns.Add("min_pause_ms", typeof(int));
            dt_params.Columns.Add("retry", typeof(int));
            dt_params.Columns.Add("mode", typeof(int));
            dt_params.Columns.Add("disable_asr", typeof(object));
            DataRow dr_params = dt_params.NewRow();
            dr_params["prompt"] = prompt;
            dr_params["max_waiting_ms"] = "5000";
            dr_params["min_pause_ms"] = "600";
            dr_params["retry"] = "0";
            dr_params["mode"] = "0";
            dr_params["disable_asr"] = "true";
            dt_params.Rows.Add(dr_params);

            DataTable dt = new DataTable();
            dt.Columns.Add("action", typeof(string));
            dt.Columns.Add("flowdata", typeof(string));
            dt.Columns.Add("params", typeof(object));
            DataRow dr = dt.NewRow();
            dr["action"] = "asr";
            dr["flowdata"] = flowdata;
            dr["params"] = json.ToJson(dt_params);
            dt.Rows.Add(dr);

            string str = json.ToJson(dt);
            Response.Write(str);
            PublicClass.SetLogs(null, "vad：" + str);//提交日志;
            return str;
        }

        public string bridge(string number, string callerid, string gateway, string prompt, string background)
        {
            DataTable dt_params = new DataTable();
            dt_params.Columns.Add("number", typeof(string));
            dt_params.Columns.Add("callerid", typeof(string));
            dt_params.Columns.Add("gateway", typeof(string));
            dt_params.Columns.Add("prompt", typeof(string));
            dt_params.Columns.Add("background", typeof(string));
            DataRow dr_params = dt_params.NewRow();
            dr_params["number"] = number;
            dr_params["callerid"] = callerid;
            dr_params["gateway"] = gateway;
            dr_params["prompt"] = prompt;
            dr_params["background"] = background;
            dt_params.Rows.Add(dr_params);

            DataTable dt = new DataTable();
            dt.Columns.Add("action", typeof(string));
            dt.Columns.Add("flowdata", typeof(string));
            dt.Columns.Add("params", typeof(object));
            DataRow dr = dt.NewRow();
            dr["action"] = "bridge";
            dr["flowdata"] = flowdata;
            dr["params"] = json.ToJson(dt_params);
            dt.Rows.Add(dr);

            string str = json.ToJson(dt);
            Response.Write(str);
            PublicClass.SetLogs(null, "bridge：" + str);//提交日志;
            return str;
        }

        public string deflect(string number)
        {
            DataTable dt_params = new DataTable();
            dt_params.Columns.Add("number", typeof(string));
            DataRow dr_params = dt_params.NewRow();
            dr_params["number"] = number;
            dt_params.Rows.Add(dr_params);

            DataTable dt = new DataTable();
            dt.Columns.Add("action", typeof(string));
            dt.Columns.Add("flowdata", typeof(string));
            dt.Columns.Add("params", typeof(object));
            DataRow dr = dt.NewRow();
            dr["action"] = "deflect";
            dr["flowdata"] = flowdata;
            dr["params"] = json.ToJson(dt_params);
            dt.Rows.Add(dr);

            string str = json.ToJson(dt);
            Response.Write(str);
            PublicClass.SetLogs(null, "deflect：" + str);//提交日志;
            return str;
        }

        public string getdtmf(string prompt, int max)
        {
            DataTable dt_params = new DataTable();
            dt_params.Columns.Add("prompt", typeof(string));
            dt_params.Columns.Add("invalid_prompt", typeof(string));
            dt_params.Columns.Add("min", typeof(int));
            dt_params.Columns.Add("max", typeof(int));
            dt_params.Columns.Add("tries", typeof(int));
            dt_params.Columns.Add("timeout", typeof(int));
            dt_params.Columns.Add("digit_timeout", typeof(int));
            dt_params.Columns.Add("terminators", typeof(string));
            DataRow dr_params = dt_params.NewRow();
            dr_params["prompt"] = prompt;
            dr_params["invalid_prompt"] = "按键无效";
            dr_params["min"] = "0";
            dr_params["max"] = max;
            dr_params["tries"] = "1";
            dr_params["timeout"] = "5000";
            dr_params["digit_timeout"] = "3000";
            dr_params["terminators"] = "#";
            dt_params.Rows.Add(dr_params);

            DataTable dt = new DataTable();
            dt.Columns.Add("action", typeof(string));
            dt.Columns.Add("flowdata", typeof(string));
            dt.Columns.Add("params", typeof(object));
            DataRow dr = dt.NewRow();
            dr["action"] = "getdtmf";
            dr["flowdata"] = flowdata;
            dr["params"] = json.ToJson(dt_params);
            dt.Rows.Add(dr);

            string str = json.ToJson(dt);
            Response.Write(str);
            PublicClass.SetLogs(null, "getdtmf：" + str);//提交日志;
            return str;
        }
    }

    public class Smart
    {
        private string notify = string.Empty;
        private string calleeid = string.Empty;
        private string callerid = string.Empty;
        private string callid = string.Empty;
        private string flowdata = string.Empty;
        private int errorcode;
        private string message = string.Empty;

        public string Message
        {
            get { return message; }
            set { message = value; }
        }

        public int Errorcode
        {
            get { return errorcode; }
            set { errorcode = value; }
        }

        public string Flowdata
        {
            get { return flowdata; }
            set { flowdata = value; }
        }

        /// <summary>
        /// 通话ID
        /// </summary>
        public string Callid
        {
            get { return callid; }
            set { callid = value; }
        }

        /// <summary>
        /// 通话被叫号码
        /// </summary>
        public string Callerid
        {
            get { return callerid; }
            set { callerid = value; }
        }

        /// <summary>
        /// 通话被叫号码
        /// </summary>
        public string Calleeid
        {
            get { return calleeid; }
            set { calleeid = value; }
        }

        /// <summary>
        /// 通知类型
        /// </summary>
        public string Notify
        {
            get { return notify; }
            set { notify = value; }
        }
    }
}
