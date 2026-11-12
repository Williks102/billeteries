<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Les formulaires ne doivent jamais afficher de clé brute (« validation.required »).
 */
class ValidationFrenchMessagesTest extends TestCase
{
    public function test_les_messages_de_validation_sont_en_francais(): void
    {
        $messages = Validator::make(['email' => 'pas-un-email'], [
            'name' => 'required',
            'email' => 'email',
            'age' => 'required|integer',
        ])->errors();

        $this->assertSame('le nom est obligatoire.', mb_strtolower($messages->first('name')));
        $this->assertStringContainsString('adresse e-mail valide', $messages->first('email'));
        $this->assertStringNotContainsString('validation.', $messages->first('age'));
    }

    public function test_aucune_cle_brute_pour_les_regles_courantes(): void
    {
        foreach (['required', 'email', 'integer', 'min.string', 'max.string', 'unique', 'confirmed', 'exists', 'image', 'date'] as $key) {
            $this->assertNotSame('validation.'.$key, trans('validation.'.$key), "Clé brute : validation.$key");
        }
    }
}
