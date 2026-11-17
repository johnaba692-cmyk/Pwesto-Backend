<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Account profile name and photo.
 */
class Profile extends MY_Controller
{
    private const PHOTO_URL_PREFIX = '/uploads/pwesto-profile/';

    public function before_action()
    {
        parent::before_action();
        $this->call->model('UserModel');
        $this->call->library('upload_validator');
    }

    /** POST /api/profile (multipart: name, optional picture) */
    public function update()
    {
        $user = $this->require_user();
        $this->verify_csrf_token();
        $name = $this->string_input($this->input(), 'name');
        if ($this->text_length($name) < 2 || $this->text_length($name) > 80) {
            $this->fail('Enter a name between 2 and 80 characters.', 422);
        }

        $previousPicture = $user['picture'] ?? '';
        $picture = $previousPicture;
        try {
            $photo = $this->upload_validator->validate('picture', [
                'max_bytes' => 2097152,
                'types' => Upload_validator::IMAGE_TYPES,
                'messages' => [
                    'too_large' => 'Choose a profile photo smaller than 2 MB.',
                    'invalid' => 'The profile photo could not be uploaded. Choose it again and retry.',
                    'type' => 'Use a valid JPEG, PNG, or WebP image up to 5,000 × 5,000 pixels.',
                    'image' => 'Use a valid JPEG, PNG, or WebP image up to 5,000 × 5,000 pixels.',
                ],
            ]);
        } catch (Upload_rejected $rejected) {
            $this->fail($rejected->getMessage(), $rejected->getCode());
        }
        if ($photo !== null) {
            $picture = $this->store_photo($photo);
        }

        try {
            $updatedUser = $this->UserModel->update_profile($user['id'], $name, $picture);
        } catch (Throwable $error) {
            if ($picture !== $previousPicture) {
                $this->delete_photo($picture);
            }
            error_log('Pwesto profile update failed: ' . $error->getMessage());
            $this->fail('Your profile could not be updated. Please try again.', 503);
        }
        $this->session->set_userdata('pwesto_user', $updatedUser);
        if ($picture !== $previousPicture) {
            $this->delete_photo($previousPicture);
        }
        $this->respond(['user' => $updatedUser]);
    }

    private function photo_directory()
    {
        return ROOT_DIR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pwesto-profile';
    }

    /** Save under a random file name; the uploads folder never executes scripts (.htaccess). */
    private function store_photo(array $photo)
    {
        $directory = $this->photo_directory();
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            $this->fail('The profile photo folder could not be created.', 503);
        }
        $fileName = random_id() . '.' . $photo['extension'];
        if (!move_uploaded_file($photo['tmp_name'], $directory . DIRECTORY_SEPARATOR . $fileName)) {
            $this->fail('The profile photo could not be saved. Check server upload permissions.', 503);
        }
        return self::PHOTO_URL_PREFIX . $fileName;
    }

    private function delete_photo($picture)
    {
        if (!is_string($picture) || strpos($picture, self::PHOTO_URL_PREFIX) !== 0) {
            return;
        }
        $fileName = substr($picture, strlen(self::PHOTO_URL_PREFIX));
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $fileName)) {
            return;
        }
        $path = $this->photo_directory() . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($path) && !unlink($path)) {
            error_log('Pwesto old profile photo could not be removed: ' . $path);
        }
    }
}
