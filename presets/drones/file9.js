var keys = new Tone.GrainPlayer({
			"url" : "/presets/drones/dsupmp3kiteupmp3.mp3"
	
		}).connect(filter);
					
document.querySelector("tone-oscilloscope").bind(keys);
				document.querySelector("tone-grain-player").bind(keys);
document.querySelector("tone-play-toggle").bind(keys);
					
				


	
	