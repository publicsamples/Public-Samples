---
title: bs
date: 2019-08-30 22:36:00 Z
---

<html>
<head>
 
	<script src="../libraries/Tone//Tone.js"></script>
	<script src="../libraries/Tone/tonejs-ui.js"></script>
    <script src="../libraries/Tone/NexusUI.js"></script>

<style type="text/css">
	

    tone-content {
	width:500px;
	font-size:10px;
}
   #content {
	   margin:0;
	   
   }

    </style>


</head>
<body>
<div>
	
	  </div>
			<tone-content>
            <tone-transport></tone-transport>
			<tone-step-sequencer id="seq1"></tone-step-sequencer>
			<tone-step-sequencer id="seq2"></tone-step-sequencer>
			<tone-step-sequencer id="seq3"></tone-step-sequencer>
				<tone-auto-filter collapsed frequency id="frequency"></tone-auto-filter>
				<tone-ping-pong-delay collapsed></tone-ping-pong-delay>
				<tone-freeverb collapsed=""></tone-freeverb>
			</tone-content>
		</tone-example>

		<script type="text/javascript">
			
			var eq = new Tone.EQ3().toMaster({
					})
				
					
				
					var verb = new Tone.Freeverb({
										"frequency" : "8n",
										"feedback" : 0.6,
										"wet" : 0.0
									}).connect(eq);	
									
									//the feedback delay
									var feedbackDelay = new Tone.PingPongDelay({
										"frequency" : "8n",
										"feedback" : 0.6,
										"wet" : 0.0
									}).connect(verb);		
				
					var filter = new Tone.AutoFilter({
								"frequency" : 100,
								"wet" : 0.0,
						"frequency" : "8n"
							}).connect(feedbackDelay).start();
				

				
	
	

		
			var keys = new Tone.Players({
"1" : "DynamicSaw/FILE25.mp3","2" : "DynamicSaw/FILE24.mp3","3" : "DynamicSaw/FILE23.mp3","4" : "DynamicSaw/FILE22.mp3","5" : "DynamicSaw/FILE21.mp3","6" : "DynamicSaw/FILE20.mp3","7" : "DynamicSaw/FILE19.mp3","8" : "DynamicSaw/FILE18.mp3","9" : "DynamicSaw/FILE17.mp3","10" : "DynamicSaw/FILE16.mp3","11" : "DynamicSaw/FILE15.mp3","12" : "DynamicSaw/FILE14.mp3"
						
			}, {
				"volume" : 00,
				"fadeOut" : "8512n",
				}).connect(filter);
				
			
				//the notes
				var noteNames = ["1", "2", "3", "4"];
				var noteNames2 = ["5", "6", "7", "8"];	
				var noteNames3 = ["9", "10", "11", "12"];		
				
		
				var loop = new Tone.Sequence(function(time, col){
					var column = document.querySelector("#seq1").currentColumn;
					column.forEach(function(val, i){
						if (val){
							//slightly randomized velocities
							var vel = Math.random() * 0.5 + 0.5;
							keys.get(noteNames[i]).start(time, 0, "16n", 560, vel);
						}
					});
					//set the columne on the correct draw frame
					Tone.Draw.schedule(function(){
						document.querySelector("#seq1").setAttribute("highlight", col);
					}, time);
				}, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], "16n").start(0);
		
			

				var loop2 = new Tone.Sequence(function(time, col){
					var column2 = document.querySelector("#seq2").currentColumn;
					column2.forEach(function(val, i){
						if (val){
							//slightly randomized velocities
							var vel2 = Math.random() * 0.5 + 0.5;
							keys.get(noteNames2[i]).start(time, 0, "16n", 560, vel2);
						}
					});
					//set the columne on the correct draw frame
					Tone.Draw.schedule(function(){
						document.querySelector("#seq2").setAttribute("highlight", col);
					}, time);
				}, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], "16n").start(0);
		
		
		
					var loop3 = new Tone.Sequence(function(time, col){
						var column3 = document.querySelector("#seq3").currentColumn;
						column3.forEach(function(val, i){
							if (val){
								//slightly randomized velocities
								var vel3 = Math.random() * 0.5 + 0.5;
								keys.get(noteNames3[i]).start(time, 0, "16n", 560, vel3);
							}
						});
						//set the columne on the correct draw frame
						Tone.Draw.schedule(function(){
							document.querySelector("#seq3").setAttribute("highlight", col);
						}, time);
					}, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], "16n").start(0);
			
		
		
			
				//bind the interface
				document.querySelector("tone-auto-filter").bind(filter);
				document.querySelector("tone-transport").bind(Tone.Transport);
				document.querySelector("tone-freeverb").bind(verb);
		document.querySelector("tone-ping-pong-delay").bind(feedbackDelay);

				Tone.Transport.on("stop", () => {
					setTimeout(() => {
						document.querySelector("#seq1").setAttribute("highlight", "-1");
						document.querySelector("#seq2").setAttribute("highlight", "-1");
						document.querySelector("#seq3").setAttribute("highlight", "-1");
					}, 100);
				
				});
		
			
		</script>
    
</body>
</html>
	
