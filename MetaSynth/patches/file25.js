var keys = new Tone.GrainPlayer({
			"url" : "https://raw.githubusercontent.com/publicsamples/Metasynth-Loops/master/MP3/swan130.mp3"
	
		}).connect(filter);
					
document.querySelector("tone-fft").bind(keys);
				document.querySelector("tone-grain-player").bind(keys);
					
				


	