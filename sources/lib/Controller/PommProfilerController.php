<?php

/*
 * This file is part of Pomm's SymfonyBidge package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PommProject\SymfonyBridge\Controller;

use PommProject\Foundation\Exception\FoundationException;
use PommProject\Foundation\Pomm;
use PommProject\Foundation\QueryManager\QueryManagerClient;
use PommProject\SymfonyBridge\DatabaseDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Controllers for the Pomm profiler extension.
 *
 * @package PommSymfonyBridge
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PommProfilerController
{
    public function __construct(
        private readonly Profiler $profiler,
        private readonly Environment $twig,
        private readonly Pomm $pomm
    ) {
    }

    /**
     * Controller to explain a SQL query.
     *
     * @throws FoundationException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function explainAction(Request $request, string $token, int $index_query): Response
    {
        $panel = 'pomm';
        $page = 'home';

        if (!($profile = $this->profiler->loadProfile($token))) {
            $profileType = $request->query->get('type', 'request');
            return new Response(
                $this->twig->render(
                    '@WebProfiler/Profiler/info.html.twig',
                    ['about' => 'no_token', 'token' => $token, 'request' => $request, 'profile_type' => $profileType]
                ),
                Response::HTTP_OK,
                ['Content-Type' => 'text/html']
            );
        }

        $this->profiler->disable();

        if (!$profile->hasCollector($panel)) {
            throw new NotFoundHttpException(sprintf('Panel "%s" is not available for token "%s".', $panel, $token));
        }

        /** @var DatabaseDataCollector $databaseDataCollector */
        $databaseDataCollector = $profile->getCollector($panel);

        if (!array_key_exists($index_query, $databaseDataCollector->getQueries())) {
            throw new \InvalidArgumentException(sprintf("No such query index '%s'.", $index_query));
        }

        $query_data = $databaseDataCollector->getQueries()[$index_query];

        /** @var QueryManagerClient $queryManager */
        $queryManager = $this->pomm[$query_data['session_stamp']]
            ->getClientUsingPooler('query_manager', null);

        $explain = $queryManager->query(sprintf("explain %s", $query_data['sql']), $query_data['parameters']);

        return new Response($this->twig->render('@Pomm/Profiler/explain.html.twig', [
            'token' => $token,
            'profile' => $profile,
            'collector' => $databaseDataCollector,
            'panel' => $panel,
            'page' => $page,
            'request' => $request,
            'query_index' => $index_query,
            'explain' => $explain,
        ]), Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }
}
