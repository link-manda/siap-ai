import './bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import mammoth from 'mammoth/mammoth.browser.js';
import * as XLSX from 'xlsx';
import DOMPurify from 'dompurify';

window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.mammoth = mammoth;
window.XLSX = XLSX;
window.DOMPurify = DOMPurify;

Alpine.start();
