{{-- ============================================================
     Script.blade.php — FIXED
     Hanya init plugin global, TIDAK ada chart/datatable spesifik
============================================================ --}}

<!-- Required vendors -->
    <script src="{{ asset('xhtml/vendor/global/global.min.js') }}"></script>
	<script src="{{ asset('xhtml/vendor/chart.js/Chart.bundle.min.js') }}"></script>
	<script src="{{ asset('xhtml/vendor/jquery-nice-select/js/jquery.nice-select.min.js') }}"></script>
	
	<!-- Apex Chart -->
	<script src="{{ asset('xhtml/vendor/apexchart/apexchart.js') }}"></script>
	<script src="{{ asset('xhtml/vendor/nouislider/nouislider.min.js') }}"></script>
	<script src="{{ asset('xhtml/vendor/wnumb/wNumb.js') }}"></script>

    <!-- Datatable -->
    <script src="{{ asset('xhtml/vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('xhtml/js/plugins-init/datatables.init.js') }}"></script>

	<!-- Toastr -->
    <script src="{{ asset('xhtml/vendor/toastr/js/toastr.min.js') }}"></script>

    <!-- All init script -->
    <script src="{{ asset('xhtml/js/plugins-init/toastr-init.js') }}"></script>
	
	<!-- Dashboard 1 -->
	<script src="{{ asset('xhtml/js/dashboard/dashboard-1.js') }}"></script>

    <script src="{{ asset('xhtml/js/custom.min.js') }}"></script>
	<script src="{{ asset('xhtml/js/dlabnav-init.js') }}"></script>

{{-- 8. SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

	{{-- <script>
	document.addEventListener('DOMContentLoaded', () => {
		const text = "Selamat Datang, {{ Auth::user()->name }} 👋";
		const typedEl = document.getElementById('navWelcomeTyped');
		let i = 0, isDeleting = false;
		function typeLoop() {
			typedEl.textContent = text.substring(0, i);
			if (!isDeleting && i < text.length) {
				i++; setTimeout(typeLoop, 80);
			} else if (isDeleting && i > 0) {
				i--; setTimeout(typeLoop, 50);
			} else {
				isDeleting = !isDeleting;
				setTimeout(typeLoop, isDeleting ? 3000 : 500);
			}
		}
		typeLoop();
	});
	</script> --}}

	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var el = document.getElementById('navWelcomeTyped');
		if (!el) return;

		var pesan = [
			'Selamat Datang, {{ Auth::user()->name }} 👋',
			'Sistem Informasi Pelaporan BOS Online',
			'Semoga harimu menyenangkan!',
		];
		var iPesan = 0, iChar = 0, hapus = false;

		function ketik() {
			var teks = pesan[iPesan];
			if (!hapus) {
				el.textContent = teks.substring(0, iChar + 1);
				iChar++;
				if (iChar === teks.length) { hapus = true; setTimeout(ketik, 1800); return; }
			} else {
				el.textContent = teks.substring(0, iChar - 1);
				iChar--;
				if (iChar === 0) { hapus = false; iPesan = (iPesan + 1) % pesan.length; }
			}
			setTimeout(ketik, hapus ? 40 : 70);
		}
		ketik();
	});
	</script>

	<script>
		toastr.options = {
			closeButton: true,
			progressBar: true,
			positionClass: 'toast-top-right',
			timeOut: 5000,
			extendedTimeOut: 2000,
			showMethod: 'slideDown',
			hideMethod: 'fadeOut',
			showDuration: 350,
			hideDuration: 250,
			newestOnTop: true,
			preventDuplicates: true
		};

		@if(session('success'))
			toastr.success(@json(session('success')), 'Berhasil');
		@endif

		@if(session('error'))
			toastr.error(@json(session('error')), 'Gagal');
		@endif

		@if(session('warning'))
			toastr.warning(@json(session('warning')), 'Perhatian');
		@endif

		@if(session('info'))
			toastr.info(@json(session('info')), 'Informasi');
		@endif

		@if($errors->any())
			@foreach($errors->all() as $e)
				toastr.error(@json($e), 'Validasi Gagal');
			@endforeach
		@endif
		</script>

{{-- 8. @stack('scripts') — WAJIB ADA untuk @push dari setiap view --}}
@stack('scripts')

