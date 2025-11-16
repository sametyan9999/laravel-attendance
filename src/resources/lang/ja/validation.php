<?php

return [

    // 共通メッセージ
    'required'   => ':attributeを入力してください',
    'email'      => ':attributeの形式が正しくありません',
    'max'        => [
        'string' => ':attributeは:max文字以内で入力してください',
    ],
    'min'        => [
            'string' => ':attributeは:min文字以上で入力してください',
    ],
    'unique'     => ':attributeは既に使用されています',
    'confirmed'  => ':attributeと一致しません',

    // ★ テストケースと完全一致させたい文言はここでフィールド別に上書きする
    'custom' => [
        'name' => [
            // 「お名前を入力してください」
            'required' => 'お名前を入力してください',
        ],
        'email' => [
            // 「メールアドレスを入力してください」
            'required' => 'メールアドレスを入力してください',
            // 「メールアドレスの形式が不適切です」
            'email'    => 'メールアドレスの形式が不適切です',
            // 「このメールアドレスは既に使用されています」
            'unique'   => 'メールアドレスは既に使用されています',
        ],
        'password' => [
            // 「パスワードを入力してください」
            'required'  => 'パスワードを入力してください',
            // 「パスワードは8文字以上で入力してください」
            'min'       => 'パスワードは:min文字以上で入力してください',
            // 「パスワードと一致しません」
            'confirmed' => 'パスワードと一致しません',
        ],
        'password_confirmation' => [
            // 必要ならここに追加
        ],
    ],

    // ラベル名置き換え（:attribute に使われる）
    'attributes' => [
        'name'                  => 'お名前',
        'email'                 => 'メールアドレス',
        'password'              => 'パスワード',
        'password_confirmation' => 'パスワード確認',
    ],
];