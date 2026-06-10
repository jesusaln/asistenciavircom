<?php

namespace App\Support;

class ConfigValidationRules
{
    public static function colorHexNullable(): array
    {
        return ['nullable', 'string', 'regex:/^#[0-9A-F]{6}$/i'];
    }

    public static function colorHexRequired(): array
    {
        return ['required', 'string', 'regex:/^#[0-9A-F]{6}$/i'];
    }

    public static function logoImage(): array
    {
        return ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'];
    }

    public static function faviconImage(): array
    {
        return ['required', 'image', 'mimes:jpeg,png,jpg,gif,ico', 'max:1024'];
    }

    public static function logoReportesImage(): array
    {
        return ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'];
    }

    public static function smtpConfig(): array
    {
        return [
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|string|in:tls,ssl',
            'email_from_address' => 'nullable|email|max:255',
            'email_from_name' => 'nullable|string|max:255',
            'email_reply_to' => 'nullable|email|max:255',
        ];
    }

    public static function reportEmail(): array
    {
        return [
            'email' => 'required|email',
            'test_mode' => 'nullable|boolean',
        ];
    }

    public static function emailDestino(): array
    {
        return [
            'email_destino' => ['required', 'email'],
        ];
    }
}
