const fs = require('fs');
const content = fs.readFileSync('app/Views/os/form.php', 'utf8');

const regex = /<script>([\s\S]*?)<\/script>/g;
let match;
while ((match = regex.exec(content)) !== null) {
    let script = match[1];
    const offset = content.substring(0, match.index + 8).split('\n').length - 1;
    
    // Replace PHP echos with valid JS placeholders
    script = script.replace(/<\?=([\s\S]*?)\?>/g, (m) => ' (null) '.padEnd(m.length, ' '));
    // Replace PHP logic with spaces
    script = script.replace(/<\?php([\s\S]*?)\?>/g, (m) => ' '.repeat(m.length));
    
    const fullScript = '\n'.repeat(offset) + script;
    
    const vm = require('vm');
    try {
        new vm.Script(fullScript, { filename: 'os_form.php' });
        console.log("Script block OK");
    } catch (e) {
        console.log("Syntax Error in script block:");
        console.log(e.toString());
        if (e.stack) {
            const lines = fullScript.split('\n');
            const matchLine = e.stack.match(/os_form\.php:(\d+):(\d+)/);
            if (matchLine) {
                const lineNum = parseInt(matchLine[1]);
                console.log(`Line ${lineNum}: ${lines[lineNum-1]}`);
                // Show more context
                for(let i = lineNum - 3; i <= lineNum + 3; i++) {
                    if (lines[i-1] !== undefined) console.log(`${i}: ${lines[i-1]}`);
                }
            }
        }
    }
}
