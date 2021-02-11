var keys = new Tone.Sampler({
		
			"C3" : "file1.[mp3|ogg]",
			"E3" : "file5.[mp3|ogg]",
			"G#3" : "file9.[mp3|ogg]",
			"C4" : "file13.[mp3|ogg]",
			"E4" : "file17.[mp3|ogg]",
			"G4" : "file20.[mp3|ogg]"
			
				
					}, {

						"baseUrl" : "https://raw.githubusercontent.com/publicsamples/Roland-Juno-6/master/mp3/LFOPad/"
					})					.connect(filter);
					
				

