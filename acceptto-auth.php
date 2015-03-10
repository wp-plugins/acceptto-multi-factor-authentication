<?php
/**
 * Template Name: Acceptto Auth 
 */
?>
<!Doctype html>
<html>
<head>
	<title>Login With Accepto</title>
	<style type="text/css">
		*{margin:0; padding: 0; font-family: arial;}
		.header{width: 100%; background-color: #333; color: #fff; padding: 20px 0; text-align: center;}
		.header h1{font-family: arial; font-size: 24px; margin: 0 auto; display: block;}
		.mid_content{padding: 20px 0; width: 100%; text-align: center;}
		.model_pop{width: 300px; border: 1px solid #ccc; margin: 0 auto; padding: 20px;}
		.model_pop label{padding: 5px 0; display: block; text-align: left;}
		.model_pop input[type='text']{width:95%; padding: 5px 2%; margin-bottom: 10px;}
		.model_pop input[type='submit']{width:45%; padding: 8px 2%; background-color: #333; color: #fff; border: 1px solid #000; float:left;}
		.model_pop input[type='submit']:first-child{margin-right:10%;}
	</style>
	<script type="text/javascript">
			function selfMe()
             {
                window.opener.location.reload();
             }
			 function CloseMe()
             {
                window.opener.location.reload();
                setTimeout(function() {window.close();}, 2000);
             }
             function ReloadMe()
             {
                setTimeout(function() {window.location.reload()();}, 1000);
             }
             function BackMe()
             {
                setTimeout(function() {window.history.back();}, 1000);
             }
             function BackToUrl()
             {
                setTimeout(function() {window.location.href = '<?php echo home_url(); ?>/wp-login.php';}, 1000);
             }
    </script>
</head>
<body>
<div class="header">
	<h1>Login With Accepto</h1>
</div>
<div class="mid_content">
<?php
acceptto_auth();
?>
<span style="clear:both; height:1px;"></span>
</div>
<div class="footer">

</div>
</body>
</html>