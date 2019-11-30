
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

var keys = new Tone.Sampler().connect(filter);
					
				

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


//keys

Tone.Transport.on("stop", () => {
setTimeout(() => {
document.querySelector("#seq1").setAttribute("highlight", "-1");
document.querySelector("#seq2").setAttribute("highlight", "-1");
document.querySelector("#seq3").setAttribute("highlight", "-1");
document.querySelector("#seq4").setAttribute("highlight", "-1");
document.querySelector("#seq5").setAttribute("highlight", "-1");
document.querySelector("#seq6").setAttribute("highlight", "-1");
}, 100);

});

