<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
  href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
  rel="stylesheet">

@vite([
  'resources/assets/vendor/fonts/boxicons.scss',
  'resources/assets/vendor/fonts/fontawesome.scss',
  'resources/assets/vendor/fonts/flag-icons.scss'
])
<!-- Core CSS -->
@vite(['resources/assets/vendor/scss'.$configData['rtlSupport'].'/core' .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.scss',
'resources/assets/vendor/scss'.$configData['rtlSupport'].'/' .$configData['theme'] .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.scss',
'resources/assets/css/demo.css'])


<!-- Vendor Styles -->
@vite([
  'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
  'resources/assets/vendor/libs/typeahead-js/typeahead.scss',
])

<!-- Sidebar Footer Styles -->
<style>
/* Minimal Sidebar Footer Styles */
.menu-footer {
  background: rgba(var(--bs-body-color-rgb), 0.03);
  font-size: 0.75rem;
}

.menu-footer a {
  transition: color 0.2s ease;
  text-decoration: none;
}

.menu-footer a:hover {
  color: var(--bs-primary) !important;
}

.status-dot {
  display: inline-block;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  margin-left: 2px;
  position: relative;
  top: -1px;
}

.status-dot.bg-success {
  background-color: var(--bs-success) !important;
  animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
  0% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
  100% {
    opacity: 1;
  }
}

/* Dark mode adjustments */
[data-theme="dark"] .menu-footer {
  background: rgba(255, 255, 255, 0.05);
}
</style>

@yield('vendor-style')

<!-- Page Styles -->
@yield('page-style')
