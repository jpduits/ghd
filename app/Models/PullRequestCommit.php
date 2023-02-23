<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PullRequestCommit extends Pivot
{



    public function pullRequest()
    {
        return $this->belongsTo(PullRequest::class, 'pull_request_id', 'id');
    }

    public function commit()
    {
        return $this->belongsTo(Commit::class, 'commit_id', 'id');
    }

}
