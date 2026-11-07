<?php
const MC_HOST = 'host.docker.internal';
const MC_PORT = 25565;

// ============================================================
//  Server status, frågar Minecraft-servern precis som serverlistan i spelet gör
// ============================================================
function get_server_status() {
    // 1. Ring servern
    $socket = @fsockopen(MC_HOST, MC_PORT, $errno, $errstr, 3);
    if ($socket === false) {
        return ['online' => false, 'error' => $errstr];
    }
    stream_set_timeout($socket, 3);   // vänta max 3 sekunder på svar

    // 2. Handslaget: "Hej, jag vill veta din status"
    $data = write_varint(0)                              // spelversion (spelar ingen roll för status)
          . write_varint(strlen(MC_HOST)) . MC_HOST      // serverns namn (längden först)
          . pack('n', MC_PORT)                           // porten, 2 bytes
          . write_varint(1);                             // 1 = "jag vill ha status"
    fwrite($socket, make_packet(0, $data));

    // 3. Statusförfrågan: ett tomt paket
    fwrite($socket, make_packet(0, ''));

    // 4. Läs svaret: [längd][paket-id][textens längd][texten (JSON)]
    read_varint($socket);                // längd på hela paketet (behövs inte)
    read_varint($socket);                // paket-id (alltid 0)
    $json_length = read_varint($socket); // hur lång JSON-texten är
    if ($json_length === null) {
        fclose($socket);
        return ['online' => false, 'error' => 'No response'];
    }

    // Läs JSON-texten. fread kan ge den i bitar, så vi läser tills vi har allt.
    $json = '';
    while (strlen($json) < $json_length) {
        $chunk = fread($socket, $json_length - strlen($json));
        if ($chunk === '' || $chunk === false) {
            break;
        }
        $json .= $chunk;
    }
    fclose($socket);   // lägg på luren

    $status = json_decode($json, true);

    // Steg 6, din tur: plocka ut det du vill visa ur $status
    return [
        'online' => true,
        'raw' => $status,
    ];
}

// ============================================================
//  Hjälpfunktioner för Minecrafts protokoll
// ============================================================

// Skriver ett tal som VarInt (Minecrafts sätt att spara tal)
function write_varint($value) {
    $bytes = '';
    do {
        $byte = $value & 0x7F;       // ta de 7 lägsta bitarna
        $value = $value >> 7;        // flytta bort dem
        if ($value !== 0) {
            $byte = $byte | 0x80;    // fler bytes kommer → sätt "fortsätt"-biten
        }
        $bytes .= chr($byte);        // gör om talet till en byte och lägg till
    } while ($value !== 0);
    return $bytes;
}

// Läser en VarInt från servern
function read_varint($socket) {
    $value = 0;
    $shift = 0;
    do {
        $char = fread($socket, 1);       // läs EN byte
        if ($char === '' || $char === false) {
            return null;                 // servern slutade svara
        }
        $byte = ord($char);              // gör om byten till ett tal (0–255)
        $value = $value | (($byte & 0x7F) << $shift);
        $shift += 7;
    } while (($byte & 0x80) && $shift < 35);  // fortsätt så länge "fortsätt"-biten är satt
    return $value;
}

// Bygger ett Minecraft-paket: [längd][paket-id][data]
function make_packet($id, $data) {
    $packet = write_varint($id) . $data;
    return write_varint(strlen($packet)) . $packet;
}
