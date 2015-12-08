<?php

namespace Drupal\DKANExtension\Context;


use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class ResourceContext extends RawDKANEntityContext{

    use ModeratorTrait;

    public function __construct() {
        parent::__construct(
            'node',
            'resource',
            NULL,
            array('moderation', 'moderation_date')
        );
    }

    /**
     * Creates resources from a table.
     *
     * @Given resources:
     */
    public function addResources(TableNode $resourcesTable){
        parent::addMultipleFromTable($resourcesTable);
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope){
        parent::gatherContexts($scope);
        $environment = $scope->getEnvironment();
        $this->groupContext = $environment->getContext('Drupal\DKANExtension\Context\GroupContext');
        $this->datasetContext = $environment->getContext('Drupal\DKANExtension\Context\DatasetContext');
    }

    /**
     *  Sets the multi-fields for body, resource format, and the references to this resource's
     *   dataset and group.
     *
     * @param $entity - the stdClass entity to wrap
     * @return \EntityMetadataWrapper of the entity
     */
    public function wrap($entity){

        $body = $entity->body;
        $group = $this->groupContext->getGroupByName($entity->og_group_ref);
        $terms = taxonomy_get_term_by_name($entity->field_format);
        $term = array_values($terms)[0];
        $dataset = $this->datasetContext->getDatasetByName($entity->field_dataset_ref);

        unset($entity->body);
        unset($entity->og_group_ref);
        unset($entity->field_format);
        unset($entity->field_dataset_ref);
        $wrapper = entity_metadata_wrapper('node', $entity, array('bundle' => 'resource'));
        $wrapper->body->set(array('value' => $body));

        // To-do: add in support for multiple groups
        $wrapper->og_group_ref->set(array($group->nid->value()));

        $wrapper->field_format->set($term->tid);
        $wrapper->field_dataset_ref->set(array($dataset->nid->value()));

        return $wrapper;
    }

    /**
     * Override RawDKANEntityContext::post_save()
     */
    public function post_save($wrapper, $fields) {
        parent::post_save($wrapper, $fields);
        $this->moderate($wrapper, $fields);
    }

}
