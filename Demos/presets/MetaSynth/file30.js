var keys = new Tone.GrainPlayer({
			"url" : "https://raw.githubusercontent.com/publicsamples/Metasynth-Loops/master/MP3/troublement60.mp3"
	
				}).connect(filter);
								
		
		document.querySelector("tone-grain-player").bind(keys);
document.querySelector("tone-play-toggle").bind(keys);

					
	
				

		
					
				


	