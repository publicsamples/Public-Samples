var keys = new Tone.Sampler({
		
			"C1" : "FILE1.[mp3|ogg]",
			"E1" : "FILE5.[mp3|ogg]",
			"G#1" : "FILE9.[mp3|ogg]",
			"C2" : "FILE13.[mp3|ogg]",
			"E2" : "FILE17.[mp3|ogg]",
			"G#2" : "FILE21.[mp3|ogg]",
			"C3" : "FILE25.[mp3|ogg]"
			
				
					}, {

						"baseUrl" : "https://raw.githubusercontent.com/publicsamples/Arturia-Microbrute/master/MP3/MBStrings/"
					})					.connect(filter);
					
				
