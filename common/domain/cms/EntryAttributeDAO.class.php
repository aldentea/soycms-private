<?php
/**
 * @entity cms.EntryAttribute
 */
abstract class EntryAttributeDAO extends SOY2DAO{

    abstract function insert(EntryAttribute $bean);

	/**
     * @query #entryId# = :entryId AND #fieldId# = :fieldId
     */
    abstract function update(EntryAttribute $bean);

    /**
     * @index fieldId
     */
    abstract function getByEntryId($entryId);

	/**
	 * @return object
	 * @query #entryId# = :entryId AND #fieldId# = :fieldId
	 */
    abstract function get($entryId, $fieldId);

    abstract function deleteByEntryId($entryId);

    /**
     * @query #entryId# = :entryId AND #fieldId# = :fieldId
     */
    abstract function delete($entryId, $fieldId);

    abstract function deleteByFieldId($fieldId);
}
?>