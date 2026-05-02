<?php

return [

    'required' => ':attributeを入力してください。',
    'email' => ':attributeはメール形式で入力してください。',
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください。',
    ],
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'confirmed' => ':attributeが確認用と一致しません。',
    'numeric' => ':attributeは数値で入力してください。',
    'image' => ':attributeは画像ファイルを選択してください。',
    'mimes' => ':attributeは:values形式でアップロードしてください。',
    'regex' => ':attributeの形式が正しくありません。',
    'exists' => '選択した:attributeが不正です。',
    'unique' => 'この:attributeは既に登録されています。',

    'attributes' => [
        'name' => 'ユーザー名',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => '確認用パスワード',
        'body' => '商品コメント',
        'zip' => '郵便番号',
        'address' => '住所',
        'building' => '建物名等',
        'profile_image' => 'プロフィール画像',
        'description' => '商品説明',
        'image' => '商品画像',
        'price' => '商品価格',
        'payment_method_id' => '支払い方法',
        'category_ids' => '商品のカテゴリー',
        'condition_id' => '商品の状態',
    ],
];