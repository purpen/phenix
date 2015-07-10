<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Doggy framework</title>
	{literal}
	<style type="text/css">
		* {
			margin:0;
			padding:0;
		}
		body {
			background:#FFFFFF;			
		}
		form {
			border:0px none;
			padding:0px;
		}
		a:link {			
			text-decoration:none;
		}
		a:hover {
			color:#fff;
			background-color:#9ACD32;			
			text-decoration:none;			
		}
		a:active {
			background:#FF9933 none repeat scroll 0%;
			color:#FFFFFF;
			text-decoration:none;
		}
		a img {
			border-width:0pt;
		}
		h1 {			
			border-bottom:4px solid #eee;
			background-color:#fff;
			color:#444;
			padding-left:10px;
			font-size:14pt;			
		}
		h3{
			background-color:#f4f4f4;
			border-bottom:1px dotted #ccc;
			font-size:14px;
			padding-left:.4em;
			cursor:pointer;
		}
		.tab_nav ul{			
			display:block;
			height:30px;
			list-style-image:none;
			list-style-position:outside;
			list-style-type:none;
			padding-left:25px;			
			background-color:#9ACD32;
		}
		.tab_nav li{
			border:1px solid #9ACD32;
			border-right:1px solid #FFFFFF;
			display:block;
			float:left;
			font-weight:bold;
			font-size:14px;
			margin:8px 5px 5px;
			padding-right:8px;
			padding-left:8px;
			color:#444;
			cursor:pointer;
		}
		.tab_nav li.current{
			color:#fff;
		}
		.tab_nav li:hover{
			border:1px solid #fff;
			color:#BAFF49;
		}
		.list {
		    font-size:12px;
		    color:#444;
		}
		.list div{
		  margin:2px;
		  margin-left:2em;
		}
		.footer{
			font-size:small;
			color:#ccc;
			border-top:1px solid #eee;
			background-color:#f1f1f1;
			text-align:left;
		}
		.footer a{
			font-size:small;
		}
		.clear{
			clear:both;
		}
		
	</style>
	{/literal}
</head>
<body>
    <h1>Doggy framework</h1>
	<div class="tab_nav">
		<ul>
			<li class="current"><a>运行信息</a></li>			
		</ul>
		<div class="clear"></div>
	</div>
	<div id="body">
		<div>
			Version: {$doggy_version}
		</div>
		<h3>Request parameters</h3>
		  <div class="list">
		      {foreach key=k item=p from=$params }
		          <div>
		              <span style="color:blue;">{$k}</span>:
		              <span style="color:#B4B19A;">{$p}</span>
		          </div>
		      {/foreach}
		  </div>
		<div>		
		</div>
		<h3>Dispatcher - interceptors</h3>
		<div class="list">
			{foreach item=i from=$dispatcher_info.interceptors }
			 <div>{$i}</div>
			{foreachelse}
			<div>No interceptors.</div>
			{/foreach}	
		</div>
		<h3>Dispatcher - before filters</h3>
		<div class="list">
			{foreach item=i from=$dispatcher_info.filters.before }
			 <div>{$i}</div>
			{foreachelse}
			<div>No filters/before.</div>
			{/foreach}	
		</div>
		<h3>Dispatcher - after filters</h3>
		<div class="list">
			{foreach item=i from=$dispatcher_info.filters.after }
			 <div>{$i}</div>
			{foreachelse}
			<div>No filters/before.</div>
			{/foreach}	
		</div>
		
		<div class="list">
		  Parameter Interceptor test:
		  <div class="list"><span>Doggy action parameter:</span><span style="color:blue;">{$doggy}</span> 
            </div>
		</div>
		<h3>Session Test</h3>
		{if $visits==0 }
		<div class="list">first session start,visits:{$visits}</div>
		{else}
		<div class="list">session works,visits:{$visits}</div>
		{/if}
		
		<h3>Module information</h3>
		<div class="list">
			{foreach item=m from=$modules }
			 <div>{$m.namespace} - default_action:{$m.default_action} state:{$m.state}</div>
			{foreachelse}
			<div>No module</div>
			{/foreach}	
		</div>
	</div>
	<div class="footer">
		<p>Copyright 2006-2009 doggy.framework (c)ChinaVisual.com,Author(s)  <a href="http://night9.cn">Night Sailer<a></p>
	</div>
</body>
<!--doggy_run_ok-->
</html>