<?php
function actionToString($eventId, $data)
{
    switch ($eventId) {
        case -1:
            return "Chiusa l'esperienza $data";
        case 0:
            return "Avvio dell'esperienza $data";
        case 1:
            return "Passaggio a camera: $data";
        case 2:
            return "Aperto pannello del grafico";
        case 3:
            return "Chiuso pannello del grafico";
        case 4:
            return "Aperto notebook";
        case 5:
            return "Chiuso notebook";
        case 6:
            return "Esperienza sbloccata senza inserimento dei dati";
        case 7:
            return "Notebook auto-riempito con pulsante compila";
        case 8:
            return "Correzione notebook: $data";
        case 9:
            return "Aperto pannello Pick & Play";
        case 10:
            return "Chiuso pannello Pick & Play";
        case 11:
            return "Istanziato oggetto $data dal P&P";
        case 12:
            return "Distrutto oggetto $data";
        case 13:
            return "Attivata la visualizzazione delle lenti";
        case 14:
            return "Disattivata la visualizzazione delle lenti";
        case 15:
            return "Check: $data";
    }
}
