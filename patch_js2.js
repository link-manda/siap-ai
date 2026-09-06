            removeFile(index) {
                const fileItem = this.files[index];
                if (fileItem && fileItem.previewUrl) {
                    URL.revokeObjectURL(fileItem.previewUrl);
                }
                this.files.splice(index, 1);
                if (this.activeIndex >= this.files.length) {
                    this.activeIndex = Math.max(0, this.files.length - 1);
                }
            },

            async openPreview(fileItem) {
                this.previewFile = fileItem;
                this.previewModalOpen = true;
                this.previewLoading = true;
                
                const container = document.getElementById('preview-content');
                container.innerHTML = '';
                
                const file = fileItem.file;
                const ext = file.name.split('.').pop().toLowerCase();
                
                try {
                    if (!window.DOMPurify) {
                        throw new Error('Pustaka keamanan DOMPurify tidak termuat (Fail-Closed).');
                    }
                    
                    if (['pdf', 'jpg', 'jpeg', 'png'].includes(ext)) {
                        if (!fileItem.previewUrl) {
                            fileItem.previewUrl = URL.createObjectURL(file);
                        }
                        const url = fileItem.previewUrl;
                        if (ext === 'pdf') {
                            container.innerHTML = `<div class="relative w-full h-[600px]"><iframe src="${url}" class="w-full h-full border-0 rounded-lg shadow-sm" sandbox="allow-same-origin"></iframe><div class="absolute inset-0 pointer-events-none flex items-center justify-center opacity-10"><span class="text-6xl font-bold rotate-45">CONFIDENTIAL</span></div></div>`;
                        } else {
                            container.innerHTML = `<div class="relative inline-block"><img src="${url}" class="max-w-full h-auto mx-auto rounded-lg shadow-sm" /><div class="absolute inset-0 pointer-events-none flex items-center justify-center opacity-20"><span class="text-4xl font-bold rotate-45 text-white bg-black/30 px-4 py-2 rounded">CONFIDENTIAL</span></div></div>`;
                        }
                    } else if (['doc', 'docx'].includes(ext)) {
                        const arrayBuffer = await file.arrayBuffer();
                        if (window.mammoth) {
                            const result = await window.mammoth.convertToHtml({arrayBuffer: arrayBuffer});
                            let html = window.DOMPurify.sanitize(result.value);
                            container.innerHTML = `<div class="bg-white p-8 rounded-lg shadow-sm prose max-w-none dark:bg-gray-900 dark:prose-invert">${html}</div>`;
                        } else {
                            container.innerText = 'Pustaka Mammoth.js tidak termuat.';
                        }
                    } else if (['xls', 'xlsx'].includes(ext)) {
                        const arrayBuffer = await file.arrayBuffer();
                        if (window.XLSX) {
                            const workbook = window.XLSX.read(arrayBuffer, {type: 'array'});
                            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                            let html = window.DOMPurify.sanitize(window.XLSX.utils.sheet_to_html(firstSheet, { id: 'excel-table' }));
                            container.innerHTML = `<div class="bg-white p-4 rounded-lg shadow-sm overflow-x-auto dark:bg-gray-900 excel-preview">${html}</div>`;
                        } else {
                            container.innerText = 'Pustaka SheetJS tidak termuat.';
                        }
                    } else {
                        container.innerText = 'Format file tidak didukung untuk pratinjau cepat.';
                    }
                } catch (e) {
                    container.innerText = `Gagal merender pratinjau: ${e.message}`;
                    container.className = "text-error-500 text-center p-4 font-medium";
                } finally {
                    this.previewLoading = false;
                }
            }
