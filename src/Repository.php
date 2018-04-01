<?php

namespace ins0\GitHub;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * A simple class for working with GitHub's "Issues" API.
 *
 * @see https://developer.github.com/v3/issues/
 *
 * @version 0.2.1
 * @author Marco Rieger (ins0)
 * @author Nathan Bishop (nbish11) (Contributor and Refactorer)
 * @copyright (c) 2015 Marco Rieger
 * @license MIT
 */
class Repository
{
    /**
     * The root URL/domain to GitHub's API.
     *
     * @var string
     */
    const GITHUB_API_URL = 'https://api.github.com';

    /**
     * Stores the full URL to the GitHub v3 API "repos" resource.
     *
     * @var string
     */
    private $url;

    /**
     * The GitHub OAUTH token to use, if provided.
     *
     * @var string
     */
    private $token;

    /**
     * @var CacheInterface PSR16 cache
     */
    private $cache;

    /**
     * Constructs a new instance.
     *
     * @param string $repository The username and repository
     *                           provided in the following
     *                           format: ":username/:repository".
     * @param string $token      An optional OAUTH token for
     *                           authentication.
     * @param CacheInterface $cache Optional PSR16 cache
     */
    public function __construct($repository, $token = null, CacheInterface $cache = null)
    {
        if (strpos($repository, '/') === false) {
            throw new InvalidArgumentException('Invalid format. Required format is: ":username/:repository".');
        }

        $this->url = sprintf('%s/repos/%s', self::GITHUB_API_URL, $repository);
        $this->token = $token;
        $this->cache = $cache;
    }

    /**
     * Fetch all releases for the current repository.
     *
     * @param array   $params Allows for advanced sorting.
     * @param integer $page   Skip to a specific page.
     *
     * @return array Always returns an array, regardless of
     *               whether or not there are any releases for
     *               the current repository.
     */
    public function getReleases(array $params = [], $page = 1)
    {
        return $this->fetchCache(sprintf('%s/releases', $this->url), $params, $page);
    }

    /**
     * Fetches all issues for the current repository.
     *
     * @param array   $params Allows for advanced sorting.
     * @param integer $page   Skip to a specific page.
     *
     * @return array Always returns an array, regardless of
     *               whether or not there are any issues for
     *               the current repository.
     */
    public function getIssues(array $params = [], $page = 1)
    {
        return $this->fetchCache(sprintf('%s/issues', $this->url), $params, $page);
    }

    /**
     * Fetch all labels for the current repository.
     *
     * @return array Always returns an array, regardless of
     *               whether or not there are any labels
     *               for the current repository.
     */
    public function getLabels()
    {
        return $this->fetchCache(sprintf('%s/labels', $this->url));
    }

    /**
     * Fetch all available assignees, to which issues may be
     * assigned to.
     *
     * @return array Always returns an array, regardless of
     *               whether or not there are any assignees
     *               for the current repository.
     */
    public function getAssignees()
    {
        return $this->fetchCache(sprintf('%s/assignees', $this->url));
    }

    /**
     * Get all comments for a specific issue.
     *
     * @param integer $number The issue number.
     * @param array   $params Allows for advanced sorting.
     *
     * @return array Always returns an array, regardless of
     *               whether or not there are any comments for
     *               the selected issue.
     */
    public function getIssueComments($number, array $params = [])
    {
        return $this->fetchCache(sprintf('%s/issues/%s/events', $this->url, $number), $params);
    }

    /**
     * Get all events for a specific issue.
     *
     * @param integer $number The issue number.
     *
     * @return array Always returns an array, regardless of
     *               whether or not there are any events for
     *               the selected issue.
     */
    public function getIssueEvents($number)
    {
        return $this->fetchCache(sprintf('%s/issues/%s/events', $this->url, $number));
    }

    /**
     * Get all labels attached to a specific issue.
     *
     * @param integer $number The issue number.
     *
     * @return array Always returns an array, regardless of
     *               whether or not there are any labels for
     *               the selected issue.
     */
    public function getIssueLabels($number)
    {
        return $this->fetchCache(sprintf('%s/issues/%s/labels', $this->url, $number));
    }

    /**
     * Fetch all milestones for the current repository.
     *
     * @param array   $params Allows for advanced sorting.
     * @param integer $page   Skip to a specific page.
     *
     * @return [type] Always returns an array, regardless of
     *                whether or not there are any milestones
     *                for the current repository.
     */
    public function getMilestones(array $params = [], $page = 1)
    {
        return $this->fetchCache(sprintf('%s/milestones', $this->url), $params, $page);
    }

    /**
     * Fetch from cache or repo
     *
     * @param string  $call   [description]
     * @param array   $params [description]
     * @param integer $page   [description]
     *
     * @return object|array [description]
     */
    private function fetchCache($call, array $params = [], $page = 1)
    {
        $params = array_merge($params, [
            'access_token' => $this->token,
            'page' => $page
        ]);

        $url = sprintf('%s?%s', $call, http_build_query($params));

        if (isset($this->cache)) {
            $key = sha1($url);
            if ($this->cache->has($key)) {
                $response = $this->cache->get($key);
            } else {
                $response = $this->fetch($url);
                $this->cache->set($key, $response);
            }
        } else {
            $response = $this->fetch($url);
        }

        if (count(preg_grep('#Link: <(.+?)>; rel="next"#', $http_response_header)) === 1) {
            return array_merge($response, $this->fetchCache($call, $params, ++$page));
        }

        return $response;
    }

    /**
     * Download from github
     *
     * @param $url
     * @return array|bool|mixed|string
     */
    private function fetch($url)
    {
        $options  = [
            'http' => [
                'user_agent' => 'github-changelog-generator'
            ]
        ];

        $context  = stream_context_create($options);
        $response = file_get_contents($url, null, $context);
        $response = $response ? json_decode($response) : [];

        return $response;
    }
}
