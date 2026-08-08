<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;

class DocumentVersionPolicy
{
    public function create(User $user, Document $document): bool
    {
        return app(DocumentPolicy::class)->update($user, $document);
    }

    public function delete(User $user, DocumentVersion $version): bool
    {
        return app(DocumentPolicy::class)->update($user, $version->document);
    }
}
