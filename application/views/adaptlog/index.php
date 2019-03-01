<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>POC区域调度批次日志</title>

	<style type="text/css">
	::selection { background-color: #E13300; color: white; }
	::-moz-selection { background-color: #E13300; color: white; }

	body {
		background-color: #fff;
		margin: 40px;
		font: 13px/20px normal Helvetica, Arial, sans-serif;
		color: #4F5155;
	}

	a {
		color: #003399;
		background-color: transparent;
		font-weight: normal;
	}

	h1 {
		color: #444;
		background-color: transparent;
		border-bottom: 1px solid #D0D0D0;
		font-size: 19px;
		font-weight: normal;
		margin: 0 0 14px 0;
		padding: 14px 15px 10px 15px;
	}

	code {
		font-family: Consolas, Monaco, Courier New, Courier, monospace;
		font-size: 12px;
		background-color: #f9f9f9;
		border: 1px solid #D0D0D0;
		color: #002166;
		display: block;
		margin: 14px 0 14px 0;
		padding: 12px 10px 12px 10px;
	}

	#body {
		margin: 0 15px 0 15px;
	}

	p.footer {
		text-align: right;
		font-size: 11px;
		border-top: 1px solid #D0D0D0;
		line-height: 32px;
		padding: 0 10px 0 10px;
		margin: 20px 0 0 0;
	}

	#container {
		margin: 10px;
		border: 1px solid #D0D0D0;
		box-shadow: 0 0 8px #D0D0D0;
	}
	</style>
</head>
<body>

<div id="container">
	<h1>POC区域调度批次日志 <a href="http://odin.xiaojukeji.com/#/monitor/screen/20747?category=cluster&isLeaf=true&nid=281450&ns=hna.its-schedule.its-tool.ipd-cloud.didi.com" target="_blank">监控大盘</a></h1>
	<table>
		<tr>
			<th>area_id</th>
			<th>trace_id</th>
			<th>dltag</th>
			<th>log</th>
			<th>log_time</th>
			<th>created_at</th>
		</tr>
		<?php
		foreach ($list as $row) {
			?>
			<tr>
				<td><?php echo $row["rel_id"]?></td>
				<td><a href="./junction?trace_id=<?php echo $row["trace_id"]?>&dltag=_didi_Junction.Do.Start"><?php echo $row["trace_id"]?></a></td>
				<td><?php echo $row["dltag"]?></td>
				<td><?php echo $row["log"]?></td>
				<td><?php echo $row["log_time"]?></td>
				<td><?php echo $row["created_at"]?></td>
			</tr>
			<?php
		}
		?>
	</table>
	<div><?php echo $page;?></div>
</div>

</body>
</html>