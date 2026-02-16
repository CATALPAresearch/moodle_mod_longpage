/**
 * Helper script to extract all i18n keys from Vue components
 * Run: node extract-i18n-keys.js
 */

const fs = require("fs");
const path = require("path");

const keys = new Set();

function extractKeysFromFile(filePath) {
  const content = fs.readFileSync(filePath, "utf-8");

  // Match $t('key') and $t("key")
  const regex1 = /\$t\(['"]([^'"]+)['"]/g;
  let match;
  while ((match = regex1.exec(content)) !== null) {
    keys.add(match[1]);
  }

  // Match $t(`key`) with template literals
  const regex2 = /\$t\(`([^`]+)`/g;
  while ((match = regex2.exec(content)) !== null) {
    // Extract static parts (ignore dynamic ${...})
    const key = match[1].replace(/\$\{[^}]+\}/g, "*");
    keys.add(key);
  }
}

function walkDir(dir) {
  const files = fs.readdirSync(dir);
  files.forEach((file) => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    if (stat.isDirectory()) {
      walkDir(filePath);
    } else if (file.endsWith(".vue") || file.endsWith(".js")) {
      extractKeysFromFile(filePath);
    }
  });
}

// Walk the src directory
walkDir(path.join(__dirname, "src"));

// Sort and output keys
const sortedKeys = Array.from(keys).sort();

console.log("Total unique keys:", sortedKeys.length);
console.log("\nKeys to add to lang/en/longpage.php:");
console.log("=====================================\n");

sortedKeys.forEach((key) => {
  // Show which keys need translation values
  console.log(`$string['${key}'] = '';`);
});

// Group by namespace
console.log("\n\nGrouped by namespace:");
console.log("=====================\n");

const grouped = {};
sortedKeys.forEach((key) => {
  const namespace = key.split(".")[0];
  if (!grouped[namespace]) {
    grouped[namespace] = [];
  }
  grouped[namespace].push(key);
});

Object.keys(grouped)
  .sort()
  .forEach((namespace) => {
    console.log(`\n// ${namespace.toUpperCase()}`);
    grouped[namespace].forEach((key) => {
      console.log(`$string['${key}'] = '';`);
    });
  });
