var keys = new Tone.GrainPlayer({
			"url" : "/Demos/presets/drones/EFXDronemono.mp3"
	
		}).connect(filter);
					

				document.querySelector("tone-grain-player").bind(keys);
document.querySelector("tone-play-toggle").bind(keys);
					
				


	
	