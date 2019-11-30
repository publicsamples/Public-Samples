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

//the notes
var noteNames = ["B3", "A#3", "A3", "G#3"];
var noteNames2 = ["G3", "F#3", "F3", "E3"];	
var noteNames3 = ["D#3", "D3", "C#3", "C3"];
var noteNames4 = ["B2", "A#2", "A2", "G#2"];
var noteNames5 = ["G2", "F#2", "F2", "E2"];			
var noteNames6 = ["D#2", "D2", "C#2", "C2"];			


var loop = new Tone.Sequence(function(time, col){
var column = document.querySelector("#seq1").currentColumn;
column.forEach(function(val, i){
if (val){

//slightly randomized velocities
keys.triggerAttackRelease(noteNames[i], time, "8n");
}
});
//set the columne on the correct draw frame
Tone.Draw.schedule(function(){
document.querySelector("#seq1").setAttribute("highlight", col);
}, time);
}, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], "16n").start(0);

var loop2 = new Tone.Sequence(function(time, col){
var column2 = document.querySelector("#seq2").currentColumn;
column2.forEach(function(val, i){
if (val){

//slightly randomized velocities
keys.triggerAttackRelease(noteNames2[i], time, "8n");
}
});

//set the columne on the correct draw frame
Tone.Draw.schedule(function(){
document.querySelector("#seq2").setAttribute("highlight", col);
}, time);
}, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], "16n").start(0);




var loop3 = new Tone.Sequence(function(time, col){
var column3 = document.querySelector("#seq3").currentColumn;
column3.forEach(function(val, i){
if (val){
keys.triggerAttackRelease(noteNames3[i], time, "8n");
}
});
//set the columne on the correct draw frame
Tone.Draw.schedule(function(){
document.querySelector("#seq3").setAttribute("highlight", col);
}, time);
}, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], "16n").start(0);



var loop4 = new Tone.Sequence(function(time, col){
var column4 = document.querySelector("#seq4").currentColumn;
column4.forEach(function(val, i){
if (val){

//slightly randomized velocities
keys.triggerAttackRelease(noteNames4[i], time, "8n");
}
});
//set the columne on the correct draw frame
Tone.Draw.schedule(function(){
document.querySelector("#seq4").setAttribute("highlight", col);
}, time);
}, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], "16n").start(0);



var loop5 = new Tone.Sequence(function(time, col){
var column5 = document.querySelector("#seq5").currentColumn;
column5.forEach(function(val, i){
if (val){

//slightly randomized velocities
keys.triggerAttackRelease(noteNames5[i], time, "8n");
}
});

//set the columne on the correct draw frame
Tone.Draw.schedule(function(){
document.querySelector("#seq5").setAttribute("highlight", col);
}, time);
}, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], "16n").start(0);




var loop6 = new Tone.Sequence(function(time, col){
var column6 = document.querySelector("#seq6").currentColumn;
column6.forEach(function(val, i){
if (val){
keys.triggerAttackRelease(noteNames6[i], time, "8n");
}
});
//set the columne on the correct draw frame
Tone.Draw.schedule(function(){
document.querySelector("#seq6").setAttribute("highlight", col);
}, time);
}, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], "16n").start(0);


function stop() {
Tone.Transport.stop();
power.turnOff();

}


function clear() {

keys.disconnect();
keys.dispose();
}


//aENV Controls
var attack = new Nexus.Slider('#attack', {
'mode': 'absolute', // "absolute" or "absolute"
'size': [20, 50],
'min': 0,
'max': 100,
'step': 0,
'value': 0
})

attack.on('change', function(v) {
keys.attack = v;
});

var release = new Nexus.Slider('#release', {
'mode': 'absolute', // "absolute" or "absolute"
'size': [20, 50],
'min': 0,
'max': 40,
'step': 0,
'value': 12
})

release.on('change', function(v) {
keys.release = v;
});

var curve = new Nexus.Toggle('#curve', {
'size': [30,30],
'state': true
})

curve.on('change', function(v) {
keys.curve = v;
});


//chorus Controls


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

//Transport/seq Controls


var power = new Nexus.TextButton("power", { mode: "toggle", size: [40, 40], text: "▶", alternate: "▪"});		
power.on('change', v => v ? Tone.Transport.start() : Tone.Transport.stop())

var tempo = new Nexus.Dial('#tempo', {size: [35, 35], min: 50,max: 200,  value: 120 });
tempo.on('change', v => Tone.Transport.bpm.value = v)

var vol = new Nexus.Dial('#vol', {size: [35, 35], min: -12,max: 12,  value: 0 });
vol.on('change', v => eq.gain.value = v)


Tone.Transport.on("stop", () => {
setTimeout(() => {
document.querySelector("#seq1").setAttribute("highlight", "-1");
document.querySelector("#seq2").setAttribute("highlight", "-1");
document.querySelector("#seq3").setAttribute("highlight", "-1");
}, 100);

});

//keys



var keyboard = new AudioKeys();
keyboard.down(function(note) {
keys.triggerAttack(Tone.Frequency(note.note, "midi").toNote());
});
keyboard.up(function(note) {
keys.triggerRelease(Tone.Frequency(note.note, "midi").toNote());
});
