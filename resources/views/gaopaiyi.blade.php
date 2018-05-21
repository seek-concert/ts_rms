<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=8" >
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" >
    {{--  csrf令牌 --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>高拍仪页面</title>
    <script src="{{asset('js/jquery-1.11.3.min.js')}}"></script>

    <script language="javascript" type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        var DeviceMain;//主头
        var VideoMain;//主头
        var PicPath;
        var initFaceDetectSuccess;

        function plugin()
        {
            return document.getElementById('view1');
        }

        function MainView()
        {
            return document.getElementById('view1');
        }

        function thumb1()
        {
            return document.getElementById('thumb1');
        }

        function addEvent(obj, name, func)
        {
            if (obj.attachEvent) {
                obj.attachEvent("on"+name, func);
            } else {
                obj.addEventListener(name, func, false);
            }
        }
        /*打开视频*/
        function OpenVideo()
        {
            OpenVideoMain();
        }
        function OpenVideoMain()
        {
            CloseVideoMain();

            if (!DeviceMain)
                return;

            var sSubType = document.getElementById('subType1');
            var sResolution = document.getElementById('selRes1');

            var SelectType = 0;
            var txt;
            if(sSubType.options.selectedIndex != -1)
            {
                txt = sSubType.options[sSubType.options.selectedIndex].text;
                if(txt == "YUY2")
                {
                    SelectType = 1;
                }
                else if(txt == "MJPG")
                {
                    SelectType = 2;
                }
                else if(txt == "UYVY")
                {
                    SelectType = 4;
                }
            }

            var nResolution = sResolution.selectedIndex;

            VideoMain = plugin().Device_CreateVideo(DeviceMain, nResolution, SelectType);
            if (VideoMain)
            {
                MainView().View_SelectVideo(VideoMain);
                MainView().View_SetText("打开视频中，请等待...", 0);

            }
        }
        /*关闭视频*/
        function CloseVideo()
        {
            CloseVideoMain();
        }
        function CloseVideoMain()
        {
            if (VideoMain)
            {
                plugin().Video_Release(VideoMain);
                VideoMain = null;

                MainView().View_SetText("", 0);
            }
        }


        /*设备1*/
        function changesubTypeMain()
        {
            if (DeviceMain)
            {
                var sSubType = document.getElementById('subType1');
                var sResolution = document.getElementById('selRes1');
                var SelectType = 0;
                var txt;
                if(sSubType.options.selectedIndex != -1)
                {
                    var txt = sSubType.options[sSubType.options.selectedIndex].text;
                    if(txt == "YUY2")
                    {
                        SelectType = 1;
                    }
                    else if(txt == "MJPG")
                    {
                        SelectType = 2;
                    }
                    else if(txt == "UYVY")
                    {
                        SelectType = 4;
                    }
                }

                var nResolution = plugin().Device_GetResolutionCountEx(DeviceMain, SelectType);
                sResolution.options.length = 0;
                for(var i = 0; i < nResolution; i++)
                {
                    var width = plugin().Device_GetResolutionWidthEx(DeviceMain, SelectType, i);
                    var heigth = plugin().Device_GetResolutionHeightEx(DeviceMain, SelectType, i);
                    sResolution.add(new Option(width.toString() + "*" + heigth.toString()));
                }
                sResolution.selectedIndex = 0;
            }
        }
        /*高拍仪初始化*/
        function Load()
        {
            //设备接入和丢失
            //type设备类型， 1 表示视频设备， 2 表示音频设备
            //idx设备索引
            //dbt 1 表示设备到达， 2 表示设备丢失
            addEvent(plugin(), 'DevChange', function (type, idx, dbt)
            {
                if(1 == type)//视频设备
                {
                    if(1 == dbt)//设备到达
                    {
                        var deviceType = plugin().Global_GetEloamType(1, idx);
                        if(1 == deviceType)//主摄像头
                        {
                            if(null == DeviceMain)
                            {
                                DeviceMain = plugin().Global_CreateDevice(1, idx);
                                if(DeviceMain)
                                {
                                    document.getElementById('lab1').innerHTML = plugin().Device_GetFriendlyName(DeviceMain);

                                    var sSubType = document.getElementById('subType1');
                                    sSubType.options.length = 0;
                                    var subType = plugin().Device_GetSubtype(DeviceMain);
                                    if (subType & 1)
                                    {
                                        sSubType.add(new Option("YUY2"));
                                    }
                                    if (subType & 2)
                                    {
                                        sSubType.add(new Option("MJPG"));
                                    }
                                    if (subType & 4)
                                    {
                                        sSubType.add(new Option("UYVY"));
                                    }

                                    sSubType.selectedIndex = 0;
                                    changesubTypeMain();

                                    OpenVideoMain();
                                }
                            }
                        }
                        else if(2 == deviceType || 3 == deviceType)//辅摄像头
                        {

                        }
                    }
                    else if(2 == dbt)//设备丢失
                    {
                        if (DeviceMain)
                        {
                            if (plugin().Device_GetIndex(DeviceMain) == idx)
                            {
                                CloseVideoMain();
                                plugin().Device_Release(DeviceMain);
                                DeviceMain = null;

                                document.getElementById('lab1').innerHTML = "";
                                document.getElementById('subType1').options.length = 0;
                                document.getElementById('selRes1').options.length = 0;
                            }
                        }
                    }
                }
            });

            addEvent(plugin(), 'Ocr', function(flag, ret)
            {
                if (1 == flag && 0 == ret)
                {
                    var ret = plugin().Global_GetOcrPlainText(0);
                    alert(ret);
                }
            });

            addEvent(plugin(), 'IdCard', function(ret)
            {
                if (1 == ret)
                {
                    var str = GetTimeString() + "：";

                    for(var i = 0; i < 16; i++)
                    {
                        str += plugin().Global_GetIdCardData(i + 1);
                        str += ";";
                    }

                    document.getElementById("idcard").value=str;

                    var image = plugin().Global_GetIdCardImage(1);//1表示头像， 2表示正面， 3表示反面 ...
                    plugin().Image_Save(image, "C:\\idcard.jpg", 0);
                    plugin().Image_Release(image);

                    document.getElementById("idcardimg").src= "C:\\idcard.jpg";
                }
            });

            addEvent(plugin(), 'MoveDetec', function(video, id)
            {
                // 自动拍照事件
            });

            addEvent(plugin(), 'Deskew', function(video, view, list)
            {
                // 纠偏回调事件
                var count = plugin().RegionList_GetCount(list);
                for (var i = 0; i < count; ++i)
                {
                    var region = plugin().RegionList_GetRegion(list, i);

                    var x1 = plugin().Region_GetX1(region);
                    var y1 = plugin().Region_GetY1(region);

                    var width = plugin().Region_GetWidth(region);
                    var height = plugin().Region_GetHeight(region);

                    plugin().Region_Release(region);
                }

                plugin().RegionList_Release(list);
            });

            var title = document.title;
            document.title = title + plugin().version;

            MainView().Global_SetWindowName("view");
//            AssistView().Global_SetWindowName("view");
            thumb1().Global_SetWindowName("thumb");

            var ret;
            ret = plugin().Global_InitDevs();
            if(ret)
            {
                //进行人脸识别初始化时，视频应处于关闭状态
                plugin().InitFaceDetect();
            }

            if( !plugin().Global_VideoCapInit())
            {
                alert("初始化失败！");
            }
        }
        /*高拍仪释放资源*/
        function Unload()
        {
            if (VideoMain)
            {
                MainView().View_SetText("", 0);
                plugin().Video_Release(VideoMain);
                VideoMain = null;
            }
            if(DeviceMain)
            {
                plugin().Device_Release(DeviceMain);
                DeviceMain = null;
            }

            plugin().Global_DeinitDevs();

            //进行人脸识别反初始化时，视频应处于关闭状态
            plugin().DeinitFaceDetect();
        }
        /*日期*/
        function EnableDate(obj)
        {
            if (obj.checked)
            {
                var offsetx = 1000;
                var offsety = 60;

                var font;
                font = plugin().Global_CreateTypeface(50, 50, 0, 0, 2, 0, 0, 0, "宋体");

                if (VideoMain)
                {
                    var width = plugin().Video_GetWidth(VideoMain);
                    var heigth = plugin().Video_GetHeight(VideoMain);

                    plugin().Video_EnableDate(VideoMain, font, width - offsetx, heigth - offsety, 0xffffff, 0);
                }
                plugin().Font_Release(font);
            }
            else
            {
                if(VideoMain)
                {
                    plugin().Video_DisableDate(VideoMain);
                }
            }
        }
        /*属性*/
        function ShowProperty()
        {
            if(DeviceMain)
            {
                plugin().Device_ShowProperty(DeviceMain, MainView().View_GetObject());
            }
        }
        /*纠偏*/
        function Deskew(obj)
        {
            if (obj.checked)
            {
                if(VideoMain)
                {
                    plugin().Video_EnableDeskewEx(VideoMain, 1);
                }
            }
            else
            {
                if(VideoMain)
                {
                    plugin().Video_DisableDeskew(VideoMain);
                }
            }
        }
        /*手动框选*/
        function SetState(obj)
        {
            if (obj.checked)
            {
                MainView().View_SetState(2);
                document.getElementById('scansize').disabled="disabled";
            }
            else
            {
                MainView().View_SetState(1);
                document.getElementById('scansize').disabled="";
            }
        }
        /*扫描尺寸*/
        function changescansize()
        {
            var rect;
            var width =  plugin().Video_GetWidth(VideoMain);
            var heigth =  plugin().Video_GetHeight(VideoMain);

            var s = document.getElementById('scansize');
            var size = s.options.selectedIndex;

            if(size == 0)
            {
                MainView().View_SetState(1);//取消框选 状态
            }
            else if(size == 1)
            {
                rect = plugin().Global_CreateRect(width/2 - width/4, heigth/2 - heigth/4, width/2, heigth/2);
                MainView().View_SetState(2);//小尺寸
                MainView().View_SetSelectedRect(rect);

            }
            else if(size == 2)
            {
                rect = plugin().Global_CreateRect(width/2 - width/6, heigth/2 - heigth/6, width/3, heigth/3);
                MainView().View_SetState(2);//中尺寸
                MainView().View_SetSelectedRect(rect);
            }

            if(size != 0)
            {
                document.getElementById('SetState').checked = false;
                document.getElementById('SetState').disabled="disabled";
            }
            else
            {
                document.getElementById('SetState').disabled = ""
            }
        }
        /*左转*/
        function Left()
        {
            if(VideoMain)
            {
                plugin().Video_RotateLeft(VideoMain);
            }
        }
        /*右转*/
        function Right()
        {
            if(VideoMain)
            {
                plugin().Video_RotateRight(VideoMain);
            }
        }

        function GetTimeString()
        {
            var date = new Date();
            var yy = date.getFullYear().toString();
            var mm = (date.getMonth() + 1).toString();
            var dd = date.getDate().toString();
            var hh = date.getHours().toString();
            var nn = date.getMinutes().toString();
            var ss = date.getSeconds().toString();
            var mi = date.getMilliseconds().toString();

            var ret = yy + mm + dd + hh + nn + ss + mi;
            return ret;
        }

        /*拍照*/
        function Scan()
        {
            if (VideoMain)
            {
                var imgList = plugin().Video_CreateImageList(VideoMain, 0, 0);
                if (imgList) {
                    var len = plugin().ImageList_GetCount(imgList);
                    for (var i = 0; i < len; i++) {
                        var img = plugin().ImageList_GetImage(imgList, i);
                        var Name = "C:\\" + GetTimeString() + ".jpg";
                        var b = plugin().Image_Save(img, Name, 0);
                        if (b) {
                            MainView().View_PlayCaptureEffect();
                            thumb1().Thumbnail_Add(Name);

                            PicPath = Name;
                        }

                        plugin().Image_Release(img);
                    }

                    plugin().ImageList_Release(imgList);
                }
            }
        }

        /*缩略图多张上传*/
        function UploadThumbToServer(up_type)
        {
            if(up_type==1){
                alert('暂不支持多图');
            }else{
                var http =thumb1().Thumbnail_HttpUploadCheckImage("{{route('g_gaopaiyi_upl')}}?",0);
                if(http)
                {
                    var img_url = thumb1().Thumbnail_GetHttpServerInfo();
                    var smid = '{{Request()->input('smid')}}';
                    var name = '{{Request()->input('name')}}';
                    var type = '{{Request()->input('type')}}';
                    var img_type = 2;
                    parent.saomiao_img(smid,img_url,name,type,img_type);
                }
                else
                {
                    alert("上传失败！");
                }
            }
        }
        /*扫描直接上传*/
        function ScanToHttpServer()
        {
            if(VideoMain)
            {
                var img = plugin().Video_CreateImage(VideoMain, 0, MainView().View_GetObject());
                if (img)
                {
                    var http = plugin().Global_CreateHttp("{{route('g_gaopaiyi_upl')}}?");
                    if (http)
                    {
                        var file= GetTimeString()+".jpg";
                        var b = plugin().Http_UploadImage(http, img, 2, 0, file);
                        if (b)
                        {
                            var img_url = '/storage/{{date('ymd')}}/'+file;
                            var smid = '{{Request()->input('smid')}}';
                            var name = '{{Request()->input('name')}}';
                            var type = '{{Request()->input('type')}}';
                            var img_type = 1;
                            parent.saomiao_img(smid,img_url,name,type,img_type);
                        }
                        else
                        {
                            alert("上传失败");
                        }

                        plugin().Http_Release(http);
                    }else{
                        alert("上传失败");
                    }

                    plugin().Image_Release(img);
                }
            }
        }

    </script>
</head>

<body onload="Load()" onunload="Unload()">

<div>
    <!--高拍仪-->
    <object id="view1" type="application/x-eloamplugin" width="100%" height="300" name="view"></object>
</div>

<div>
    <!--高拍仪照片-->
    <object id="thumb1" type="application/x-eloamplugin" width="100%" height="150" name="thumb"></object>
</div>

<!--操作表格开始-->
<tr>
    <td>
        <label id="lab1">设备1</label>
        <select id="subType1" style="width: 90px" name="subType1" onchange="changesubTypeMain()"></select>
        <select id="selRes1" style="width: 90px" name="selRes"></select>
        扫描尺寸<select id="scansize" style="width: 90px" name="scansize" onchange="changescansize()">
            <option value ="org">原始</option>
            <option value ="mid">中</option>
            <option value="small">小</option>
        </select><br /><br />
        <input class="submit_01" type="button" value="打开视频" onclick="OpenVideo()" />
        <input class="submit_01" type="button" value="关闭视频" onclick="CloseVideo()" />
        <input class="submit_01" type="button" value="拍照"	onclick="Scan()" />
        <b>|</b>
        <input id="EnableDate" type="checkbox" value="" onclick="EnableDate(this)" />日期
        <input id="Deskew" type="checkbox" value=""  onclick="Deskew(this)"/>纠偏
        <input id="SetState" type="checkbox" value="" onclick="SetState(this)" />手动框选
        <br /><br />
        <input class="submit_01" type="button" value="左转"	onclick="Left()" />
        <input class="submit_01" type="button" value="右转"	onclick="Right()" />
        <input class="submit_01" type="button" value="属性"	onclick="ShowProperty()" />
        <b>|</b>
        <input class="submit_01" type="button" value="缩略图多张上传"	onclick="UploadThumbToServer({{Request()->input('type')}})" />
        <input class="submit_01" type="button" value="扫描直接上传"	onclick="ScanToHttpServer()" />
    </td>
</tr>
<!--操作表格结束-->
</body>
</html>