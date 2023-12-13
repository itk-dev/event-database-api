<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Api\State\EventRepresentationProvider;

#[ApiResource(operations: [
    new Get(
        openapiContext: [
            'summary' => 'Get single event base on identifier',
            'parameters' => [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'schema' => [
                        'type' => 'integer',
                    ],
                ],
            ],
        ],
        output: EventRepresentationProvider::class,
        provider: EventRepresentationProvider::class,
    ),
])]
class Event
{
    #[ApiProperty(
        identifier: true,
    )]
    private int $id;

    #[ApiProperty()]
    private string $title;
    #[ApiProperty()]
    private string $excerpt;
    #[ApiProperty()]
    private string $description;
    #[ApiProperty()]
    private string $url;
    #[ApiProperty()]
    private string $ticketUrl;
    #[ApiProperty()]
    private bool $public;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function setExcerpt(string $excerpt): static
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getTicketUrl(): string
    {
        return $this->ticketUrl;
    }

    public function setTicketUrl(string $ticketUrl): static
    {
        $this->ticketUrl = $ticketUrl;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }
}
