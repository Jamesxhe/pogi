USE `pup_pick`;

ALTER TABLE `claims`
    ADD COLUMN `claim_image_path` VARCHAR(255) DEFAULT NULL
    AFTER `claim_message`;
