var keys = new Tone.GrainPlayer({
			"url" : "/Demos/presets/xpanderloops/file6.mp3",
	"loop" : true
				}).connect(filter);
								
		
		document.querySelector("tone-grain-player").bind(keys);
document.querySelector("tone-play-toggle").bind(keys);

					
	
				

					
				


	
	