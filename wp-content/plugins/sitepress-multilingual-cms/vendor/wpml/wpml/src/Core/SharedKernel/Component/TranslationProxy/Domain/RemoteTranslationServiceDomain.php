<?php

namespace WPML\Core\SharedKernel\Component\TranslationProxy\Domain;

class RemoteTranslationServiceDomain {

  /** @var int */
  private $id;

  /** @var string */
  private $name;

  /** @var bool */
  private $requiresAuthentication;

  /** @var string */
  private $description;

  /** @var string */
  private $url;

  /** @var string */
  private $logoUrl;

  /** @var mixed[] */
  private $customFields;

  /** @var mixed[] */
  private $customFieldsData;

  /** @var RemoteTranslationServiceExtraField[] */
  private $extraFields;

  /** @var bool */
  private $autoRefreshProjectOptions;

  /**
   * When the maximumJobsPerBatch is not set or it's 0 this means that., we don't need to chunk the translation service jobs separately
   * @var int | null
   */
  private $maximumJobsPerBatch;


  /**
   * @param int $id
   * @param string $name
   * @param bool $requiresAuthentication
   * @param string $description
   * @param string $url
   * @param string $logoUrl
   * @param mixed[] $customFields
   * @param mixed[] $customFieldsData
   * @param RemoteTranslationServiceExtraField[] $extraFields
   * @param int | null $maximumJobsPerBatch
   * @param bool $autoRefreshProjectOptions
   */
  public function __construct(
    int $id,
    string $name,
    bool $requiresAuthentication,
    string $description,
    string $url,
    string $logoUrl,
    array $customFields,
    array $customFieldsData,
    array $extraFields,
    int $maximumJobsPerBatch = null,
    bool $autoRefreshProjectOptions = false
  ) {
    $this->id                     = $id;
    $this->name                   = $name;
    $this->requiresAuthentication = $requiresAuthentication;
    $this->description            = $description;
    $this->url                    = $url;
    $this->logoUrl                = $logoUrl;
    $this->customFields           = $customFields;
    $this->customFieldsData       = $customFieldsData;
    $this->extraFields            = $extraFields;
    $this->maximumJobsPerBatch    = $maximumJobsPerBatch;
    $this->autoRefreshProjectOptions = $autoRefreshProjectOptions;
  }


  public function getId(): int {
    return $this->id;
  }


  public function getName(): string {
    return $this->name;
  }


  public function isRequiresAuthentication(): bool {
    return $this->requiresAuthentication;
  }


  public function getDescription(): string {
    return $this->description;
  }


  public function getUrl(): string {
    return $this->url;
  }


  public function getLogoUrl(): string {
    return $this->logoUrl;
  }


  /**
   * @return mixed[]
   */
  public function getCustomFields(): array {
    return $this->customFields;
  }


  /**
   * @return mixed[]
   */
  public function getCustomFieldsData(): array {
    return $this->customFieldsData;
  }


  public function isAuthenticated(): bool {
    if ( ! $this->requiresAuthentication ) {
      return true;
    }

    return ! empty( $this->customFieldsData );
  }


  /**
   * @param RemoteTranslationServiceExtraField[] $extraFields
   *
   * @return void
   */
  public function setExtraFields( array $extraFields ) {
    $this->extraFields = $extraFields;
  }


  /**
   * @return RemoteTranslationServiceExtraField[]
   */
  public function getExtraFields(): array {
    return $this->extraFields;
  }


  /**
   * @return int | null
   */
  public function getMaximumJobsPerBatch() {
    return $this->maximumJobsPerBatch;
  }


  public function getAutoRefreshProjectOptions(): bool {
    return $this->autoRefreshProjectOptions;
  }


  /**
   * @return array{
   *   id: int,
   *   name: string,
   *   url: string,
   *   isAuthenticated: bool,
   *   maximumJobsPerBatch: int|null,
   *   extraFields: array<array{
   *   type: string,
   *   label: string,
   *   name: string,
   *   items: ExtraFieldItems|null
   * }>,
   *   autoRefreshProjectOptions: bool
   * }
   */
  public function toArray(): array {
    return [
      'id'                  => $this->getId(),
      'name'                => $this->getName(),
      'url'                 => $this->getUrl(),
      'isAuthenticated'     => $this->isAuthenticated(),
      // If maximumJobsPerBatch is NULL this means that we're not going to chunk translation service jobs separately
      'maximumJobsPerBatch' => $this->getMaximumJobsPerBatch(),
      'extraFields'         => array_map(
        function ( $field ) {
          return $field->toArray();
        },
        $this->getExtraFields()
      ),
      'autoRefreshProjectOptions' => $this->getAutoRefreshProjectOptions()
    ];
  }


}
