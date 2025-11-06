        </main> 
        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; TSGI <?php echo date('Y'); ?></div>
                </div>
            </div>
        </footer>
    </div> 
</div> 
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus item ini? Tindakan ini tidak dapat dibatalkan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a id="btn-confirm-delete" class="btn btn-danger" href="#">Ya, Hapus</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
	<script>
        // Inisialisasi Summernote pada semua textarea dengan class 'summernote-editor'
        $(document).ready(function() {
            $('.summernote-editor').summernote({
                height: 300,                 // set editor height
                minHeight: null,             // set minimum height of editor
                maxHeight: null,             // set maximum height of editor
                focus: true,                 // set focus to editable area after initializing summernote
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']], // Hapus 'picture', 'video' jika tidak ingin upload gambar via editor
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
        });
    </script>
<script>
    // Pastikan DOM sudah dimuat
    document.addEventListener('DOMContentLoaded', function () {
        
        var deleteModal = document.getElementById('confirmDeleteModal');
        if (deleteModal) {
            
            // Dengarkan event 'show.bs.modal'
            deleteModal.addEventListener('show.bs.modal', function (event) {
                
                // Ambil tombol yang memicu modal (tombol tong sampah)
                var button = event.relatedTarget;
                
                // Ekstrak URL hapus dari atribut data-bs-url
                var deleteUrl = button.getAttribute('data-bs-url');
                
                // Cari tombol 'Ya, Hapus' di dalam modal
                var confirmButton = deleteModal.querySelector('#btn-confirm-delete');
                
                // Set 'href' tombol 'Ya, Hapus' menjadi URL yang benar
                confirmButton.setAttribute('href', deleteUrl);
            });
        }
    });
    </script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest"></script>
<script src="<?php echo $admin_base; ?>/js/datatables-simple-demo.js"></script>
<script src="<?php echo $admin_base; ?>/js/scripts.js"></script>
</body>
</html>