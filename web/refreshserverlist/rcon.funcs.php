<?php

function EncodeHeader($isFromServer, $isResponse, $sequence)
{
$header = $sequence & 0x3fffffff;

if ($isFromServer) {
$header += 0x80000000;
}

if ($isResponse) {
$header += 0x40000000;
}

return pack('I', $header);
}

function DecodeHeader($data)
{
$header = unpack('I', mb_substr($data, 0, 4));

return array($header & 0x80000000, $header & 0x40000000, $header & 0x3fffffff);
}

function EncodeInt32($size)
{
return pack('I', $size);
}

function DecodeInt32($data)
{
$decode = unpack('I', mb_substr($data, 0, 4));

return $decode[1];
}

function EncodeWords($words)
{
$size = 0;
$encodedWords = '';

foreach ($words as $word) {
$encodedWords .= EncodeInt32(strlen($word));
$encodedWords .= $word;
$encodedWords .= "\x00";
$size += strlen($word) + 5;
}

return array($size, $encodedWords);
}

function DecodeWords($size, $data)
{
$numWords = DecodeInt32($data);
$words = array();
$offset = 0;
while ($offset < $size) {
$wordLen = DecodeInt32(mb_substr($data, $offset, 4));
$word = mb_substr($data, $offset + 4, $wordLen);
array_push($words, $word);
$offset += $wordLen + 5;
}

return $words;
}

function EncodePacket($isFromServer, $isResponse, $sequence, $words)
{
$encodedHeader = EncodeHeader($isFromServer, $isResponse, $sequence);
$encodedNumWords = EncodeInt32(count($words));
list($wordsSize, $encodedWords) = EncodeWords($words);
$encodedSize = EncodeInt32($wordsSize + 12);

return $encodedHeader . $encodedSize . $encodedNumWords . $encodedWords;
}

function DecodePacket($data)
{
list($isFromServer, $isResponse, $sequence) = DecodeHeader($data);
$wordsSize = DecodeInt32(mb_substr($data, 4, 4)) - 12;
$words = DecodeWords($wordsSize, mb_substr($data, 12));

return array($isFromServer, $isResponse, $sequence, $words);
}

function EncodeClientRequest($string)
{
global $clientSequenceNr;

// string splitting
if ((strpos($string, '"') !== FALSE) or (strpos($string, '\'') !== FALSE)) {
$words = preg_split('/["\']/', $string);

for ($i=0; $i < count($words); $i++) {
$words[$i] = trim($words[$i]);
}
} else {
$words = preg_split('/\s+/', $string);
}

$packet = EncodePacket(False, False, $clientSequenceNr, $words);
$clientSequenceNr = ($clientSequenceNr + 1) & 0x3fffffff;

return $packet;
}

function EncodeClientResponse($sequence, $words)
{
return EncodePacket(True, True, $sequence, $words);
}

function containsCompletePacket($data)
{
if (mb_strlen($data) < 8) {
return False;
}

if (mb_strlen($data) < DecodeInt32(mb_substr($data, 4, 4))) {
return False;
}

return True;
}

function receivePacket(&$socket)
{
$receiveBuffer = '';	
while (!containsCompletePacket($receiveBuffer)) {
global $receiveBuffer;

if (($receiveBuffer .= socket_read($socket, 4096)) === FALSE) {
echo "Socket error: " . socket_strerror(socket_last_error($socket)) . "\n";
socket_close($socket);
exit;
}
}

$packetSize = DecodeInt32(mb_substr($receiveBuffer, 4, 4));
$packet = mb_substr($receiveBuffer, 0, $packetSize);
$receiveBuffer = mb_substr($receiveBuffer, $packetSize);

return array($packet, $receiveBuffer);
}

function printPacket($packet)
{
if ($packet[0]) {
echo "IsFromServer, $packet[0] ";
} else {
echo "IsFromClient, ";
}

if ($packet[1]) {
echo "Response, $packet[1] ";
} else {
echo "Request, ";
}

echo "Sequence: $packet[2]";

if ($packet[3]) {
echo " Words:";
foreach ($packet[3] as $word) {
echo " \"$word\"";
}
}
}

// Hashed password helper functions
function hexstr($hexstr)
{
$hexstr = str_replace(' ', '', $hexstr);
$hexstr = str_replace('\x', '', $hexstr);
$retstr = pack('H*', $hexstr);
return $retstr;
}

function strhex($string)
{
$hexstr = unpack('H*', $string);
return array_shift($hexstr);
}

function generatePasswordHash($salt, $password)
{
$salt = hexstr($salt);
$hashedPassword = md5($salt . $password, TRUE);

return strtoupper(strhex($hashedPassword));
}

/**
* Main RCON Command. Call this to send a command to the server.
* Arguments: socket, command to send
*
* @return array of words; server's response
**/
function rconCommand(&$socket, $string)
{
if ((socket_write($socket, EncodeClientRequest($string))) === FALSE) {
echo "Socket error: " . socket_strerror(socket_last_error($socket)) . "\n";
socket_close($socket);
exit;
}

list($packet, $receiveBuffer) = receivePacket($socket);
list($isFromServer, $isResponse, $sequence, $words) = DecodePacket($packet);

return $words;
}

// same as rconCommand, but prints packet for debugging
function rconCommandPrintPacket(&$socket, $string)
{
if ((socket_write($socket, EncodeClientRequest($string))) === FALSE) {
echo "Socket error: " . socket_strerror(socket_last_error($socket)) . "\n";
socket_close($socket);
exit;
}

list($packet, $receiveBuffer) = receivePacket($socket);
printPacket(DecodePacket($packet));
list($isFromServer, $isResponse, $sequence, $words) = DecodePacket($packet);

return $words;
}

?>