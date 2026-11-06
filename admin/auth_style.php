<?php
// admin/auth_style.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= $title ?? 'Admin Auth'; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
  body { background-color: #0F1115; color: white; }
  .card-glass{
    background: rgba(26, 29, 35, 0.85);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.06);
  }
  .accent-orange { color: #FF6600; }
  .bg-orange-maju { background-color: #FF6600; }
</style>
</head>

<body class="font-sans min-h-screen flex items-center justify-center px-6">
  <div class="w-full max-w-xl">
