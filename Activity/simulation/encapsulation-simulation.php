<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkillSync - Encapsulation Simulation</title>
<link rel="shortcut icon" sizes="32x32" href="LOGO.png" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f4f6f8;
        color: #2c3e50;
        margin: 0;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .content {
        max-width: 1200px;
        width: 95%;
        height: 90vh;
        display: flex;
        flex-direction: column;
        background-color: #ffffff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    h1 { color: #27ae60; text-align: center; margin: 10px 0 20px; }
    h2 { color: #27ae60; margin-top: 0; }

    /* Progress Tracker */
    .progress-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    .step { flex: 1; text-align: center; position: relative; }
    .step:before {
        content: "";
        position: absolute;
        top: 15px;
        left: -50%;
        width: 100%;
        height: 4px;
        background: #ddd;
        z-index: -1;
    }
    .step:first-child:before { display: none; }
    .circle {
        width: 30px; height: 30px;
        margin: 0 auto 8px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: bold;
        color: white;
    }
    .active .circle { background: #27ae60; }
    .inactive .circle { background: #bbb; }
    .step span { font-size: 14px; display: block; color: #333; }
    .active span { font-weight: bold; color: #27ae60; }

    .container {
        flex: 1;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        overflow: hidden;
    }
    .panel {
        flex: 1;
        min-width: 300px;
        background: #fff;
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow-y: auto;
    }
    textarea {
        width: 100%;
        height: 150px;
        margin-top: 10px;
        font-family: monospace;
        border-radius: 6px;
        border: 1px solid #ccc;
        padding: 8px;
        resize: none;
        overflow-x: hidden;
        white-space: pre-wrap;
    }
    button, select, input {
        margin: 5px 0;
        padding: 6px 10px;
        border-radius: 8px;
        border: 1px solid #ccc;
    }
    button {
        cursor: pointer;
        background: #27ae60;
        color: #fff;
        border: none;
        transition: background 0.3s, transform 0.2s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    button:hover { background: #1e8449; transform: translateY(-2px); }

    .return, .start {
        padding: 10px 20px;
        border-radius: 50px;
        font-size: 15px;
        font-weight: 600;
    }
    .return { background: #e74c3c; }
    .return:hover { background: #c0392b; }
    .start { background: #2980b9; }
    .start:hover { background: #1f6391; }

    .button-container {
        margin-top: 10px;
        display: flex;
        justify-content: center;
        gap: 15px;
    }

    .object-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 10px;
        margin: 5px 0;
        background: #fafafa;
    }
    .object-card h3 { margin: 0 0 5px 0; color: #27ae60; }
    .method-output { color: #2980b9; margin-top: 5px; }
</style>
</head>
<body>

<div class="content">
    <!-- Progress Tracker -->
    <div class="progress-steps">
        <div class="step inactive">
            <div class="circle">1</div>
            <span>Intro</span>
        </div>
        <div class="step inactive">
            <div class="circle">2</div>
            <span>Details</span>
        </div>
        <div class="step active">
            <div class="circle">3</div>
            <span>Encapsulation Simulation</span>
        </div>
    </div>

    <h1>Encapsulation Playground</h1>

    <div class="container">
        <!-- Controls -->
        <div class="panel">
            <h2>Controls</h2>
            <label>Class Name:</label>
            <input type="text" id="className" placeholder="e.g. Person"><br>
            <button id="createClassBtn">Create Class</button>
            <button id="resetBtn">Reset All</button>
            <hr>

            <label>Object Name:</label>
            <input type="text" id="objectName" placeholder="e.g. person1"><br>
            <button id="createObjectBtn">Create Object</button>
            <hr>

            <label>Choose Object:</label>
            <select id="objectSelect"></select><br>

            <label>Setter:</label>
            <select id="setterSelect"></select>
            <input type="text" id="setterValue" placeholder="Value"><br>
            <button id="callSetterBtn">Call Setter</button>
            <hr>

            <label>Getter:</label>
            <select id="getterSelect"></select><br>
            <button id="callGetterBtn">Call Getter</button>
        </div>

        <!-- Workspace -->
        <div class="panel">
            <h2>Workspace</h2>
            <div id="workspace"></div>
        </div>

        <!-- Code -->
        <div class="panel">
            <h2>Code Output</h2>
            <textarea id="codeInput" readonly></textarea>
            <button id="runCodeBtn">Run Code</button>
        </div>
    </div>

    <div class="button-container">
        <button class="return" onclick="window.location.href='oop1_encapsulation.php'">⬅ Back</button>
        <button class="start" onclick="window.location.href='oop1_encapsulation_list.php'">▶ Start Activity</button>
    </div>
</div>

<script>
let currentClass = null;
let objects = {};
let setters = ["setName", "setAge"];
let getters = ["getName", "getAge"];

const workspace = document.getElementById('workspace');
const codeInput = document.getElementById('codeInput');
const objectSelect = document.getElementById('objectSelect');
const setterSelect = document.getElementById('setterSelect');
const getterSelect = document.getElementById('getterSelect');

function updateDropdowns() {
    objectSelect.innerHTML = "";
    Object.keys(objects).forEach(obj => {
        let opt = document.createElement("option");
        opt.value = obj;
        opt.textContent = obj;
        objectSelect.appendChild(opt);
    });

    setterSelect.innerHTML = "";
    setters.forEach(s => {
        let opt = document.createElement("option");
        opt.value = s;
        opt.textContent = s;
        setterSelect.appendChild(opt);
    });

    getterSelect.innerHTML = "";
    getters.forEach(g => {
        let opt = document.createElement("option");
        opt.value = g;
        opt.textContent = g;
        getterSelect.appendChild(opt);
    });
}

function render() {
    workspace.innerHTML = "";
    for (let name in objects) {
        let card = document.createElement("div");
        card.className = "object-card";
        card.innerHTML = `<h3>${name}</h3>`;
        card.innerHTML += `<p>Name: ${objects[name]._name || "Not set"}</p>`;
        card.innerHTML += `<p>Age: ${objects[name]._age || "Not set"}</p>`;
        card.innerHTML += `<div class="method-output">${objects[name].methodOutput || ''}</div>`;
        workspace.appendChild(card);
    }
}

function updateCode(snippet) {
    codeInput.value = snippet;
    codeInput.scrollTop = codeInput.scrollHeight;
}

document.getElementById("createClassBtn").onclick = () => {
    let className = document.getElementById("className").value.trim();
    if (!className) return alert("Enter a class name");
    currentClass = className;
    updateCode(`class ${className} {
  constructor() {
    this._name = "";
    this._age = 0;
  }
  setName(name) { this._name = name; }
  getName() { return this._name; }
  setAge(age) { this._age = age; }
  getAge() { return this._age; }
}`);
    render();
};

document.getElementById("resetBtn").onclick = () => {
    currentClass = null;
    objects = {};
    codeInput.value = "";
    workspace.innerHTML = "";
    updateDropdowns();
};

document.getElementById("createObjectBtn").onclick = () => {
    if (!currentClass) return alert("Create a class first");
    let objName = document.getElementById("objectName").value.trim();
    if (!objName) return alert("Enter an object name");
    objects[objName] = { class: currentClass, _name: "", _age: "" };
    updateCode(`let ${objName} = new ${currentClass}();`);
    render();
    updateDropdowns();
};

document.getElementById("callSetterBtn").onclick = () => {
    let objName = objectSelect.value;
    let setter = setterSelect.value;
    let value = document.getElementById("setterValue").value;
    if (!objName) return alert("No object selected");
    if (!setter) return alert("Choose a setter");
    objects[objName][setter === "setName" ? "_name" : "_age"] = value;
    let snippet = isNaN(value) ? `${objName}.${setter}("${value}");` : `${objName}.${setter}(${value});`;
    updateCode(snippet);
    render();
};

document.getElementById("callGetterBtn").onclick = () => {
    let objName = objectSelect.value;
    let getter = getterSelect.value;
    if (!objName) return alert("No object selected");
    let result = objects[objName][getter === "getName" ? "_name" : "_age"];
    let snippet = `${objName}.${getter}(); // returns ${result}`;
    updateCode(snippet);
    objects[objName].methodOutput = `${getter}() → ${result}`;
    render();
};

updateDropdowns();
</script>
</body>
</html>
