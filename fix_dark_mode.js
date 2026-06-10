const fs = require('fs');
const file = 'resources/js/Pages/Public/AgendarCita.vue';
let content = fs.readFileSync(file, 'utf8');

// Labels
content = content.replace(/text-gray-700/g, 'text-gray-700 dark:text-gray-300');
// Inputs, textareas, selects
content = content.replace(/bg-white dark:bg-slate-900/g, 'bg-white dark:bg-slate-900 dark:text-white');
content = content.replace(/class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-slate-800/g, 'class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-white');
// Wait, ensure we don't accidentally duplicate
content = content.replace(/dark:bg-slate-900 dark:text-white dark:bg-slate-900/g, 'dark:bg-slate-900 dark:text-white');
content = content.replace(/dark:text-gray-300 dark:text-gray-300/g, 'dark:text-gray-300');

// Step indicators
content = content.replace(/'bg-gray-200 text-gray-500 dark:text-gray-400'/g, "'bg-gray-200 dark:bg-slate-800 text-gray-500 dark:text-gray-400'");

// Progress bar text
content = content.replace(/bg-gray-200 rounded-full overflow-hidden/g, 'bg-gray-200 dark:bg-slate-800 rounded-full overflow-hidden');

// Buttons / cards hovering
content = content.replace(/hover:bg-white dark:bg-slate-900/g, 'hover:bg-white dark:hover:bg-slate-800 dark:bg-slate-900');

fs.writeFileSync(file, content);
