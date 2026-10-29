            async processSingleFile(fileItem) {
                fileItem.status = 'processing';
                
                const formData = new FormData();
                formData.append('document', fileItem.file);

                try {
                    const response = await fetch(processAiUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    if (response.status === 419) {
                        throw new Error("Sesi Anda telah kedaluwarsa. Silakan muat ulang halaman.");
                    }
                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("Respons server bukan JSON (Terjadi galat 500).");
                    }

                    const result = await response.json();

                    if (response.ok) {
                        fileItem.status = 'success';
                        fileItem.data.file_path = result.data.file_path;
                        fileItem.data.file_type = result.data.file_type;
                        fileItem.data.extracted_text = result.data.extracted_text;
                        fileItem.data.title = result.data.title;
                        fileItem.data.category_id = result.data.category_id;
                        fileItem.data.classification_id = result.data.classification_id;
                        fileItem.data.summary = result.data.summary;
                        fileItem.data.ai_model = result.data.ai_model;
                        fileItem.data.ai_confidence_score = result.data.ai_confidence_score;
                    } else {
                        throw new Error(result.message || 'Terjadi kesalahan');
                    }
                } catch (error) {
                    fileItem.status = 'error';
                    fileItem.error = error.message;
                }
            },

            async saveAll() {
                if (this.files.length === 0) return;
                
                // Validate
                for (let i = 0; i < this.files.length; i++) {
                    const f = this.files[i].data;
                    if (!f.title || !f.category_id || !f.classification_id) {
                        this.globalError = `File ${i+1} memiliki isian wajib yang kosong.`;
                        this.activeIndex = i;
                        return;
                    }
                }

                this.isSaving = true;
                this.globalError = null;

                const payload = {
                    archives: this.files.map(f => f.data)
                };

                try {
                    const response = await fetch(bulkStoreUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (response.status === 419) {
                        throw new Error("Sesi Anda telah kedaluwarsa. Silakan muat ulang halaman.");
                    }
                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("Respons server bukan JSON (Terjadi galat 500).");
                    }

                    const result = await response.json();

                    if (response.ok) {
                        window.location.href = redirectUrl;
                    } else {
                        let errMsg = result.message || 'Terjadi kesalahan saat menyimpan.';
                        if (result.errors) {
                            const firstErr = Object.values(result.errors)[0];
                            errMsg += ' ' + (Array.isArray(firstErr) ? firstErr[0] : firstErr);
                        }
                        throw new Error(errMsg);
                    }
                } catch (error) {
                    this.globalError = error.message;
                    this.isSaving = false;
                }
            },
