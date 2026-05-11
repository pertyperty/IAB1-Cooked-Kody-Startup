console.log('Kody starter app loaded.');

(function(){
	// Simple client-side validation for admin create/edit forms
	function validateAdminForm(e){
		var form = e.target;
		if (!form.classList.contains('admin-validated')) return;
		var maxImageBytes = 2 * 1024 * 1024; // 2MB
		var files = form.querySelectorAll('input[type="file"]');
		for (var i=0;i<files.length;i++){
			var f = files[i];
			if (f.files && f.files[0]){
				var file = f.files[0];
				if (file.size > maxImageBytes){
					alert('File "' + file.name + '" is too large (max 2MB).');
					e.preventDefault();
					return false;
				}
				var allowed = ['image/jpeg','image/png','image/gif'];
				if (allowed.indexOf(file.type) === -1){
					alert('Invalid file type for "' + file.name + '". Only JPG/PNG/GIF allowed.');
					e.preventDefault();
					return false;
				}
			}
		}
		return true;
	}

	document.addEventListener('submit', function(ev){
		var form = ev.target;
		if (form && (form.action.indexOf('/actions/create.php') !== -1 || form.action.indexOf('/actions/update.php') !== -1)){
			// mark and validate
			form.classList.add('admin-validated');
			return validateAdminForm(ev);
		}
	}, true);
	// Auto-dismiss server flash messages
	window.addEventListener('load', function(){
		var flash = document.querySelector('.crud-flash');
		if (flash){
			setTimeout(function(){ try{ flash.remove(); }catch(e){} }, 4200);
		}
	});

	// Keep users at their current reading position after same-page submits/actions.
	var scrollPosKey = 'kody-scroll-pos';
	var scrollPathKey = 'kody-scroll-path';

	function rememberScrollForCurrentPath(){
		try {
			sessionStorage.setItem(scrollPosKey, String(window.scrollY || window.pageYOffset || 0));
			sessionStorage.setItem(scrollPathKey, window.location.pathname);
		} catch (e) {}
	}

	document.addEventListener('click', function(ev){
		var target = ev.target;
		if (!target) return;
		var anchor = target.closest('a[href="#"]');
		if (anchor && !anchor.classList.contains('skip-link')) {
			ev.preventDefault();
		}
		var clickable = target.closest('button, input[type="submit"], input[type="button"], input[type="reset"], a.btn, a.button-link, a.card-button, a.primary');
		if (!clickable) return;
		rememberScrollForCurrentPath();
	}, true);

	document.addEventListener('submit', function(){
		rememberScrollForCurrentPath();
	}, true);

	window.addEventListener('load', function(){
		try {
			var savedPath = sessionStorage.getItem(scrollPathKey);
			var savedPos = sessionStorage.getItem(scrollPosKey);
			if (!savedPath || !savedPos) return;
			if (savedPath !== window.location.pathname) return;
			window.scrollTo(0, parseInt(savedPos, 10) || 0);
			sessionStorage.removeItem(scrollPosKey);
			sessionStorage.removeItem(scrollPathKey);
		} catch (e) {}
	});
})();
