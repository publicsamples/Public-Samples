
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


//aENV Controls
var attack = new Nexus.Slider('#attack', {
'mode': 'relative', // "absolute" or "relative"
'size': [25, 75],
'min': 0,
'max': 100,
'step': 0,
'value': 0
})

attack.on('change', function(v) {
keys.attack = v;
});

var release = new Nexus.Slider('#release', {
'mode': 'relative', // "absolute" or "relative"
'size': [25, 75],
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

//filter Controls


var frequency = new Nexus.Slider('#frequency', {
'mode': 'relative', // "absolute" or "relative"
'size': [25, 75],
'min': 0,
'max': 10000,
'step': 0,
'value': 10000
})

frequency.on('change', function(v) {
filter.frequency.value = v;
});

var Q = new Nexus.Slider('#Q', {
'mode': 'relative', // "absolute" or "relative"
'size': [25, 75],
'min': 0,
'max': 12,
'step': 0,
	'value': 0
})

Q.on('change', function(v) {
filter.Q.value = v;
});

var gain = new Nexus.Slider('#gain', {
'mode': 'relative', // "absolute" or "relative"
'size': [25, 75],
'min': 0,
'max': 100,
'step': 0,
'value': 0
})

gain.on('change', function(v) {
filter.gain.value = v;
});

var lfofreq = new Nexus.Slider('#lfofreq', {
'mode': 'relative', // "absolute" or "relative"
'size': [25, 75],
'min': 0,
'max': 150,
'step': 0,
'value': 0
})

lfofreq.on('change', function(v) {
lfo.frequency.value = v;
});

var amplitude = new Nexus.Slider('#amplitude', {
'mode': 'relative', // "absolute" or "relative"
'size': [25, 75],
'min': 0,
'max': 24,
'step': 0,
'value': 0
})

amplitude.on('change', function(v) {
lfo.amplitude.value = v;
});


//Transport/seq Controls

var power = new Nexus.TextButton("power", { mode: "toggle", size: [40, 40], text: "▶", alternate: "▪"});		
power.on('change', v => v ? Tone.Transport.start() : Tone.Transport.stop())

var tempo = new Nexus.Dial('#tempo', {size: [25, 25], min: 50,max: 200,  value: 120 });
tempo.on('change', v => Tone.Transport.bpm.value = v)
Tone.Transport.on("stop", () => {
setTimeout(() => {
document.querySelector("#seq1").setAttribute("highlight", "-1");
document.querySelector("#seq2").setAttribute("highlight", "-1");
document.querySelector("#seq3").setAttribute("highlight", "-1");
}, 100);

});

//keys

var keyboardUI = new Nexus.Piano('#Keyboard', {
'size': [250, 125],
'mode': 'button', // 'button', 'toggle', or 'impulse'
'lowNote': 36,
'highNote': 72
})

keyboardUI.on('change', function(note) {
console.log(Tone.Frequency(note.note, "midi").toNote());
if (note.state === true) {
keys.triggerAttack(Tone.Frequency(note.note, "midi").toNote());
} else if (note.state === false) {
keys.triggerRelease(Tone.Frequency(note.note, "midi").toNote());
}
})

var keyboard = new AudioKeys();
keyboard.down(function(note) {
keys.triggerAttack(Tone.Frequency(note.note, "midi").toNote());
});
keyboard.up(function(note) {
keys.triggerRelease(Tone.Frequency(note.note, "midi").toNote());
});

