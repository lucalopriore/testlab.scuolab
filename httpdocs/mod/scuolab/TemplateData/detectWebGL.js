
function detectWebGL($id) {
    var canvas = document.getElementById($id);

    try { gl = canvas.getContext("webgl"); }
    catch (x) { gl = null; }

    if (gl == null) {
        try { gl = canvas.getContext("experimental-webgl"); }
        catch (x) { gl = null; }
    }

    if (gl) {
        return true;
    }
    return false;
}