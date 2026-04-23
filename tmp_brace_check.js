const fs = require('fs');
const content = fs.readFileSync('app/Views/os/form.php', 'utf8');

const regex = /<script>([\s\S]*?)<\/script>/g;
let match;
while ((match = regex.exec(content)) !== null) {
    const script = match[1];
    const preLines = content.substring(0, match.index + 8).split('\n').length - 1;
    
    let stack = [];
    const lines = script.split('\n');
    
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        const lineNum = i + 1 + preLines;
        
        // Very simplistic parser that ignores strings/comments
        // (This might be fooled by complex regex or escaped chars, but let's try)
        let inString = null;
        for (let j = 0; j < line.length; j++) {
            const char = line[j];
            if (inString) {
                if (char === inString && line[j-1] !== '\\') inString = null;
                continue;
            }
            if (char === '"' || char === "'" || char === '`') {
                inString = char;
                continue;
            }
            if (char === '/' && line[j+1] === '/') break; // comment
            
            if (char === '{') stack.push({ line: lineNum, col: j+1 });
            if (char === '}') {
                if (stack.length === 0) {
                    console.log(`Unexpected '}' at line ${lineNum}, col ${j+1}`);
                    console.log(line);
                } else {
                    stack.pop();
                }
            }
        }
    }
    if (stack.length > 0) {
        console.log(`Unclosed braces: ${stack.length}`);
        stack.forEach(b => console.log(`  at line ${b.line}, col ${b.col}`));
    } else {
        console.log("Braces balanced in this block.");
    }
}
