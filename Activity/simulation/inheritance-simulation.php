<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkillSync - OOP Inheritance Simulation</title>
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
            <span>Inheritance Simulation</span>
        </div>
    </div>

    <h1>OOP Inheritance Playground</h1>

    <div class="container">
        <div class="panel">
            <h2>Controls</h2>
            <label>Subclass Name:</label>
            <input type="text" id="className" placeholder="e.g. Student"><br>
            <button id="createSubclassBtn">Create Subclass (extends Person)</button>
            <button id="resetBtn">Reset All</button>
            <hr>

            <label>Object Name:</label>
            <input type="text" id="objectName" placeholder="e.g. student1"><br>
            <button id="createObjectBtn">Create Object</button>
            <hr>

            <label>Choose Object:</label>
            <select id="objectSelect"></select><br>

            <div class="field-row">
            <label for="fieldSelect">Field Name:</label>
            <select id="fieldSelect"></select>
            <input type="text" id="fieldValue" placeholder="Value">
            </div>
            <button id="setFieldBtn">Set Field</button>


            <label>Choose Method:</label>
            <select id="methodSelect"></select><br>
            <button id="callMethodBtn">Call Method</button>
        </div>

        <div class="panel">
            <h2>Workspace</h2>
            <div id="workspace"></div>
        </div>

        <div class="panel">
            <h2>Code Output</h2>
            <textarea id="codeInput" readonly></textarea>
        </div>
    </div>

    <div class="button-container">
        <button class="return" onclick="window.location.href='oop1_inheritance.php'">⬅ Back</button>
        <button class="start" onclick="window.location.href='oop1_inheritance_list.php'">▶ Start Activity</button>
    </div>
</div>

<script>
let baseClass = "Person";
let currentSubclass = null;
let objects = {};
let methods = ["introduce", "greet"];
let subclassMethods = ["study"];
let fields = ["name", "age", "course"];

const workspace = document.getElementById('workspace');
const codeInput = document.getElementById('codeInput');
const objectSelect = document.getElementById('objectSelect');
const fieldSelect = document.getElementById('fieldSelect');
const methodSelect = document.getElementById('methodSelect');

function updateDropdowns() {
    objectSelect.innerHTML = "";
    Object.keys(objects).forEach(obj => {
        let opt = document.createElement("option");
        opt.value = obj;
        opt.textContent = obj;
        objectSelect.appendChild(opt);
    });

    fieldSelect.innerHTML = "";
    fields.forEach(f => {
        let opt = document.createElement("option");
        opt.value = f;
        opt.textContent = f;
        fieldSelect.appendChild(opt);
    });

    methodSelect.innerHTML = "";
    [...methods, ...subclassMethods].forEach(m => {
        let opt = document.createElement("option");
        opt.value = m;
        opt.textContent = m;
        methodSelect.appendChild(opt);
    });
}

function render() {
    workspace.innerHTML = "";
    for (let name in objects) {
        let card = document.createElement("div");
        card.className = "object-card";
        card.innerHTML = `<h3>${name}</h3>`;
        for (let key in objects[name]) {
            if (key !== 'class' && key !== 'methodOutput') {
                card.innerHTML += `<p>${key}: ${objects[name][key]}</p>`;
            }
        }
        card.innerHTML += `<div class="method-output">${objects[name].methodOutput || ''}</div>`;
        workspace.appendChild(card);
    }
}

function updateCode(snippet) {
    codeInput.value += snippet + "\n";
    codeInput.scrollTop = codeInput.scrollHeight;
}

document.getElementById("createSubclassBtn").onclick = () => {
    let className = document.getElementById("className").value.trim();
    if (!className) return alert("Enter a subclass name");
    currentSubclass = className;
    updateCode(`class ${baseClass} {
  constructor(name, age) {
    this.name = name;
    this.age = age;
  }
  introduce() { return \`Hi, I'm \${this.name} and I'm \${this.age} years old.\`; }
  greet() { return \`Hello from \${this.name}!\`; }
}

class ${className} extends ${baseClass} {
  constructor(name, age, course) {
    super(name, age);
    this.course = course;
  }
  study() { return \`\${this.name} is studying \${this.course}.\`; }
}`);
    render();
};

document.getElementById("resetBtn").onclick = () => {
    currentSubclass = null;
    objects = {};
    codeInput.value = "";
    workspace.innerHTML = "";
    updateDropdowns();
};

document.getElementById("createObjectBtn").onclick = () => {
    if (!currentSubclass) return alert("Create a subclass first");
    let objName = document.getElementById("objectName").value.trim();
    if (!objName) return alert("Enter an object name");
    objects[objName] = { class: currentSubclass };
    updateCode(`let ${objName} = new ${currentSubclass}("Unknown", 0, "Unknown Course");`);
    render();
    updateDropdowns();
};

document.getElementById("setFieldBtn").onclick = () => {
    let objName = objectSelect.value;
    let field = fieldSelect.value;
    let value = document.getElementById("fieldValue").value;
    if (!objName) return alert("No object selected");
    if (!field) return alert("Choose a field");
    objects[objName][field] = value;
    let snippet = isNaN(value) ? `${objName}.${field} = "${value}";` : `${objName}.${field} = ${value};`;
    updateCode(snippet);
    render();
};

document.getElementById("callMethodBtn").onclick = () => {
    let objName = objectSelect.value;
    let method = methodSelect.value;
    if (!objName) return alert("No object selected");

    let snippet = `${objName}.${method}();`;
    updateCode(snippet);

    let res = "";
    if (method === "introduce") res = `Hi, I'm ${objects[objName].name || "Unknown"} and I'm ${objects[objName].age || "0"} years old.`; 
    else if (method === "greet") res = `Hello from ${objects[objName].name || "Unknown"}!`;
    else if (method === "study") res = `${objects[objName].name || "Unknown"} is studying ${objects[objName].course || "Unknown Course"}.`;

    objects[objName].methodOutput = res;
    render();
};

// ✅ Show example inheritance code on load
updateCode(`class Person {
  constructor(name, age) {
    this.name = name;
    this.age = age;
  }
  introduce() { return \`Hi, I'm \${this.name} and I'm \${this.age} years old.\`; }
  greet() { return \`Hello from \${this.name}!\`; }
}

class Student extends Person {
  constructor(name, age, course) {
    super(name, age);
    this.course = course;
  }
  study() { return \`\${this.name} is studying \${this.course}.\`; }
}

let student1 = new Student("Alice", 20, "Computer Science");
student1.introduce();`);

render();
updateDropdowns();
</script>

</body>
</html>
