<body onload="loadJS('https://modularsamples.com/MetaSynth/patches/file1.js');">

</body>

function loadJS(file) {
// DOM: Create the script element
var jsElm = document.createElement("script");
// set the type attribute
jsElm.type = "application/javascript";
// make the script element load file
jsElm.src = file;
// finally insert the element to the body element in order to load the script
document.body.appendChild(jsElm);
}

var eq = new Tone.Gain().toMaster({
})

var verb = new Tone.Freeverb({
"wet" : 0.0
}).connect(eq);	

//the feedback delay
var feedbackDelay = new Tone.PingPongDelay({
"wet" : 0.0
}).connect(verb);		


var filter = new Tone.AutoFilter({
	"wet" : 0.0
}).connect(feedbackDelay).start();		


	
Nexus.colors.accent = "#ccc"
Nexus.colors.fill = "#333"


//filter Controls

var fwet = new Nexus.Slider('#fwet', {
'mode': 'relative', // "absolute" or "absolute"
'size': [20, 50],
"min": 0,
"max" : 1,
'value' : 0
})

fwet.on('change', function(v) {
filter.wet.value = v;
});

var baseFrequency = new Nexus.Slider('#baseFrequency', {
'mode': 'absolute', // "absolute" or "absolute"
'size': [20, 50],
'min': 20,
'max': 4000,
'step': 0,
	'value': 200
})

baseFrequency.on('change', function(v) {
filter.filter.frequency.value = v;
});

var Q = new Nexus.Slider('#Q', {
'mode': 'absolute', // "absolute" or "absolute"
'size': [20, 50],
'min': 0,
'max': 10,
'step': 0,
	'value': 1
})

Q.on('change', function(v) {
filter.filter.Q.value = v;
});

var depth = new Nexus.Slider('#depth', {
'mode': 'absolute', // "absolute" or "absolute"
'size': [20, 50],
'min': 0.00,
'max': 1.00,
'step': 0,
'value': 0
})

depth.on('change', function(v) {
filter.depth.value = v;
});

var ffrequency = new Nexus.Slider('#ffrequency', {
'mode': 'absolute', // "absolute" or "absolute"
'size': [20, 50],
'min': 0,
'max': 20,
'step': 0,
'value': 4
})

ffrequency.on('change', function(v) {
filter.frequency.value = v;
});

//delay Controls

var dwet = new Nexus.Slider('#dwet', {
'mode': 'relative', // "absolute" or "absolute"
'size': [20, 50],
'min': 0.0,
'max': 1.0,
'step': 0,
'value': 0
})

dwet.on('change', function(v) {
feedbackDelay.wet.value = v;
});

var dtime = new Nexus.Slider('#dtime', {
'mode': 'absolute', // "absolute" or "absolute"
'size': [20, 50],
'min': 0.01,
'max': 1.0,
'step': 0,
'value': 0.25
})

dtime.on('change', function(v) {
feedbackDelay.delayTime.value = v;
});

var dfeedback = new Nexus.Slider('#dfeedback', {
'mode': 'absolute', // "absolute" or "absolute"
'size': [20, 50],
'min': 0.0,
'max': 0.95,
'step': 0,
'value': 0.60
})

dfeedback.on('change', function(v) {
feedbackDelay.feedback.value = v;
});

//Reverb Controls

var rwet = new Nexus.Slider('#rwet', {
'mode': 'relative', // "absolute" or "absolute"
'size': [20, 50],
'min': 0,
'max': 1,
'step': 0,
'value': 0
})

rwet.on('change', function(v) {
verb.wet.value = v;
});

var roomSize = new Nexus.Slider('#roomSize', {
'mode': 'absolute', // "absolute" or "absolute"
'size': [20, 50],
'min': 0.10,
'max': 0.95,
'step': 0,
'value': 0.70
})

roomSize.on('change', function(v) {
verb.roomSize.value = v;
});

var dampening = new Nexus.Slider('#dampening', {
'mode': 'absolute', // "absolute" or "absolute"
'size': [20, 50],
'min': 100,
'max': 5000,
'step': 0,
'value': 3000
})

dampening.on('change', function(v) {
verb.dampening.value = v;
});

function stop() {
keys.stop();
keys.disconnect();


}

var power = new Nexus.TextButton("power", { mode: "toggle", size: [140, 40], text: "Start", alternate: "Stop"});		
power.on('change', v => v ? keys.start() : keys.stop())


</script>