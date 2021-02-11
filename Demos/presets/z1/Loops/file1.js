var keys = new Tone.GrainPlayer({
			"url" : "/presets/z1/Loops/4lfo.mp3"
	
		}).connect(filter);
					
		document.querySelector("tone-oscilloscope").bind(keys);
		document.querySelector("tone-grain-player").bind(keys);
document.querySelector("tone-play-toggle").bind(keys);

