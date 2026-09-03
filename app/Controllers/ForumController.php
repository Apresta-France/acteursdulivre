<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Data\Seo;
use Adl\Data\Share;
use Adl\Models\ForumCategory;
use Adl\Models\ForumPost;
use Adl\Models\ForumTopic;
use Adl\Models\Report;

final class ForumController
{
    public function index(Request $request): void
    {
        $filter = $this->resolveFilter($request->string('filtre'));
        $page = max(1, $request->int('page', 1) ?? 1);
        $q = trim($request->string('q'));
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        if ($filter === 'mine' && $userId <= 0) {
            Auth::requireUser();
            return;
        }

        $categories = [];
        $stats = ['topics' => 0, 'posts' => 0, 'unanswered' => 0, 'week' => 0];
        $recent = [];
        $unanswered = [];
        $contributors = [];
        $tags = [];
        $listing = ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => ForumTopic::PER_PAGE];

        try {
            $categories = ForumCategory::all();
            $stats = ForumTopic::stats();
            $recent = ForumTopic::recent(3);
            $unanswered = ForumTopic::unanswered(4);
            $contributors = ForumTopic::topContributors(5);
            $tags = ForumTopic::popularTags(12);
            $listing = ForumTopic::list([
                'filter' => $filter,
                'page' => $page,
                'q' => $q,
                'user_id' => $userId,
            ]);
        } catch (\Throwable) {
        }

        $meta = Seo::forScreen('forum');
        if ($q !== '' || $page > 1 || $filter !== 'recent') {
            $meta['robots'] = 'noindex, follow';
        }
        $meta['url'] = Share::absolute('/forum');

        View::page('forum', [
            'title' => 'Le forum des métiers du livre',
            'meta' => $meta,
            'forumCategories' => $categories,
            'forumStats' => $stats,
            'forumRecent' => $recent,
            'forumUnanswered' => $unanswered,
            'forumContributors' => $contributors,
            'forumTags' => $tags,
            'forumTopics' => $listing['items'],
            'forumFilter' => $filter,
            'forumQ' => $q,
            'pager' => [
                'page' => $listing['page'],
                'pages' => $listing['pages'],
                'total' => $listing['total'],
            ],
        ]);
    }

    public function category(Request $request, string $slug): void
    {
        try {
            $category = ForumCategory::findBySlug($slug);
        } catch (\Throwable) {
            $category = null;
        }
        if (!$category) {
            not_found('Cette rubrique n\'existe pas.');
        }

        $filter = $this->resolveCategoryFilter($request->string('filtre'));
        $page = max(1, $request->int('page', 1) ?? 1);
        $listing = ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
        $contributors = [];
        $tags = [];
        $pinnedReads = [];
        $postCount = 0;

        try {
            $listing = ForumTopic::list([
                'category_id' => (int) $category['id'],
                'filter' => $filter,
                'page' => $page,
            ]);
            $contributors = ForumTopic::topContributors(4);
            $tags = ForumTopic::popularTags(8, (int) $category['id']);
            $postCount = ForumCategory::countPosts((int) $category['id']);
            $pinnedReads = array_values(array_filter(
                $listing['items'],
                static fn (array $t): bool => !empty($t['is_pinned'])
            ));
            if ($pinnedReads === []) {
                $pinnedReads = array_slice($listing['items'], 0, 3);
            }
        } catch (\Throwable) {
        }

        $meta = Seo::build(
            (string) $category['name'] . ' — Forum',
            (string) $category['description'],
            (string) $category['href']
        );
        if ($page > 1 || $filter !== 'recent') {
            $meta['robots'] = 'noindex, follow';
        }

        View::page('forum-categorie', [
            'title' => (string) $category['name'],
            'meta' => $meta,
            'forumCategory' => $category,
            'forumTopics' => $listing['items'],
            'forumFilter' => $filter,
            'forumContributors' => $contributors,
            'forumTags' => $tags,
            'forumPinnedReads' => $pinnedReads,
            'forumPostCount' => $postCount,
            'pager' => [
                'page' => $listing['page'],
                'pages' => $listing['pages'],
                'total' => $listing['total'],
            ],
        ]);
    }

    public function topic(Request $request, string $categorySlug, string $topicSlug): void
    {
        try {
            $category = ForumCategory::findBySlug($categorySlug);
            $topic = $category
                ? ForumTopic::findBySlug((int) $category['id'], $topicSlug)
                : null;
        } catch (\Throwable) {
            $category = null;
            $topic = null;
        }
        if (!$category || !$topic || ($topic['status'] ?? '') !== 'visible') {
            not_found('Cette discussion n\'existe pas.');
        }

        $sort = $request->string('tri') === 'utiles' ? 'useful' : 'chrono';
        $page = max(1, $request->int('page', 1) ?? 1);
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        try {
            ForumTopic::bumpViews((int) $topic['id']);
            $topic['view_count'] = (int) $topic['view_count'] + 1;
            $topic['views_label'] = format_int((int) $topic['view_count']);
        } catch (\Throwable) {
        }

        $op = null;
        $replies = ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
        $participants = [];
        $participantCount = 0;
        $related = [];
        $following = false;
        $authorPostCount = 0;

        try {
            $op = ForumPost::opening((int) $topic['id']);
            $replies = ForumPost::forTopic((int) $topic['id'], $sort, $page, $userId ?: null);
            $participants = ForumPost::participants((int) $topic['id']);
            $participantCount = ForumPost::participantCount((int) $topic['id']);
            $related = ForumTopic::related((int) $topic['category_id'], (int) $topic['id'], 4);
            if ($userId > 0) {
                $following = ForumTopic::isFollowing((int) $topic['id'], $userId);
                ForumTopic::markRead((int) $topic['id'], $userId);
            }
            if ($op) {
                $authorPostCount = ForumPost::authorPostCount((int) $op['user_id']);
            }
        } catch (\Throwable) {
        }

        $meta = Seo::build(
            (string) $topic['title'],
            mb_substr(plain_text((string) ($op['body'] ?? '')), 0, 160) ?: 'Discussion sur le forum des métiers du livre.',
            (string) $topic['href'],
            'article'
        );
        $meta['json_ld'] = [
            Seo::organization(),
            Seo::website(),
            Seo::breadcrumb([
                ['name' => 'Acteurs du Livre', 'url' => '/'],
                ['name' => 'Forum', 'url' => '/forum'],
                ['name' => (string) $category['name'], 'url' => (string) $category['href']],
                ['name' => (string) $topic['title'], 'url' => (string) $topic['href']],
            ]),
        ];

        View::page('forum-discussion', [
            'title' => (string) $topic['title'],
            'meta' => $meta,
            'forumCategory' => $category,
            'forumTopic' => $topic,
            'forumOp' => $op,
            'forumReplies' => $replies['items'],
            'forumReplyTotal' => $replies['total'],
            'forumSort' => $sort,
            'forumParticipants' => $participants,
            'forumParticipantCount' => $participantCount,
            'forumRelated' => $related,
            'forumFollowing' => $following,
            'forumAuthorPostCount' => $authorPostCount,
            'forumReportReasons' => Report::REASONS,
            'forumFlashError' => flash('error'),
            'old' => flash('old') ?: [],
            'pager' => [
                'page' => $replies['page'],
                'pages' => $replies['pages'],
                'total' => $replies['total'],
            ],
        ]);
    }

    public function createForm(Request $request): void
    {
        Auth::requireUser();
        $categories = [];
        try {
            $categories = ForumCategory::all();
        } catch (\Throwable) {
        }
        $preselect = $request->string('rubrique');
        $old = flash('old') ?: [];

        View::page('forum-nouveau', [
            'title' => 'Ouvrir une discussion',
            'meta' => Seo::build(
                'Ouvrir une discussion — Forum',
                'Posez une question concrète aux professionnels du livre.',
                '/forum/nouveau',
                'website',
                null,
                ['robots' => 'noindex, follow']
            ),
            'forumCategories' => $categories,
            'forumCategorySlug' => $preselect,
            'forumFlashError' => flash('error'),
            'old' => $old,
        ]);
    }

    public function suggestApi(Request $request): void
    {
        $q = $request->string('q');
        $limit = max(1, min(10, $request->int('limit', 6) ?? 6));
        $categoryId = max(0, $request->int('category_id', 0) ?? 0);
        try {
            $suggestions = ForumTopic::suggestSimilar($q, $categoryId, $limit);
        } catch (\Throwable) {
            $suggestions = [];
        }
        foreach ($suggestions as $i => $item) {
            $suggestions[$i]['href'] = url((string) ($item['href'] ?? '/forum'));
        }
        json_response([
            'suggestions' => $suggestions,
            'results' => $suggestions,
        ]);
    }

    public function searchApi(Request $request): void
    {
        $q = $request->string('q');
        $limit = max(1, min(12, $request->int('limit', 8) ?? 8));
        try {
            $suggestions = ForumTopic::suggestSearch($q, $limit);
        } catch (\Throwable) {
            $suggestions = [];
        }
        foreach ($suggestions as $i => $item) {
            $suggestions[$i]['href'] = url((string) ($item['href'] ?? '/forum'));
        }
        json_response([
            'suggestions' => $suggestions,
            'results' => $suggestions,
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requireUser();
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        try {
            $topic = ForumTopic::create($userId, [
                'category_id' => $request->int('category_id'),
                'title' => $request->string('title'),
                'body' => $request->string('body'),
                'tags' => $request->string('tags'),
                'no_ai' => $request->bool('no_ai'),
            ]);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e, 'Impossible de publier la discussion.'));
            flash('old', [
                'category_id' => $request->int('category_id'),
                'title' => $request->string('title'),
                'body' => $request->string('body'),
                'tags' => $request->string('tags'),
            ]);
            redirect('/forum/nouveau');
        }

        flash('saved', 'Discussion publiée.');
        redirect((string) $topic['href']);
    }

    public function reply(Request $request, string $categorySlug, string $topicSlug): void
    {
        Auth::requireUser();
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        try {
            $category = ForumCategory::findBySlug($categorySlug);
            $topic = $category
                ? ForumTopic::findBySlug((int) $category['id'], $topicSlug)
                : null;
        } catch (\Throwable) {
            $category = null;
            $topic = null;
        }
        if (!$category || !$topic || ($topic['status'] ?? '') !== 'visible') {
            not_found('Cette discussion n\'existe pas.');
        }

        try {
            ForumPost::create((int) $topic['id'], $userId, [
                'body' => $request->string('body'),
                'parent_id' => $request->int('parent_id'),
                'no_ai' => $request->bool('no_ai'),
            ]);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e, 'Impossible de publier la réponse.'));
            flash('old', ['body' => $request->string('body')]);
            redirect((string) $topic['href'] . '#repondre');
        }

        flash('saved', 'Réponse publiée.');
        redirect((string) $topic['href'] . '#reponses');
    }

    public function follow(Request $request, string $categorySlug, string $topicSlug): void
    {
        Auth::requireUser();
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $topic = $this->resolveTopic($categorySlug, $topicSlug);

        try {
            $on = ForumTopic::toggleFollow((int) $topic['id'], $userId);
            flash('saved', $on ? 'Discussion suivie.' : 'Vous ne suivez plus cette discussion.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e, 'Impossible de mettre à jour le suivi.'));
        }
        redirect((string) $topic['href']);
    }

    public function useful(Request $request, string $categorySlug, string $topicSlug, string $postId): void
    {
        Auth::requireUser();
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $topic = $this->resolveTopic($categorySlug, $topicSlug);

        try {
            ForumPost::toggleUseful((int) $postId, $userId, (int) $topic['id']);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e, 'Impossible d\'enregistrer ce vote.'));
        }
        redirect((string) $topic['href'] . '#post-' . (int) $postId);
    }

    public function solve(Request $request, string $categorySlug, string $topicSlug, string $postId): void
    {
        Auth::requireUser();
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $isAdmin = ($user['role'] ?? '') === 'admin';
        $topic = $this->resolveTopic($categorySlug, $topicSlug);

        try {
            ForumTopic::markSolved((int) $topic['id'], (int) $postId, $userId, $isAdmin);
            flash('saved', 'Réponse retenue.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e, 'Impossible de retenir cette réponse.'));
        }
        redirect((string) $topic['href'] . '#post-' . (int) $postId);
    }

    public function deleteReply(Request $request, string $categorySlug, string $topicSlug, string $postId): void
    {
        Auth::requireUser();
        $user = Auth::user();
        $isAdmin = ($user['role'] ?? '') === 'admin';
        $topic = $this->resolveTopic($categorySlug, $topicSlug);

        try {
            ForumPost::hide((int) $postId, (int) $topic['id'], $isAdmin);
            flash('saved', 'Réponse supprimée.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e, 'Impossible de supprimer cette réponse.'));
        }
        redirect((string) $topic['href'] . '#reponses');
    }

    /** @return array<string, mixed> */
    private function resolveTopic(string $categorySlug, string $topicSlug): array
    {
        try {
            $category = ForumCategory::findBySlug($categorySlug);
            $topic = $category
                ? ForumTopic::findBySlug((int) $category['id'], $topicSlug)
                : null;
        } catch (\Throwable) {
            $topic = null;
        }
        if (!$topic || ($topic['status'] ?? '') !== 'visible') {
            not_found('Cette discussion n\'existe pas.');
        }
        return $topic;
    }

    private function resolveFilter(string $raw): string
    {
        return match ($raw) {
            'sans-reponse' => 'unanswered',
            'suivies' => 'popular',
            'mine' => 'mine',
            default => 'recent',
        };
    }

    private function resolveCategoryFilter(string $raw): string
    {
        return match ($raw) {
            'sans-reponse' => 'unanswered',
            'utiles' => 'useful',
            'resolues' => 'solved',
            default => 'recent',
        };
    }
}
