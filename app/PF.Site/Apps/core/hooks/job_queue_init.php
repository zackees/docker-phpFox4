<?php
\Core\Queue\Manager::instance()
	->addHandler('core_get_facebook_images', '\Apps\PHPfox_Core\Job\GetFacebookImages')
	->addHandler('core_clone_phpfox_tag', '\Apps\PHPfox_Core\Job\ClonePhpfoxTag')
	->addHandler('core_transfer_asset_files', '\Apps\PHPfox_Core\Job\TransferAssetFiles')
	->addHandler('core_storage_transfer_files', '\Apps\PHPfox_Core\Job\TransferStorageFiles')
	->addHandler('core_storage_transfer_files_update_db', '\Apps\PHPfox_Core\Job\TransferStorageFilesUpdateDB')
	->addHandler('core_storage_transfer_files_remove_local', '\Apps\PHPfox_Core\Job\TransferStorageFilesRemoveLocal')
	->addHandler('core_storage_transfer_files_update_db_execute', '\Apps\PHPfox_Core\Job\TransferStorageFilesUpdateDBExecute')
;