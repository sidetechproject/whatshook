const qrcode = require('qrcode-terminal');

const { Client, LocalAuth } = require('whatsapp-web.js');

const client = new Client({
    authStrategy: new LocalAuth()
});

const bodyParser = require('body-parser');
const express = require("express");

const app = express();
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());

client.on('qr', (qr) => {
	qrcode.generate(qr, {small: true});
    console.log('QR RECEIVED', qr);
});

client.on('authenticated', () => {
    console.log('AUTHENTICATED');
});

client.on('auth_failure', msg => {
    // Fired if session restore wasx unsuccessful
    console.error('AUTHENTICATION FAILURE', msg);
});

client.on('ready', () => {
    console.log('Client is ready!');
});

client.initialize();

// API WHATSHOOK
app.post("/webhook", (req, res, next) => {
	console.log('Got body:', req.body);

	let url = req.body.url;
	let name = req.body.name;
	let number = req.body.number;
	//let message = JSON.stringify(req.body.message);
	let message = '*🔔 WhatsHook - ' + name + '*' + `

` + req.body.payload + `

` + "_🤖 automated message, don't reply_";

	number = number.includes('@c.us') ? number : `${number}@c.us`;
	client.sendMessage(number, message);

 	res.sendStatus(200);
});

app.listen(3000, () => console.log(`Started server at http://localhost:3000!`));


