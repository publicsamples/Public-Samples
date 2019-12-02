var keys = new Tone.GrainPlayer({
			"url" : "https://raw.githubusercontent.com/publicsamples/Metasynth-Loops/master/MP3/3.mp3"
	
		}).connect(filter);
					
document.querySelector("tone-oscilloscope").bind(keys);
				document.querySelector("tone-grain-player").bind(keys);
document.querySelector("tone-play-toggle").bind(keys);
					
				

